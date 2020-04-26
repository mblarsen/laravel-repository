<?php

namespace Mblarsen\LaravelRepository\Tests;

use Mblarsen\LaravelRepository\RequestResourceContext;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestResourceContextTest extends TestCase
{
    /** @test */
    public function values_extracted_from_request()
    {
        /** @var ParameterBag $query_param_bag */
        $query_param_bag = request()->query;
        $query_param_bag->add([
            'filters' => ['name' => 'cra'],
            'sort_by' => 'name',
            'sort_order' => 'desc',
            'with' => ['comments'],
            'page' => 3,
            'per_page' => 10,
        ]);

        /** @var RequestResourceContext $context */
        $context = resolve(RequestResourceContext::class);

        $this->assertEquals(['name' => 'cra'], $context->filters());
        $this->assertEquals(['name', 'desc'], $context->sortBy());
        $this->assertEquals(['comments'], $context->with());
        $this->assertEquals(true, $context->paginate());
        $this->assertEquals(3, $context->page());
        $this->assertEquals(10, $context->perPage());
    }

    /** @test */
    public function converts_to_array()
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
        $context = resolve(RequestResourceContext::class);
        $context_array = $context->toArray();
        $expected_array = [
            'filters' => ['name' => 'cra'],
            'page' => 1,
            'paginate' => false,
            'per_page' => 15,
            'sort_by' => 'name',
            'sort_order' => 'desc',
            'user' => null,
            'with' => ['comments'],
        ];

        $this->assertEquals($expected_array, $context_array);
    }
}
