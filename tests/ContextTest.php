<?php

namespace Mblarsen\LaravelRepository\Tests;

use Mblarsen\LaravelRepository\ArrayResourceContext;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\RequestResourceContext;

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
}
