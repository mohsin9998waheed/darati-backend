<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // One boolean column per notification type.
            // Defaults to true (opted-in) — users must explicitly disable.
            $table->boolean('episode_ready')   ->default(true);  // my episode finished processing
            $table->boolean('new_episode')     ->default(true);  // new episode on a followed book
            $table->boolean('resume_reminder') ->default(true);  // re-engagement after pause

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
