<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path')->nullable();
            $table->enum('link_type', ['book', 'external', 'static'])->default('static');
            $table->string('link_value')->nullable(); // audiobook_id or URL
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
