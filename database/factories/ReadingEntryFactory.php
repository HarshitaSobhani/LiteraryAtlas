<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\ReadingEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReadingEntry> */
class ReadingEntryFactory extends Factory
{
    protected $model = ReadingEntry::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => 'want_to_read',
            'progress' => 0,
        ];
    }
}
