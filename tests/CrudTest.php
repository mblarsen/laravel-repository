<?php

namespace Mblarsen\LaravelRepository\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\Tests\Models\User;

class CrudTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    /** @test */
    public function can_create()
    {
        $user = Repository::for(User::class)->create([
            'first_name' => 'Wow',
            'last_name' => 'Crab',
            'email' => 'wow@example.com',
            'password' => 'seeekwed'
        ]);
        $this->assertNotNull($user);
        $this->assertEquals('Wow', $user->first_name);
    }

    /** @test */
    public function can_update()
    {
        $repository = Repository::for(User::class);
        $user = $repository->create([
            'first_name' => 'Wow',
            'last_name' => 'Crab',
            'email' => 'crab@example.com',
            'password' => 'seeekwed'
        ]);

        $user = $repository
            ->update($user, ['first_name' => 'Mr. Crab'])
            ->fresh();

        $this->assertEquals('Mr. Crab', $user->first_name);
    }

    /** @test */
    public function can_destroy()
    {
        $email = 'doomed@example.com';
        $repository = Repository::for(User::class);
        $user = $repository->create([
            'first_name' => 'Wow',
            'last_name' => 'Crab',
            'email' => $email,
            'password' => 'seeekwed'
        ]);

        $repository->destroy($user);

        $this->expectException(ModelNotFoundException::class);
        User::whereEmail($email)->firstOrFail();
    }
}
