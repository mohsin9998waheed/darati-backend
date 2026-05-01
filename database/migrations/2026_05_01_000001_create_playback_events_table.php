<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playback_events', function (Blueprint $table) {
            $table->id();

            // Who triggered the event
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // What happened
            $table->string('event', 64);          // playback_requested, audio_source_loaded, etc.
            $table->string('episode_id', 32)->nullable();  // kept as string for flexibility
            $table->string('audiobook_id', 32)->nullable();

            // Performance metrics
            $table->unsignedInteger('elapsed_ms')->nullable(); // TTFA, URL-fetch latency, etc.

            // Device context
            $table->string('device_os', 16)->default('unknown');       // android / ios / unknown
            $table->string('device_os_version', 32)->default('unknown');
            $table->string('device_model', 64)->default('unknown');
            $table->string('network_type', 16)->default('unknown');    // wifi / mobile / none / ethernet

            // Extra: event-specific payload stored as JSON
            // (url_type, error_type, error_message, resume_pos_seconds, etc.)
            $table->json('meta')->nullable();

            // When it happened (UTC)
            $table->string('ts', 32)->nullable();  // ISO-8601 from device clock
            $table->timestamps();                  // created_at = server receipt time
        });

        // ── Indexes for SLO queries ───────────────────────────────────────────
        // 1. SLO dashboard: filter by event name + time window
        Schema::table('playback_events', function (Blueprint $table) {
            $table->index(['event', 'created_at'],        'pe_event_time');
            // 2. Segment by OS
            $table->index(['device_os', 'event'],         'pe_os_event');
            // 3. Segment by network type
            $table->index(['network_type', 'event'],      'pe_net_event');
            // 4. Episode-level analytics
            $table->index(['episode_id', 'event'],        'pe_ep_event');
            // 5. User history
            $table->index(['user_id', 'created_at'],      'pe_user_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playback_events');
    }
};
