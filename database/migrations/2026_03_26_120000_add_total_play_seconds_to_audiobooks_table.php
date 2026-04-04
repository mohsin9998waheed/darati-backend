<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audiobooks', function (Blueprint $table) {
            $table->unsignedBigInteger('total_play_seconds')->default(0)->after('total_listens');
        });
    }

    public function down(): void
    {
        Schema::table('audiobooks', function (Blueprint $table) {
            $table->dropColumn('total_play_seconds');
        });
    }
};
