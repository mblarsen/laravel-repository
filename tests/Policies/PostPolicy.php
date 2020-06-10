<?php

namespace Mblarsen\LaravelRepository\Tests\Policies;

class PostPolicy
{
    public function create()
    {
        return true;
    }

    public function viewAny()
    {
        return true;
    }
}
