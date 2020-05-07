<?php

use Faker\Generator as Faker;
use Mblarsen\LaravelRepository\Tests\Models\Comment;

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

$factory->define(Comment::class, function (Faker $faker) {
    return [
        'body' => $faker->paragraph,
    ];
});
