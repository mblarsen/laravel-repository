<?php

namespace Mblarsen\LaravelRepository\Tests;

use InvalidArgumentException;
use Mblarsen\LaravelRepository\ArrayResourceContext;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\RequestResourceContext;
use Mblarsen\LaravelRepository\Tests\Models\User;
use Symfony\Component\HttpFoundation\ParameterBag;

class ArrayResourceContextTest extends TestCase
{
    /** @test */
    public function creates_from_array()
    {
        $user = new User;

        $context = ArrayResourceContext::create([
            'filters' => ['name' => 'cra'],
            'sort_by' => 'name',
            'sort_order' => 'desc',
            'user' => $user,
            'with' => ['comments'],
        ]);

        $this->assertEquals(['name' => 'cra'], $context->filters());
        $this->assertEquals(['name', 'desc'], $context->sortBy());
        $this->assertEquals(['comments'], $context->with());
        $this->assertEquals($user, $context->user());
        $this->assertEquals(false, $context->paginate());
    }

    /** @test */
    public function set_context_creates_from_array()
    {
        $repository = Repository::for(User::class);
        $repository->setContext([
            'sort_by' => 'name',
            'sort_order' => 'desc',
        ]);
        $context = $repository->getContext();
        $this->assertInstanceOf(ArrayResourceContext::class, $context);
    }

    /** @test */
    public function merges_context()
    {
        $context = ArrayResourceContext::create([
            'sort_by' => 'name',
            'filters' => ['name' => 'cra'],
        ])->merge(['sort_order' => 'desc', 'filters' => ['foo' => 'bar']]);

        $this->assertEquals(['name', 'desc'], $context->sortBy());
        $this->assertEquals(['name' => 'cra', 'foo' => 'bar'], $context->filters());
    }

    /** @test */
    public function excludes_from_context()
    {
        $context = ArrayResourceContext::create([
            'sort_by' => 'name',
            'sort_order' => 'desc',
        ])->exclude(['sort_order']);

        $this->assertEquals(['name', 'asc'], $context->sortBy());
    }


    /** @test */
    public function switches_to_paginate()
    {
        $context = ArrayResourceContext::create([
            'page' => 3,
        ]);

        $this->assertEquals(3, $context->page());
        $this->assertEquals(15, $context->perPage());
        $this->assertEquals(true, $context->paginate());
    }

    /** @test */
    public function creates_from_request()
    {
        /** @var ParameterBag $query_param_bag */
        $query_param_bag = request()->query;
        $query_param_bag->add([
            'filters' => ['name' => 'cra'],
            'sort_by' => 'name',
            'sort_order' => 'desc',
            'with' => ['comments'],
        ]);

        /** @var RequestResourceContext $context */

        $context = ArrayResourceContext::create(
            resolve(RequestResourceContext::class)->toArray()
        )->exclude(['page']);

        $this->assertEquals(['name' => 'cra'], $context->filters());
        $this->assertEquals(['name', 'desc'], $context->sortBy());
        $this->assertEquals(['comments'], $context->with());
        $this->assertEquals(false, $context->paginate());
    }

    /** @test */
    public function converts_to_array()
    {
        $context = ArrayResourceContext::create([
            'foo' => 'bar',
            'uselss' => 'stuff',
            'filters' => ['name' => 'cra'],
            'page' => 1,
            'per_page' => 15,
            'sort_by' => 'name',
            'sort_order' => 'desc',
            'user' => null,
            'with' => ['comments'],
        ]);

        $expected_array = [
            'filters' => ['name' => 'cra'],
            'page' => 1,
            'paginate' => true,
            'per_page' => 15,
            'sort_by' => 'name',
            'sort_order' => 'desc',
            'user' => null,
            'with' => ['comments'],
        ];

        $this->assertEquals($expected_array, $context->toArray());
    }

    /** @test */
    public function can_set_value_with_path()
    {
        $context = ArrayResourceContext::create([
            'filters' => ['name' => 'cra'],
        ]);

        $filters = $context->set('filters.name', 'foo')->filters();

        $this->assertEquals('foo', $filters['name']);
    }

    /** @test */
    public function can_set_only_valid()
    {
        $context = ArrayResourceContext::create([]);

        $this->expectException(InvalidArgumentException::class);
        $context->set('foo.name', 'foo');
    }
}
