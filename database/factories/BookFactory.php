<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Book> */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => 'draft',
            'working_title' => $this->faker->sentence(3),
        ];
    }
}
