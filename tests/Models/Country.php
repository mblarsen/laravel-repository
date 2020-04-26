<?php

namespace Mblarsen\LaravelRepository\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $guarded = [];

    public function posts()
    {
        return $this->hasManyThrough(Post::class, User::class);
    }
}
