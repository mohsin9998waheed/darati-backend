<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Product change: Book → Episodes (no user-facing chapters).
 *
 * Keeps the chapters table as a hidden one-chapter-per-book wrapper so
 * existing FKs, stream auth, and listen/cycle logic keep working.
 *
 * For each audiobook:
 *  - Ensure exactly one chapter exists (create "Episodes" if none)
 *  - Move every episode onto that chapter with a continuous order
 *  - Delete leftover empty chapters
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $audiobookIds = DB::table('audiobooks')->orderBy('id')->pluck('id');

            foreach ($audiobookIds as $audiobookId) {
                $chapters = DB::table('chapters')
                    ->where('audiobook_id', $audiobookId)
                    ->orderBy('order')
                    ->orderBy('id')
                    ->get();

                if ($chapters->isEmpty()) {
                    DB::table('chapters')->insert([
                        'audiobook_id' => $audiobookId,
                        'title'        => 'Episodes',
                        'description'  => null,
                        'order'        => 1,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                    continue;
                }

                $keeperId = (int) $chapters->first()->id;
                $order = 1;

                foreach ($chapters as $chapter) {
                    $episodes = DB::table('episodes')
                        ->where('chapter_id', $chapter->id)
                        ->orderBy('order')
                        ->orderBy('id')
                        ->get();

                    foreach ($episodes as $episode) {
                        DB::table('episodes')
                            ->where('id', $episode->id)
                            ->update([
                                'chapter_id' => $keeperId,
                                'order'      => $order++,
                                'updated_at' => now(),
                            ]);
                    }
                }

                DB::table('chapters')
                    ->where('id', $keeperId)
                    ->update([
                        'title'       => 'Episodes',
                        'description' => null,
                        'order'       => 1,
                        'updated_at'  => now(),
                    ]);

                $extraIds = $chapters
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id) => $id !== $keeperId)
                    ->values()
                    ->all();

                if ($extraIds !== []) {
                    DB::table('chapters')->whereIn('id', $extraIds)->delete();
                }
            }
        });
    }

    public function down(): void
    {
        // Data flatten is not safely reversible; schema is unchanged.
    }
};
