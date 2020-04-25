<?php

namespace Mblarsen\LaravelRepository\Tests;

use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\RequestResourceContext;
use Mblarsen\LaravelRepository\ResourceContext;
use Mblarsen\LaravelRepository\Tests\Models\User;

class SetupTest extends TestCase
{
    /** @test */
    public function migration_works()
    {
        $user = User::create([
            'name' => 'Wowcrab',
            'email' => 'wow@example.com',
            'password' => 'seeekwed'
        ]);
        $this->assertNotNull($user);
        $this->assertEquals(1, user::count());
    }

    /** @test */
    public function repository_resolves()
    {
        $this->assertInstanceOf(RequestResourceContext::class, resolve(ResourceContext::class));
        $this->assertInstanceOf(Repository::class, resolve(Repository::class));
        $this->assertInstanceOf(RequestResourceContext::class, resolve(Repository::class)->getContext());
    }
}
