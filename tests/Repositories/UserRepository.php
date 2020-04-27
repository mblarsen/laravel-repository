<?php

namespace Mblarsen\LaravelRepository\Tests\Repositories;

use Mblarsen\LaravelRepository\Repository;
use Mblarsen\LaravelRepository\Tests\Models\User;
use Mblarsen\LaravelRepository\Tests\Resources\UserResource;

class UserRepository extends Repository
{
    protected $model = User::class;

    protected $resource = UserResource::class;

    protected $default_sort_by = 'first_name';

    protected function register()
    {
        $this->default_list_column = function ($model) {
            return $model->full_name;
        };
    }
}
