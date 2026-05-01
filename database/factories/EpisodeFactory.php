<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Episode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Episode>
 */
class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition(): array
    {
        return [
            'chapter_id'        => Chapter::factory(),
            'title'             => 'Episode ' . fake()->numberBetween(1, 50),
            'audio_path'        => 'episodes/' . fake()->uuid() . '.mp3',
            'raw_audio_path'    => null,
            'duration_seconds'  => fake()->numberBetween(60, 3600),
            'file_size'         => fake()->numberBetween(500_000, 50_000_000),
            'order'             => fake()->numberBetween(1, 50),
            'is_preview'        => false,
            'processing_status' => 'ready',
        ];
    }

    /** Episode is fully ready and playable (default). */
    public function ready(): static
    {
        return $this->state([
            'processing_status' => 'ready',
            'audio_path'        => 'episodes/' . fake()->uuid() . '.mp3',
        ]);
    }

    /** Episode is queued for processing — not yet playable. */
    public function queued(): static
    {
        return $this->state([
            'processing_status' => 'queued',
            'audio_path'        => 'episodes/pending-' . fake()->uuid() . '.mp3',
        ]);
    }

    /** Episode is actively being transcoded — not yet playable. */
    public function processing(): static
    {
        return $this->state([
            'processing_status' => 'processing',
            'audio_path'        => 'episodes/pending-' . fake()->uuid() . '.mp3',
        ]);
    }

    /** Transcode job failed — not playable. */
    public function failed(): static
    {
        return $this->state([
            'processing_status' => 'failed',
            'audio_path'        => 'episodes/failed-' . fake()->uuid() . '.mp3',
        ]);
    }

    /** Status is ready but audio_path is empty — storage anomaly. */
    public function audioMissing(): static
    {
        return $this->state([
            'processing_status' => 'ready',
            'audio_path'        => '',
        ]);
    }
}
