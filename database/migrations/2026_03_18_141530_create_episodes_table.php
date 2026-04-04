<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->string('title');
            $table->string('audio_path');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedBigInteger('file_size')->default(0)->comment('bytes');
            $table->unsignedSmallInteger('order')->default(1);
            $table->boolean('is_preview')->default(false);
            $table->timestamps();

            $table->index(['chapter_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
