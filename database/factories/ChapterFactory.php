<?php

namespace Database\Factories;

use App\Models\Audiobook;
use App\Models\Chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition(): array
    {
        return [
            'audiobook_id' => Audiobook::factory(),
            'title'        => 'Chapter ' . fake()->numberBetween(1, 20),
            'description'  => null,
            'order'        => fake()->numberBetween(1, 20),
        ];
    }
}
