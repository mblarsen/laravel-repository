<?php

namespace Mblarsen\LaravelRepository\Tests;

use BadMethodCallException;
use Exception;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\Tests\Models\User;
use Mblarsen\LaravelRepository\Tests\Resources\UserResource;
use Mblarsen\LaravelRepository\Tests\Resources\UserResourceCollection;

class ResourceTest extends TestCase
{
    /** @test */
    public function outputs_resource()
    {
        $user = User::firstOrCreate(['first_name' => 'foo', 'last_name' => 'jensen', 'email' => 'foo.jensen@example.com', 'password' => 'seeekwed']);

        $repository = Repository::for(User::class);
        $repository->setResource(UserResource::class);

        $resource = $repository->findResource($user->id);

        $this->assertInstanceOf(UserResource::class, $resource);
    }

    /** @test */
    public function throws_when_resource_is_not_set()
    {
        $repository = Repository::for(User::class);

        $this->expectException(Exception::class);
        $repository->allResources();
    }

    /** @test */
    public function throws_when_unknown_method_is_called()
    {
        $repository = Repository::for(User::class);

        $this->expectException(BadMethodCallException::class);
        $repository->fooBar();
    }

    /** @test */
    public function throws_when_unknown_resource_method_is_called()
    {
        $repository = Repository::for(User::class);

        $this->expectException(BadMethodCallException::class);
        $repository->listResources();
    }

    /** @test */
    public function outputs_anonymous_collection()
    {
        $repository = Repository::for(User::class);
        $repository->setResource(UserResource::class);

        $resources = $repository->allResources();

        $this->assertInstanceOf(AnonymousResourceCollection::class, $resources);
    }

    /** @test */
    public function outputs_users_collection()
    {
        $repository = Repository::for(User::class);
        $repository->setResource(
            UserResource::class,
            UserResourceCollection::class
        );

        $resources = $repository->allResources();

        $this->assertInstanceOf(UserResourceCollection::class, $resources);
    }
}
