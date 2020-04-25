<?php

namespace Mblarsen\LaravelRepository\Tests;

use Orchestra\Testbench\TestCase;
use Mblarsen\LaravelRepository\LaravelRepositoryServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LaravelRepositoryServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
