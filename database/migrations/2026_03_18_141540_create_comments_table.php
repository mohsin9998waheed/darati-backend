<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('audiobook_id')->constrained('audiobooks')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();

            $table->index(['audiobook_id', 'created_at']);
            $table->index('is_flagged');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
