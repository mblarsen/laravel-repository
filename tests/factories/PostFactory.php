<?php

use Faker\Generator as Faker;
use Mblarsen\LaravelRepository\Tests\Models\Post;
use Mblarsen\LaravelRepository\Tests\Models\PostMeta;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

/* @var \Illuminate\Database\Eloquent\Factory $factory */

$factory->define(Post::class, function (Faker $faker) {
    return [
        'title' => $faker->jobTitle,
        'body' => $faker->paragraph,
    ];
});

$factory->afterCreating(Post::class, function (Post $post, $faker) {
    $post->postMeta()->save(
        factory(PostMeta::class)->make()
    );
});
