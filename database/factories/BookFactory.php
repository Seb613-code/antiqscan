<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => auth()->id() ?? User::factory(),
            'status' => 'draft',
            'working_title' => $this->faker->sentence(3),
        ];
    }
}
