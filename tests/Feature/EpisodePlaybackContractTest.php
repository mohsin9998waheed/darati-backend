<?php

namespace Tests\Feature;

use App\Models\Audiobook;
use App\Models\Chapter;
use App\Models\Episode;
use App\Models\User;
use App\Services\S3Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * EpisodePlaybackContractTest
 *
 * Verifies the API contract for episode playability:
 *
 *  - /episodes/{id}/signed-audio  →  200 when ready, 409 when not, 401 when unauthed
 *  - /audiobooks/{id}             →  episodes carry is_playable + playback_block_reason
 *  - /stream/{id}                 →  409 when not ready (stream endpoint)
 *
 * These tests use an in-memory SQLite DB (phpunit.xml) and mock S3Service
 * to avoid any real network calls.
 */
class EpisodePlaybackContractTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function actingAsListener(): User
    {
        $user = User::factory()->create(['role' => 'listener']);
        return $user;
    }

    /**
     * Build a fully wired episode: approved book → chapter → episode.
     * The $episodeState factory state (e.g. 'ready', 'queued', 'failed') controls playability.
     */
    private function makeEpisode(string $state = 'ready'): Episode
    {
        $book    = Audiobook::factory()->approved()->create();
        $chapter = Chapter::factory()->create(['audiobook_id' => $book->id]);
        return Episode::factory()->{$state}()->create(['chapter_id' => $chapter->id]);
    }

    /** Mock S3Service so tests never touch real AWS. */
    private function mockS3(bool $exists = true, string $signedUrl = 'https://s3.example.com/test.mp3'): void
    {
        $mock = Mockery::mock(S3Service::class);
        $mock->shouldReceive('exists')->andReturn($exists);
        $mock->shouldReceive('temporaryUrl')->andReturn($signedUrl);
        $this->app->instance(S3Service::class, $mock);
    }

    // ── signed-audio: authentication ─────────────────────────────────────────

    public function test_signed_audio_requires_authentication(): void
    {
        $episode = $this->makeEpisode('ready');

        $this->getJson("/api/episodes/{$episode->id}/signed-audio")
            ->assertStatus(401);
    }

    // ── signed-audio: ready episode ──────────────────────────────────────────

    public function test_signed_audio_returns_200_and_play_url_for_ready_episode(): void
    {
        $this->mockS3(exists: true, signedUrl: 'https://s3.example.com/episode.mp3');
        $user    = $this->actingAsListener();
        $episode = $this->makeEpisode('ready');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/episodes/{$episode->id}/signed-audio");

        $response->assertStatus(200)
            ->assertJsonStructure(['play_url', 'correlation_id'])
            ->assertJsonPath('play_url', 'https://s3.example.com/episode.mp3');

        // Correlation ID must be present in the response header too
        $this->assertNotEmpty($response->headers->get('X-Correlation-Id'));
    }

    // ── signed-audio: queued episode ─────────────────────────────────────────

    public function test_signed_audio_returns_409_for_queued_episode(): void
    {
        $user    = $this->actingAsListener();
        $episode = $this->makeEpisode('queued');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/episodes/{$episode->id}/signed-audio");

        $response->assertStatus(409)
            ->assertJsonPath('error', 'episode_not_playable')
            ->assertJsonPath('block_reason', 'processing')
            ->assertJsonPath('retry_after_seconds', 30);

        $this->assertNotEmpty($response->headers->get('X-Correlation-Id'));
        $this->assertEquals('30', $response->headers->get('Retry-After'));
    }

    // ── signed-audio: processing episode ─────────────────────────────────────

    public function test_signed_audio_returns_409_for_processing_episode(): void
    {
        $user    = $this->actingAsListener();
        $episode = $this->makeEpisode('processing');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/episodes/{$episode->id}/signed-audio");

        $response->assertStatus(409)
            ->assertJsonPath('error', 'episode_not_playable')
            ->assertJsonPath('block_reason', 'processing')
            ->assertJsonPath('retry_after_seconds', 30);
    }

    // ── signed-audio: failed episode ─────────────────────────────────────────

    public function test_signed_audio_returns_409_for_failed_episode(): void
    {
        $user    = $this->actingAsListener();
        $episode = $this->makeEpisode('failed');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/episodes/{$episode->id}/signed-audio");

        $response->assertStatus(409)
            ->assertJsonPath('error', 'episode_not_playable')
            ->assertJsonPath('block_reason', 'processing_failed')
            ->assertJsonPath('retry_after_seconds', null);
    }

    // ── signed-audio: audio_missing episode ──────────────────────────────────

    public function test_signed_audio_returns_409_for_audio_missing_episode(): void
    {
        $user    = $this->actingAsListener();
        $episode = $this->makeEpisode('audioMissing');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/episodes/{$episode->id}/signed-audio");

        $response->assertStatus(409)
            ->assertJsonPath('error', 'episode_not_playable')
            ->assertJsonPath('block_reason', 'audio_missing');
    }

    // ── signed-audio: S3 file gone despite ready status ──────────────────────

    public function test_signed_audio_returns_409_when_s3_file_missing_despite_ready_status(): void
    {
        $this->mockS3(exists: false);
        $user    = $this->actingAsListener();
        $episode = $this->makeEpisode('ready');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/episodes/{$episode->id}/signed-audio");

        $response->assertStatus(409)
            ->assertJsonPath('block_reason', 'audio_missing');
    }

    // ── signed-audio: unapproved book ────────────────────────────────────────

    public function test_signed_audio_returns_404_for_unapproved_audiobook(): void
    {
        $this->mockS3(exists: true);
        $user    = $this->actingAsListener();
        $book    = Audiobook::factory()->pending()->create();
        $chapter = Chapter::factory()->create(['audiobook_id' => $book->id]);
        $episode = Episode::factory()->ready()->create(['chapter_id' => $chapter->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/episodes/{$episode->id}/signed-audio")
            ->assertStatus(404);
    }

    // ── stream endpoint: not ready ────────────────────────────────────────────

    public function test_stream_returns_409_for_queued_episode(): void
    {
        $user    = $this->actingAsListener();
        $episode = $this->makeEpisode('queued');

        $this->actingAs($user, 'sanctum')
            ->get("/api/stream/{$episode->id}")
            ->assertStatus(409)
            ->assertJsonPath('block_reason', 'processing');
    }

    public function test_stream_returns_409_for_failed_episode(): void
    {
        $user    = $this->actingAsListener();
        $episode = $this->makeEpisode('failed');

        $this->actingAs($user, 'sanctum')
            ->get("/api/stream/{$episode->id}")
            ->assertStatus(409)
            ->assertJsonPath('block_reason', 'processing_failed');
    }

    // ── Audiobook API: episode payload contract ───────────────────────────────

    public function test_audiobook_show_includes_is_playable_and_block_reason_for_ready_episode(): void
    {
        $book    = Audiobook::factory()->approved()->create();
        $chapter = Chapter::factory()->create(['audiobook_id' => $book->id]);
        Episode::factory()->ready()->create(['chapter_id' => $chapter->id]);

        $response = $this->getJson("/api/audiobooks/{$book->id}");

        $response->assertStatus(200);
        $episode = $response->json('data.chapters.0.episodes.0');
        $flat    = $response->json('data.episodes.0');

        $this->assertTrue($episode['is_playable']);
        $this->assertNull($episode['playback_block_reason']);
        $this->assertNotNull($episode['stream_url']);
        $this->assertTrue($flat['is_playable']);
        $this->assertEquals($episode['id'], $flat['id']);
    }

    public function test_audiobook_show_marks_queued_episode_as_not_playable(): void
    {
        $book    = Audiobook::factory()->approved()->create();
        $chapter = Chapter::factory()->create(['audiobook_id' => $book->id]);
        Episode::factory()->queued()->create(['chapter_id' => $chapter->id]);

        $response = $this->getJson("/api/audiobooks/{$book->id}");

        $response->assertStatus(200);
        $episode = $response->json('data.chapters.0.episodes.0');

        $this->assertFalse($episode['is_playable']);
        $this->assertEquals('processing', $episode['playback_block_reason']);
        $this->assertNull($episode['stream_url']);
    }

    public function test_audiobook_show_marks_failed_episode_as_not_playable(): void
    {
        $book    = Audiobook::factory()->approved()->create();
        $chapter = Chapter::factory()->create(['audiobook_id' => $book->id]);
        Episode::factory()->failed()->create(['chapter_id' => $chapter->id]);

        $response = $this->getJson("/api/audiobooks/{$book->id}");

        $response->assertStatus(200);
        $episode = $response->json('data.chapters.0.episodes.0');

        $this->assertFalse($episode['is_playable']);
        $this->assertEquals('processing_failed', $episode['playback_block_reason']);
        $this->assertNull($episode['stream_url']);
    }

    public function test_audiobook_show_episode_payload_contains_all_required_contract_fields(): void
    {
        $book    = Audiobook::factory()->approved()->create();
        $chapter = Chapter::factory()->create(['audiobook_id' => $book->id]);
        Episode::factory()->ready()->create(['chapter_id' => $chapter->id]);

        $response = $this->getJson("/api/audiobooks/{$book->id}");
        $episode  = $response->json('data.chapters.0.episodes.0');

        foreach (['id', 'title', 'duration_seconds', 'is_preview', 'order',
                  'processing_status', 'is_playable', 'playback_block_reason', 'stream_url'] as $field) {
            $this->assertArrayHasKey($field, $episode, "Missing field: {$field}");
        }
    }

    // ── Episode model unit-level checks ──────────────────────────────────────

    public function test_episode_playback_block_reason_returns_null_when_ready(): void
    {
        $ep = Episode::factory()->ready()->make();
        $this->assertNull($ep->playbackBlockReason());
        $this->assertTrue($ep->isPlayable());
    }

    public function test_episode_playback_block_reason_returns_processing_when_queued(): void
    {
        $ep = Episode::factory()->queued()->make();
        $this->assertEquals('processing', $ep->playbackBlockReason());
        $this->assertFalse($ep->isPlayable());
    }

    public function test_episode_playback_block_reason_returns_processing_failed(): void
    {
        $ep = Episode::factory()->failed()->make();
        $this->assertEquals('processing_failed', $ep->playbackBlockReason());
        $this->assertFalse($ep->isPlayable());
    }

    public function test_episode_playback_block_reason_returns_audio_missing_when_path_null(): void
    {
        $ep = Episode::factory()->audioMissing()->make();
        $this->assertEquals('audio_missing', $ep->playbackBlockReason());
        $this->assertFalse($ep->isPlayable());
    }
}
