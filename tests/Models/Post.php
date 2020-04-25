<?php

namespace Mblarsen\LaravelRepository\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = [];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function postMeta()
    {
        return $this->hasOne(PostMeta::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
