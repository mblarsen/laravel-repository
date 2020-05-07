<?php

namespace Mblarsen\LaravelRepository\Tests;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\Tests\Models\Comment;
use Mblarsen\LaravelRepository\Tests\Models\Country;
use Mblarsen\LaravelRepository\Tests\Models\Post;
use Mblarsen\LaravelRepository\Tests\Models\User;

class FilterAndSortTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    /** @test */
    public function fetches_all()
    {
        factory(User::class)->create(['first_name' => 'foo']);
        factory(User::class)->create(['first_name' => 'bar']);
        factory(User::class)->create(['first_name' => 'mars']);

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
        factory(User::class)->create(['first_name' => 'foo']);
        factory(User::class)->create(['first_name' => 'bar']);
        factory(User::class)->create(['first_name' => 'mars']);

        /** @var LengthAwarePaginator */
        $users = Repository::for(User::class, [
            'page' => 1,
            'per_page' => 2,
        ])->all();

        $this->assertInstanceOf(LengthAwarePaginator::class, $users);

        $this->assertEquals(2, count($users->items()));
        $this->assertEquals(3, $users->total());
        $this->assertEquals(1, $users->firstItem());
        $this->assertEquals(2, $users->lastItem());

        $this->assertEquals(['foo', 'bar'], Arr::pluck($users->items(), ['first_name']));

        /** @var LengthAwarePaginator */
        $users = Repository::for(User::class, [
            'page' => 2,
            'per_page' => 2,
        ])->all();

        $this->assertEquals(1, count($users->items()));
        $this->assertEquals(3, $users->total());
        $this->assertEquals(3, $users->firstItem());
        $this->assertEquals(3, $users->lastItem());

        $this->assertEquals(['mars'], Arr::pluck($users->items(), ['first_name']));
    }

    /** @test */
    public function fetches_list()
    {
        $user = factory(User::class)->create();
        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens']),
            factory(Post::class)->make(['title' => 'fish']),
            factory(Post::class)->make(['title' => 'boats']),
            factory(Post::class)->make(['title' => 'bat']),
        ]);

        /** @var Collection $posts */
        $posts = Repository::for(Post::class)->list('title');

        $this->assertEquals(
            [
                ['value' => 1, 'label' => 'aliens'],
                ['value' => 2, 'label' => 'fish'],
                ['value' => 3, 'label' => 'boats'],
                ['value' => 4, 'label' => 'bat']
            ],
            $posts->toArray()
        );
    }

    /** @test */
    public function fetches_list_default_to_sort_by()
    {
        $user = factory(User::class)->create();
        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens']),
            factory(Post::class)->make(['title' => 'fish']),
            factory(Post::class)->make(['title' => 'boats']),
            factory(Post::class)->make(['title' => 'bat']),
        ]);

        /** @var Collection $posts */
        $posts = Repository::for(Post::class)
            ->setDefaultSort('title')
            ->list();

        $this->assertEquals(
            [
                ['value' => 1, 'label' => 'aliens'],
                ['value' => 4, 'label' => 'bat'],
                ['value' => 3, 'label' => 'boats'],
                ['value' => 2, 'label' => 'fish'],
            ],
            $posts->toArray()
        );
    }

    /** @test */
    public function fetches_list_paginated()
    {
        $user = factory(User::class)->create();
        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens']),
            factory(Post::class)->make(['title' => 'fish']),
            factory(Post::class)->make(['title' => 'boats']),
            factory(Post::class)->make(['title' => 'bat']),
        ]);

        /** @var LengthAwarePaginator */
        $posts = Repository::for(
            Post::class,
            ['page' => 1, 'per_page' => 2]
        )
            ->list('title');

        $this->assertEquals(
            [
                ['value' => 1, 'label' => 'aliens'],
                ['value' => 2, 'label' => 'fish'],
            ],
            collect($posts->items())->toArray()
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
        factory(User::class)->create(['first_name' => 'foo', 'last_name' => 'jensen']);
        factory(User::class)->create(['first_name' => 'bar', 'last_name' => 'larsen']);
        factory(User::class)->create(['first_name' => 'mars', 'last_name' => 'jensen']);

        /** @var Collection $posts */
        $users = Repository::for(User::class)->list(function ($user) {
            return $user->full_name;
        });

        $this->assertEquals(
            [
                ['value' => 1, 'label' => 'foo jensen'],
                ['value' => 2, 'label' => 'bar larsen'],
                ['value' => 3, 'label' => 'mars jensen']
            ],
            $users->toArray()
        );
    }

    /** @test */
    public function fetches_list_paginated_with_callback()
    {
        factory(User::class)->create(['first_name' => 'foo', 'last_name' => 'jensen']);
        factory(User::class)->create(['first_name' => 'bar', 'last_name' => 'larsen']);
        factory(User::class)->create(['first_name' => 'mars', 'last_name' => 'jensen']);

        /** @var LengthAwarePaginator */
        $users = Repository::for(
            User::class,
            ['page' => 1, 'per_page' => 2]
        )
            ->list(function ($user) {
                return $user->full_name;
            });

        $this->assertEquals(
            [
                ['value' => 1, 'label' => 'foo jensen'],
                ['value' => 2, 'label' => 'bar larsen'],
            ],
            collect($users->items())->toArray()
        );
    }

    /** @test */
    public function fetch_one()
    {
        factory(User::class)->create();

        $user = Repository::for(User::class)->find(1);

        $this->assertInstanceOf(User::class, $user);
    }

    /** @test */
    public function includes_relations()
    {
        factory(User::class)->create();

        $repository = Repository::for(User::class, [
            'with' => [
                'comments',
                'posts',
            ],
        ]);

        $repository->setAllowedWith(['posts']);

        /** @var User $user */
        $user = $repository->find(1);
        $this->assertNotTrue($user->relationLoaded('comments'), 'comments should not be loaded');
        $this->assertTrue($user->relationLoaded('posts'), 'posts should be loaded');
    }

    /** @test */
    public function includes_allow_all()
    {
        factory(User::class)->create();

        $repository = Repository::for(User::class, [
            'with' => [
                'comments',
                'posts',
            ],
        ]);

        $repository->setAllowedWith(['*']);

        /** @var User $user */
        $user = $repository->find(1);
        $this->assertTrue($user->relationLoaded('comments'), 'comments should be loaded');
        $this->assertTrue($user->relationLoaded('posts'), 'posts should be loaded');
    }

    /** @test */
    public function includes_default_with()
    {
        factory(User::class)->create();

        $repository = Repository::for(User::class, [
            'with' => [
                'comments',
            ],
        ])
            ->setAllowedWith(['comments'])
            ->setDefaultWith(['posts']);

        /** @var User $user */
        $user = $repository->find(1);
        $this->assertTrue($user->relationLoaded('comments'), 'comments should be loaded');
        $this->assertTrue($user->relationLoaded('posts'), 'posts should be loaded');
    }

    /** @test */
    public function set_allowed_with_is_set()
    {
        factory(User::class)->create();

        $repository = Repository::for(User::class);
        $repository->setContext([
            'with' => [
                'comments',
            ],
        ], true);
        $user = $repository->find(1);
        $this->assertTrue($user->relationLoaded('comments'), 'comments should be loaded');
    }

    /** @test */
    public function sort_asc()
    {
        factory(User::class)->create(['first_name' => 'foo']);
        factory(User::class)->create(['first_name' => 'bar']);
        factory(User::class)->create(['first_name' => 'mars']);

        $repository = Repository::for(User::class, [
            'sort_by' => 'first_name',
        ]);

        /** @var Collection */
        $users = $repository->all();;

        $this->assertEquals([2, 1, 3], $users->pluck(['id'])->toArray());
    }

    /** @test */
    public function sort_desc()
    {
        factory(User::class)->create(['first_name' => 'foo']);
        factory(User::class)->create(['first_name' => 'bar']);
        factory(User::class)->create(['first_name' => 'mars']);

        $repository = Repository::for(User::class, [
            'sort_by' => 'first_name',
            'sort_order' => 'desc',
        ]);

        /** @var Collection */
        $users = $repository->all();

        $this->assertEquals([3, 1, 2], $users->pluck(['id'])->toArray());
    }

    /** @test */
    public function sort_default()
    {
        factory(User::class)->create(['first_name' => 'foo']);
        factory(User::class)->create(['first_name' => 'bar']);
        factory(User::class)->create(['first_name' => 'mars']);

        $repository = Repository::for(User::class)
            ->setDefaultSort('first_name');

        /** @var Collection */
        $users = $repository->all();

        $this->assertEquals([2, 1, 3], $users->pluck(['id'])->toArray());
    }

    /** @test */
    public function sort_by_has_one_relation()
    {
        $user = factory(User::class)->create(['first_name' => 'foo']);
        $user->posts()->saveMany([
            factory(Post::class)->create(['title' => 'aliens', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'fish', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'boats', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'bat', 'user_id' => $user->id]),
        ]);

        $user->posts[0]->postMeta->update(['version' => 3]);
        $user->posts[1]->postMeta->update(['version' => 2]);
        $user->posts[2]->postMeta->update(['version' => 1]);
        $user->posts[3]->postMeta->update(['version' => 9]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'sort_by' => 'postMeta.version',
            ]);

        /** @var Collection */
        $posts = $repository->all();

        $this->assertEquals(['boats', 'fish', 'aliens', 'bat'], $posts->pluck(['title'])->toArray());
    }

    /** @test */
    public function sort_by_belongs_to_relation()
    {
        $user_1 = factory(User::class)->create(['first_name' => 'foo']);
        $user_2 = factory(User::class)->create(['first_name' => 'bar']);
        $user_3 = factory(User::class)->create(['first_name' => 'mars']);

        factory(Post::class)->create(['title' => 'aliens', 'user_id' => $user_1->id]);
        factory(Post::class)->create(['title' => 'fish', 'user_id' => $user_2->id]);
        factory(Post::class)->create(['title' => 'boats', 'user_id' => $user_3->id]);
        factory(Post::class)->create(['title' => 'bat', 'user_id' => $user_3->id]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'sort_by' => 'user.first_name',
            ]);
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(['fish', 'aliens', 'boats', 'bat'], $posts->pluck(['title'])->toArray());
    }

    /** @test */
    public function sort_by_morph_relation()
    {
        $user = factory(User::class)->create();

        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens']),
            factory(Post::class)->make(['title' => 'fish']),
            factory(Post::class)->make(['title' => 'boats']),
            factory(Post::class)->make(['title' => 'bat']),
        ]);

        $user->posts[1]->comments()->save(
            factory(Comment::class)->make(['user_id' => $user->id, 'created_at' => now()])
        );
        $user->posts[2]->comments()->save(
            factory(Comment::class)->make(['user_id' => $user->id, 'created_at' => now()->addMinutes(5)])
        );

        $repository = Repository::for(Post::class)
            ->setContext([
                'sort_by' => 'comments.created_at',
                'sort_order' => 'asc'
            ]);

        /** @var Collection */
        $posts = $repository->all();
        // null first
        // null
        // null
        // now
        // now+5
        $this->assertEquals(['aliens', 'bat', 'fish', 'boats'], $posts->pluck(['title'])->toArray());

        $repository = Repository::for(Post::class)
            ->setContext([
                'sort_by' => 'comments.created_at',
                'sort_order' => 'desc'
            ]);

        /** @var Collection */
        $posts = $repository->all();
        // null last
        // now+5
        // now
        // null
        // null
        $this->assertEquals(['boats', 'fish', 'aliens', 'bat'], $posts->pluck(['title'])->toArray());
    }

    /** @test */
    public function sort_by_unsupported_relation()
    {
        $repository = Repository::for(Country::class)
            ->setContext([
                'sort_by' => 'posts.title',
            ]);

        $this->expectExceptionMessage('Relation type HasManyThrough is not supported');
        $repository->all();
    }


    /** @test */
    public function filter_one()
    {
        $user = factory(User::class)->create();
        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens']),
            factory(Post::class)->make(['title' => 'fish']),
            factory(Post::class)->make(['title' => 'boats']),
            factory(Post::class)->make(['title' => 'bat']),
        ]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'filters' => [
                    'title' => 'oat' // boat
                ]
            ]);
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('boats', $posts->first()->title);
    }

    /** @test */
    public function filter_one_exact()
    {
        $user = factory(User::class)->create();
        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens']),
            factory(Post::class)->make(['title' => 'fish']),
            factory(Post::class)->make(['title' => 'boats']),
            factory(Post::class)->make(['title' => 'bat']),
        ]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'filters' => [
                    'title!' => 'oat' // boat
                ]
            ]);
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(0, $posts->count());

        $repository->setContext([
            'filters' => [
                'title!' => 'boats' // boats
            ]
        ]);

        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('boats', $posts->first()->title);
    }

    /** @test */
    public function filter_multiple_different_values()
    {
        $user = factory(User::class)->create();
        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens', 'body' => 'foo']),
            factory(Post::class)->make(['title' => 'fish', 'body' => 'nobody']),
            factory(Post::class)->make(['title' => 'boats', 'body' => 'foo']),
            factory(Post::class)->make(['title' => 'bat', 'body' => 'foo']),
        ]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'filters' => [
                    'title' => 'i', // alien and fish
                    'body' => 'y' // only fish
                ]
            ]);
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('fish', $posts->first()->title);
    }

    /** @test */
    public function filter_multiple_different_values_exact()
    {
        $user = factory(User::class)->create();
        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens', 'body' => 'foo']),
            factory(Post::class)->make(['title' => 'fish', 'body' => 'nobody']),
            factory(Post::class)->make(['title' => 'boats', 'body' => 'foo']),
            factory(Post::class)->make(['title' => 'bat', 'body' => 'foo']),
        ]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'filters' => [
                    'title!' => 'i', // alien and fish
                    'body' => 'y' // only fish
                ]
            ]);
        $posts = $repository->all();
        $this->assertEquals(0, $posts->count());

        $repository->setContext([
            'filters' => [
                'title!' => 'fish', // alien and fish
                'body' => 'y' // only fish
            ]
        ]);
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('fish', $posts->first()->title);
    }

    /** @test */
    public function filter_multiple_same_value()
    {
        $user = factory(User::class)->create();
        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens', 'body' => 'foo']),
            factory(Post::class)->make(['title' => 'fish', 'body' => 'nobody']),
            factory(Post::class)->make(['title' => 'boats', 'body' => 'singer']),
            factory(Post::class)->make(['title' => 'bat', 'body' => 'foo']),
        ]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'filters' => [
                    'title|body' => 'i', // alien and fish has in title, boats has in body
                ]
            ]);
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(3, $posts->count());
    }

    /** @test */
    public function filter_multiple_same_value_exact()
    {
        $user = factory(User::class)->create();
        $user->posts()->saveMany([
            factory(Post::class)->make(['title' => 'aliens', 'body' => 'foo']),
            factory(Post::class)->make(['title' => 'fish', 'body' => 'nobody']),
            factory(Post::class)->make(['title' => 'boats', 'body' => 'singer']),
            factory(Post::class)->make(['title' => 'bats', 'body' => 'foo']),
        ]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'filters' => [
                    'title|body' => 'bat',
                ]
            ]);
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());

        $repository->setContext([
            'filters' => [
                'title|body!' => 'bat',
            ]
        ]);
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());

        $repository->setContext([
            'filters' => [
                'title!|body' => 'bat',
            ]
        ]);
        $posts = $repository->all();
        $this->assertEquals(0, $posts->count());

        $repository->setContext([
            'filters' => [
                'title!|body!' => 'bat',
            ]
        ]);
        $posts = $repository->all();
        $this->assertEquals(0, $posts->count());
    }

    /** @test */
    public function filter_using_concat()
    {
        try {
            $user = factory(User::class)->create();
            $user->posts()->saveMany([
                factory(Post::class)->make(['title' => 'aliens', 'body' => 'Nothing is out there']),
                factory(Post::class)->make(['title' => 'fish', 'body' => 'nobody']),
                factory(Post::class)->make(['title' => 'boats', 'body' => 'No one is on them']),
                factory(Post::class)->make(['title' => 'bats', 'body' => 'foo']),
            ]);

            $repository = Repository::for(Post::class)
                ->setContext([
                    'sort_by' => 'title',
                    'filters' => [
                        'title+body' => 's no', // 'aliens Nothing...', 'boats No one...'
                    ]
                ]);
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
    public function filter_using_concat_exact()
    {
        try {
            $user = factory(User::class)->create();
            $user->posts()->saveMany([
                factory(Post::class)->make(['title' => 'aliens', 'body' => 'Nothing is out there']),
                factory(Post::class)->make(['title' => 'fish', 'body' => 'nobody']),
                factory(Post::class)->make(['title' => 'boats', 'body' => 'No one is on them']),
                factory(Post::class)->make(['title' => 'bats', 'body' => 'foo']),
            ]);

            $repository = Repository::for(Post::class)
                ->setContext([
                    'sort_by' => 'title',
                    'filters' => [
                        'title+body!' => 's no',
                    ]
                ]);
            /** @var Collection */
            $posts = $repository->all();
            $this->assertEquals(0, $posts->count());

            $repository->setContext([
                'sort_by' => 'title',
                'filters' => [
                    'title+body!' => 'aliens Nothing is out there',
                ]
            ]);
            /** @var Collection */
            $posts = $repository->all();
            $this->assertEquals(1, $posts->count());
            $this->assertEquals(['aliens'], $posts->pluck(['title'])->toArray());
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
        $user = factory(User::class)->create(['first_name' => 'foo']);
        $user->posts()->saveMany([
            factory(Post::class)->create(['title' => 'aliens', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'fish', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'boats', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'bat', 'user_id' => $user->id]),
        ]);

        $user->posts[0]->postMeta->update(['version' => 3]);
        $user->posts[1]->postMeta->update(['version' => 2]);
        $user->posts[2]->postMeta->update(['version' => 1]);
        $user->posts[3]->postMeta->update(['version' => 9]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'filters' => [
                    'postMeta.version' => 3,
                ]
            ]);
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('aliens', $posts->first()->title);
    }

    /** @test */
    public function filter_by_relation_exact_number()
    {
        $user = factory(User::class)->create(['first_name' => 'foo']);
        $user->posts()->saveMany([
            factory(Post::class)->create(['title' => 'aliens', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'fish', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'boats', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'bat', 'user_id' => $user->id]),
        ]);

        $user->posts[0]->postMeta->update(['version' => 3]);
        $user->posts[1]->postMeta->update(['version' => 2]);
        $user->posts[2]->postMeta->update(['version' => 1]);
        $user->posts[3]->postMeta->update(['version' => 9]);

        $repository = Repository::for(Post::class)
            ->setContext([
                'filters' => [
                    'postMeta.version!' => 9,
                ]
            ]);
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('bat', $posts->first()->title);
    }

    /** @test */
    public function filter_by_relation_exact_string()
    {
        $user = factory(User::class)->create(['first_name' => 'foo']);
        $user->posts()->saveMany([
            factory(Post::class)->create(['title' => 'aliens', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'fish', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'boats', 'user_id' => $user->id]),
            factory(Post::class)->create(['title' => 'bat', 'user_id' => $user->id]),
        ]);

        $user->posts[0]->postMeta->update(['version' => 3]);
        $user->posts[1]->postMeta->update(['version' => 2]);
        $user->posts[2]->postMeta->update(['version' => 1]);
        $user->posts[3]->postMeta->update(['version' => 9, 'code' => 'numbat']);

        $repository = Repository::for(Post::class)
            ->setContext([
                'filters' => [
                    'postMeta.code!' => "numbat",
                ]
            ]);
        /** @var Collection */
        $posts = $repository->all();
        $this->assertEquals(1, $posts->count());
        $this->assertEquals('bat', $posts->first()->title);
    }

    /** @test */
    public function filter_by_relation_deeper()
    {
        $user_1 = factory(User::class)->create(['first_name' => 'foo']);
        $user_2 = factory(User::class)->create(['first_name' => 'bar']);
        $user_3 = factory(User::class)->create(['first_name' => 'mars']);

        $posts = [
            factory(Post::class)->create(['title' => 'aliens', 'user_id' => $user_1->id]),
            factory(Post::class)->create(['title' => 'fish', 'user_id' => $user_2->id]),
            factory(Post::class)->create(['title' => 'boats', 'user_id' => $user_3->id]),
            factory(Post::class)->create(['title' => 'bat', 'user_id' => $user_3->id]),
        ];

        $posts[0]->postMeta->update(['version' => 3]);
        $posts[1]->postMeta->update(['version' => 2]);
        $posts[2]->postMeta->update(['version' => 1]);
        $posts[3]->postMeta->update(['version' => 9]);

        $repository = Repository::for(User::class)
            ->setContext([
                'filters' => [
                    'posts.postMeta.version' => 3,
                ]
            ]);
        /** @var Collection */
        $users = $repository->all();
        $this->assertEquals(1, $users->count());
        $this->assertEquals('foo', $users->first()->first_name);

        $repository->setContext([
            'filters' => [
                'posts.postMeta.version' => 2,
            ]
        ]);
        /** @var Collection */
        $users = $repository->all();
        $this->assertEquals(1, $users->count());
        $this->assertEquals('bar', $users->first()->first_name);
    }

    /** @test */
    public function filter_by_relation_deeper_exact()
    {
        $user_1 = factory(User::class)->create(['first_name' => 'foo']);
        $user_2 = factory(User::class)->create(['first_name' => 'bar']);
        $user_3 = factory(User::class)->create(['first_name' => 'mars']);

        $posts = [
            factory(Post::class)->create(['title' => 'aliens', 'user_id' => $user_1->id]),
            factory(Post::class)->create(['title' => 'fish', 'user_id' => $user_2->id]),
            factory(Post::class)->create(['title' => 'boats', 'user_id' => $user_3->id]),
            factory(Post::class)->create(['title' => 'bat', 'user_id' => $user_3->id]),
        ];

        $posts[0]->postMeta->update(['version' => 3]);
        $posts[1]->postMeta->update(['version' => 2]);
        $posts[2]->postMeta->update(['version' => 1]);
        $posts[3]->postMeta->update(['version' => 9, 'code' => 'numbat']);

        $repository = Repository::for(User::class)
            ->setContext([
                'filters' => [
                    'posts.postMeta.version!' => 3,
                ]
            ]);
        $users = $repository->all();
        $this->assertEquals(1, $users->count());
        $this->assertEquals('foo', $users->first()->first_name);

        $repository->setContext([
            'filters' => [
                'posts.postMeta.code!' => "bat",
            ]
        ]);
        $users = $repository->all();
        $this->assertEquals(0, $users->count());

        $repository->setContext([
            'filters' => [
                'posts.postMeta.code!' => "numbat",
            ]
        ]);
        $users = $repository->all();
        $this->assertEquals(1, $users->count());
        $this->assertEquals('mars', $users->first()->first_name);
    }

    /** @test */
    public function filter_by_relation_multiple()
    {
        $user_1 = factory(User::class)->create(['first_name' => 'foo']);
        $user_2 = factory(User::class)->create(['first_name' => 'bar']);
        $user_3 = factory(User::class)->create(['first_name' => 'mars']);

        factory(Post::class)->create(['title' => 'aliens', 'user_id' => $user_1->id]);
        factory(Post::class)->create(['title' => 'fish', 'user_id' => $user_2->id]);
        factory(Post::class)->create(['title' => 'boats', 'user_id' => $user_3->id]);
        factory(Post::class)->create(['title' => 'bat', 'user_id' => $user_3->id]);

        $repository = Repository::for(User::class)
            ->setContext([
                'filters' => [
                    'country.name|posts.title' => 'aliens',
                ]
            ]);

        $query_all = $repository->allQuery();

        $this->assertStringContainsString(
            'countries',
            $query_all->toBase()->toSql(),
        );

        $this->assertEquals(1, $query_all->get()->count());
        $this->assertEquals('foo', $query_all->first()->first_name);
    }

    /** @test */
    public function filter_by_relation_multiple_exact()
    {
        $user_1 = factory(User::class)->create(['first_name' => 'foo']);
        $user_2 = factory(User::class)->create(['first_name' => 'bar']);
        $user_3 = factory(User::class)->create(['first_name' => 'mars']);

        factory(Post::class)->create(['title' => 'aliens', 'user_id' => $user_1->id]);
        factory(Post::class)->create(['title' => 'fish', 'user_id' => $user_2->id]);
        factory(Post::class)->create(['title' => 'boats', 'user_id' => $user_3->id]);
        factory(Post::class)->create(['title' => 'bat', 'user_id' => $user_3->id]);

        $repository = Repository::for(User::class)
            ->setContext([
                'filters' => [
                    'country.name|posts.title!' => 'alien',
                ]
            ]);
        $query_all = $repository->allQuery();

        $this->assertStringContainsString(
            'countries',
            $query_all->toBase()->toSql(),
        );
        $this->assertEquals(0, $query_all->get()->count());

        $repository->setContext([
            'filters' => [
                'country.name|posts.title!' => 'aliens',
            ]
        ]);
        $query_all = $repository->allQuery();
        $this->assertEquals(1, $query_all->get()->count());
        $this->assertEquals('foo', $query_all->first()->first_name);
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
