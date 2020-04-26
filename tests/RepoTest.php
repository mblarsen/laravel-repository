<?php

namespace Mblarsen\LaravelRepository\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\Tests\Models\User;

class RepoTest extends TestCase
{
    /** @test */
    public function can_create()
    {
        $user = Repository::for(User::class)->create([
            'name' => 'Wowcrab',
            'email' => 'wow@example.com',
            'password' => 'seeekwed'
        ]);
        $this->assertNotNull($user);
        $this->assertEquals('Wowcrab', $user->name);
    }

    /** @test */
    public function can_update()
    {
        $repository = Repository::for(User::class);
        $user = $repository->create([
            'name' => 'Wowcrab',
            'email' => 'crab@example.com',
            'password' => 'seeekwed'
        ]);

        $user = $repository
            ->update($user, ['name' => 'Mr. Crab'])
            ->fresh();

        $this->assertEquals('Mr. Crab', $user->name);
    }

    /** @test */
    public function can_destroy()
    {
        $email = 'doomed@example.com';
        $repository = Repository::for(User::class);
        $user = $repository->create([
            'name' => 'Wowcrab',
            'email' => $email,
            'password' => 'seeekwed'
        ]);

        $repository->destroy($user);

        $this->expectException(ModelNotFoundException::class);
        User::whereEmail($email)->firstOrFail();
    }
}
