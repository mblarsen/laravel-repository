<?php

use Faker\Generator as Faker;
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

$factory->define(PostMeta::class, function (Faker $faker) {
    return [
        'version' => $faker->numberBetween(1, 42),
        'code' => $faker->word
    ];
});
