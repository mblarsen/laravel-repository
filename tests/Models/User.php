<?php

namespace Mblarsen\LaravelRepository\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function comments()
    {
        return $this->morphTo(Comment::class, 'commentable');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
