<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audiobook_id')->constrained('audiobooks')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('order')->default(1);
            $table->timestamps();

            $table->index(['audiobook_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
