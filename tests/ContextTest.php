<?php

namespace Mblarsen\LaravelRepository\Tests;

use Mblarsen\LaravelRepository\ArrayResourceContext;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\RequestResourceContext;
use Mblarsen\LaravelRepository\Tests\Models\User;

class ContextTest extends TestCase
{
    /** @test */
    public function repository_resolves()
    {
        $repository = resolve(Repository::class);
        $this->assertInstanceOf(RequestResourceContext::class, $repository->getContext());

        $context = ArrayResourceContext::create([]);
        $repository->setContext($context);
        $this->assertEquals($context, $repository->getContext());
    }

    /** @test */
    public function passing_array_will_create_array_context()
    {
        $repository = Repository::for(User::class, ['with' => ['posts']]);
        $context = $repository->getContext();
        $this->assertInstanceOf(ArrayResourceContext::class, $context);
        $this->assertEquals(['posts'], $context->with());
    }
}
