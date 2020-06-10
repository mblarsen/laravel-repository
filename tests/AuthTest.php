<?php

namespace Mblarsen\LaravelRepository\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\Tests\Models\Post;
use Mblarsen\LaravelRepository\Tests\Models\User;
use Mblarsen\LaravelRepository\Tests\Policies\PostPolicy;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function turned_off_success()
    {
        $user = factory(User::class)->create();

        $post = Repository::for(Post::class)->create([
            'title' => 'aliens',
            'body' => 'Dolor eius amet pariatur minus repudiandae',
            'user_id' => $user->id
        ]);

        $this->assertNotNull($post);
    }

    /** @test */
    public function turned_on_fail()
    {
        $user = factory(User::class)->create();

        $this->expectErrorMessage('This action is unauthorized.');

        Repository::for(Post::class)
            ->shouldAuthorize()
            ->create([
                'title' => 'aliens',
                'body' => 'Dolor eius amet pariatur minus repudiandae',
                'user_id' => $user->id
            ]);
    }

    /** @test */
    public function turned_on_with_guard_success()
    {
        Gate::policy(Post::class, PostPolicy::class);

        $user = $this->logIn();

        $post = Repository::for(Post::class)
            ->shouldAuthorize()
            ->create([
                'title' => 'aliens',
                'body' => 'Dolor eius amet pariatur minus repudiandae',
                'user_id' => $user->id
            ]);

        $this->assertNotNull($post);
    }

    protected function logIn(User $user = null)
    {
        $user = $user ?: factory(User::class)->create();

        Auth::setUser($user);

        return $user;
    }
}
