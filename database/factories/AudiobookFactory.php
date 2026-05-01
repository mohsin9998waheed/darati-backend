<?php

namespace Database\Factories;

use App\Models\Audiobook;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audiobook>
 */
class AudiobookFactory extends Factory
{
    protected $model = Audiobook::class;

    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'language'    => 'en',
            'status'      => 'approved',
            'thumbnail'   => null,
            'author_name' => fake()->name(),
            'avg_rating'  => 0.0,
            'total_listens'      => 0,
            'total_play_seconds' => 0,
            'is_trending' => false,
            'artist_id'   => User::factory(),
        ];
    }

    /** Set status to 'pending' */
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    /** Set status to 'approved' (default) */
    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }
}
