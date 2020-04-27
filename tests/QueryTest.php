<?php

namespace Mblarsen\LaravelRepository\Tests;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Mblarsen\LaravelRepository\ArrayResourceContext;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\Tests\Models\Country;
use Mblarsen\LaravelRepository\Tests\Models\Post;
use Mblarsen\LaravelRepository\Tests\Models\User;

class QueryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setup();

        $user_1 = User::firstOrCreate(['first_name' => 'foo', 'last_name' => 'jensen', 'email' => 'foo@example.com', 'password' => 'seeekwed']);
        $user_2 = User::firstOrCreate(['first_name' => 'bar', 'last_name' => 'larsen',  'email' => 'bar@example.com', 'password' => 'seeekwed']);
        $user_3 = User::firstOrCreate(['first_name' => 'mars', 'last_name' => 'jensen',  'email' => 'mars@example.com', 'password' => 'seeekwed']);

        $post_1 = Post::create([
            'title' => 'aliens', 'body' => 'Nothing interesting', 'user_id' => $user_1->id
        ]);
        $post_1->postMeta()->create(['version' => 3]);
        $post_2 = Post::create([
            'title' => 'fish', 'body' => 'Very repetitive',  'user_id' => $user_2->id
        ]);
        $post_2->postMeta()->create(['version' => 2]);
        $post_2->comments()->create(['body' => 'wow', 'user_id' => $user_1->id, 'created_at' => now()]);
        $post_3 = Post::create([
            'title' => 'boats', 'body' => 'No one will read this', 'user_id' => $user_3->id
        ]);
        $post_3->postMeta()->create(['version' => 1]);
        $post_3->comments()->create(['body' => 'crux', 'user_id' => $user_2->id, 'created_at' => now()->addMinutes(5)]);
    }

    /** @test */
    public function fetches_all()
    {
        /** @var Collection $users */
        $users = Repository::for(User::class)->all();

        $this->assertInstanceOf(Collection::class, $users);

        $this->assertEquals(
            ['foo', 'bar', 'mars'],
            $users->pluck(['first_name'])->toArray()
        );
    }

    /** @test */
    public function fetches_all_paginated()
    {
        /** @var LengthAwarePaginator */
        $users = Repository::for(User::class, ArrayResourceContext::create([
            'page' => 1,
            'per_page' => 2,
        ]))->all();

        $this->assertInstanceOf(LengthAwarePaginator::class, $users);

        $this->assertEquals(2, count($users->items()));
        $this->assertEquals(3, $users->total());
        $this->assertEquals(1, $users->firstItem());
        $this->assertEquals(2, $users->lastItem());

        $this->assertEquals(['foo', 'bar'], Arr::pluck($users->items(), ['first_name']));

        /** @var LengthAwarePaginator */
        $users = Repository::for(User::class, ArrayResourceContext::create([
            'page' => 2,
            'per_page' => 2,
        ]))->all();

        $this->assertEquals(1, count($users->items()));
        $this->assertEquals(3, $users->total());
        $this->assertEquals(3, $users->firstItem());
        $this->assertEquals(3, $users->lastItem());

        $this->assertEquals(['mars'], Arr::pluck($users->items(), ['first_name']));
    }

    /** @test */
    public function fetches_list()
    {
        /** @var Collection $posts */
        $posts = Repository::for(Post::class)->list('title');

        $this->assertEquals(
            [
                ['value' => 1, 'label' => 'aliens'],
                ['value' => 2, 'label' => 'fish'],
                ['value' => 3, 'label' => 'boats']
            ],
            $posts->toArray()
        );
    }

    /** @test */
    public function fetches_list_invalid_column()
    {
        $this->expectException(InvalidArgumentException::class);
        Repository::for(Post::class)->list(['not good']);
    }

    /** @test */
    public function fetches_list_with_callback()
    {
        /** @var Collection $posts */
        $posts = Repository::for(User::class)->list(function ($user) {
            return $user->full_name;
        });

        $this->assertEquals(
            [
                ['value' => 1, 'label' => 'foo jensen'],
                ['value' => 2, 'label' => 'bar larsen'],
                ['value' => 3, 'label' => 'mars jensen']
            ],
            $posts->toArray()
        );
    }

    /** @test */
    public function fetch_one()
    {
        $user = Repository::for(User::class)->find(1);

        $this->assertInstanceOf(User::class, $user);
    }

    /** @test */
    public function includes_relations()
    {
        $repository = Repository::for(User::class, ArrayResourceContext::create([
            'with' => [
                'comments',
                'posts',
            ],
        ]));

        $repository->setAllowedWith(['posts']);

        /** @var User $user */
        $user = $repository->find(1);
        $this->assertNotTrue($user->relationLoaded('comments'), 'comments should not be loaded');
        $this->assertTrue($user->relationLoaded('posts'), 'posts should be loaded');
    }

    /** @test */
    public function includes_allow_all()
    {
        $repository = Repository::for(User::class, ArrayResourceContext::create([
            'with' => [
                'comments',
                'posts',
            ],
        ]));

        $repository->setAllowedWith(Repository::WITH_ALLOW_ALL);

        /** @var User $user */
        $user = $repository->find(1);
        $this->assertTrue($user->relationLoaded('comments'), 'comments should be loaded');
        $this->assertTrue($user->relationLoaded('posts'), 'posts should be loaded');
    }

    /** @test */
    public function includes_default_with()
    {
        $repository = Repository::for(User::class, ArrayResourceContext::create([
            'with' => [
                'comments',
            ],
        ]))
            ->setAllowedWith(['comments'])
            ->setDefaultWith(['posts']);

        /** @var User $user */
        $user = $repository->find(1);
        $this->assertTrue($user->relationLoaded('comments'), 'comments should be loaded');
        $this->assertTrue($user->relationLoaded('posts'), 'posts should be loaded');
    }

    /** @test */
    public function sort_asc()
    {
        $repository = Repository::for(User::class, ArrayResourceContext::create([
            'sort_by' => 'first_name',
        ]));

        /** @var Collection */
        $users = $repository->all();;

        $this->assertEquals([2, 1, 3], $users->pluck(['id'])->toArray());
    }

    /** @test */
    public function sort_desc()
    {
        $repository = Repository::for(User::class, ArrayResourceContext::create([
            'sort_by' => 'first_name',
            'sort_order' => 'desc',
        ]));

        /** @var Collection */
        $users = $repository->all();

        $this->assertEquals([3, 1, 2], $users->pluck(['id'])->toArray());
    }

    /** @test */
    public function sort_default()
    {
        $repository = Repository::for(User::class)
            ->setDefaultSort('first_name');

        /** @var Collection */
        $users = $repository->all();

        $this->assertEquals([2, 1, 3], $users->pluck(['id'])->toArray());
    }

    /** @test */
    public function sort_by_has_one_relation()
    {
        $repository = Repository::for(Post::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'sort_by' => 'postMeta.version',
                ]
            ));

        /** @var Collection */
        $posts = $repository->all();

        $this->assertEquals(['boats', 'fish', 'aliens'], $posts->pluck(['title'])->toArray());
    }

    /** @test */
    public function sort_by_belongs_to_relation()
    {
        $repository = Repository::for(Post::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'sort_by' => 'user.first_name',
                ]
            ));
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(['fish', 'aliens', 'boats'], $posts->pluck(['title'])->toArray());
    }

    /** @test */
    public function sort_by_morph_relation()
    {
        $repository = Repository::for(Post::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'sort_by' => 'comments.created_at',
                    'sort_order' => 'asc'
                ]
            ));

        /** @var Collection */
        $posts = $repository->all();
        // null first
        // null
        // now
        // now+5
        $this->assertEquals(['aliens', 'fish', 'boats'], $posts->pluck(['title'])->toArray());

        $repository = Repository::for(Post::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'sort_by' => 'comments.created_at',
                    'sort_order' => 'desc'
                ]
            ));

        /** @var Collection */
        $posts = $repository->all();
        // null last
        // now+5
        // now
        // null
        $this->assertEquals(['boats', 'fish', 'aliens'], $posts->pluck(['title'])->toArray());
    }

    /** @test */
    public function sort_by_unsupported_relation()
    {
        $repository = Repository::for(Country::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'sort_by' => 'posts.title',
                ]
            ));

        $this->expectExceptionMessage('Relation type HasManyThrough is not supported');
        $repository->all();
    }


    /** @test */
    public function filter_one()
    {
        $repository = Repository::for(Post::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'filters' => [
                        'title' => 'oat' // boat
                    ]
                ]
            ));
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('boats', $posts->first()->title);
    }

    /** @test */
    public function filter_multiple_different_values()
    {
        $repository = Repository::for(Post::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'filters' => [
                        'title' => 'i', // alien and fish
                        'body' => 'y' // only fish
                    ]
                ]
            ));
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('fish', $posts->first()->title);
    }

    /** @test */
    public function filter_multiple_same_value()
    {
        // if (DB::getDriverName() === 'sqlite') {
        //     $this->markTestSkipped();
        //     return;
        // }

        $repository = Repository::for(Post::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'filters' => [
                        'title|body' => 'i', // alien and fish has in title, boat has in body
                    ]
                ]
            ));
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(3, $posts->count());
    }

    /** @test */
    public function filter_using_concat()
    {
        try {
            $repository = Repository::for(Post::class)
                ->setContext(ArrayResourceContext::create(
                    [
                        'sort_by' => 'title',
                        'filters' => [
                            'title+body' => 's no', // 'alients Nothing...', 'boats No one...'
                        ]
                    ]
                ));
            /** @var Collection */
            $posts = $repository->all();

            $this->assertEquals(2, $posts->count());
            $this->assertEquals(['aliens', 'boats'], $posts->pluck(['title'])->toArray());
        } catch (QueryException $e) {
            if (DB::getDriverName() === 'sqlite') {
                $this->markTestSkipped();
            } else {
                throw $e;
            }
        }
    }

    /** @test */
    public function filter_by_relation()
    {
        $repository = Repository::for(Post::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'filters' => [
                        'postMeta.version' => 3,
                    ]
                ]
            ));
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('aliens', $posts->first()->title);
    }

    /** @test */
    public function filter_by_relation_deeper()
    {
        $repository = Repository::for(User::class)
            ->setContext(ArrayResourceContext::create(
                [
                    'filters' => [
                        'posts.postMeta.version' => 3,
                    ]
                ]
            ));
        /** @var Collection */
        $users = $repository->all();
        $this->assertEquals(1, $users->count());
        $this->assertEquals('foo', $users->first()->first_name);

        $repository->setContext(ArrayResourceContext::create(
            [
                'filters' => [
                    'posts.postMeta.version' => 2,
                ]
            ]
        ));
        /** @var Collection */
        $users = $repository->all();
        $this->assertEquals(1, $users->count());
        $this->assertEquals('bar', $users->first()->first_name);
    }

    protected function logDB($callback)
    {
        DB::enableQueryLog();
        try {
            $callback();
        } finally {
            $log = DB::getQueryLog();
            var_dump($log);
            DB::disableQueryLog();
        }
    }
}
