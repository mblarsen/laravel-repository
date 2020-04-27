<?php

namespace Mblarsen\LaravelRepository\Tests;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Mblarsen\LaravelRepository\ArrayResourceContext;
use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\RequestResourceContext;
use Mblarsen\LaravelRepository\ResourceContext;
use Mblarsen\LaravelRepository\Tests\Models\Post;
use Mblarsen\LaravelRepository\Tests\Models\User;
use ReflectionClass;

class ModelTest extends TestCase
{
    /** @test */
    public function create_for_model()
    {
        $repository = Repository::for(User::class);
        $this->assertEquals(User::class, $this->getProperty($repository, 'model'));
    }

    /** @test */
    public function query_is_for_model()
    {
        $repository = Repository::for(User::class);
        /** @var Builder */
        $query = $this->invokeMethod($repository, 'modelQuery');
        $model = $query->getModel();

        $this->assertEquals(User::class, get_class($model));
    }

    /** @test */
    public function set_context_for_model()
    {
        $context = ArrayResourceContext::create();
        $repository = Repository::for(User::class, $context);

        $this->assertEquals($context, $repository->getContext());
    }

    /** @test */
    public function repository_resolves()
    {
        $this->assertInstanceOf(RequestResourceContext::class, resolve(ResourceContext::class));
    }

    /** @test */
    public function throw_if_models_does_not_match()
    {
        $repository = Repository::for(User::class);
        $this->expectException(InvalidArgumentException::class);
        $repository->all(Post::query());
    }

    protected function getProperty(&$object, $property)
    {
        $reflection = new ReflectionClass(get_class($object));
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    protected function invokeMethod(&$object, $method_name, array $parameters = array())
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method_name);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
