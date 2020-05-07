<?php

namespace Mblarsen\LaravelRepository\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\JsonResource;
use Mblarsen\LaravelRepository\Tests\Models\User;
use Mblarsen\LaravelRepository\Tests\Repositories\UserRepository;

class ExtensionTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setup();

        User::firstOrCreate(['first_name' => 'foo', 'last_name' => 'jensen', 'email' => 'foo@example.com', 'password' => 'seeekwed']);
        User::firstOrCreate(['first_name' => 'bar', 'last_name' => 'larsen',  'email' => 'bar@example.com', 'password' => 'seeekwed']);
        User::firstOrCreate(['first_name' => 'mars', 'last_name' => 'jensen',  'email' => 'mars@example.com', 'password' => 'seeekwed']);
    }

    /** @test */
    public function uses_default_sort_by()
    {
        $repository = resolve(UserRepository::class);
        $users = $repository->all();

        $this->assertEquals(3, $users->count());

        $this->assertEquals(
            ['bar', 'foo', 'mars'],
            $users->pluck(['first_name'])->toArray()
        );
    }

    /** @test */
    public function uses_default_resource()
    {
        $repository = resolve(UserRepository::class);
        /** @var JsonResource */
        $users = $repository->allResources(User::query()->select(['id', 'first_name']));

        $this->assertEquals(3, $users->count());

        $this->assertEquals(
            [
                ['id' => 2, 'first_name' => 'bar'],
                ['id' => 1, 'first_name' => 'foo'],
                ['id' => 3, 'first_name' => 'mars']
            ],
            $users->toArray(request())
        );
    }

    /** @test */
    public function uses_default_list_column()
    {
        $repository = resolve(UserRepository::class);
        $users = $repository->list();

        $this->assertEquals(3, $users->count());

        $this->assertEquals(
            [
                ['value' => 2, 'label' => 'bar larsen'],
                ['value' => 1, 'label' => 'foo jensen'],
                ['value' => 3, 'label' => 'mars jensen']
            ],
            $users->toArray()
        );
    }
}
