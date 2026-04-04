<?php

namespace Database\Seeders;

use App\Models\Audiobook;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');

        $artist1 = User::updateOrCreate(
            ['email' => 'artist1@darati.com'],
            [
                'name'      => 'Sarah Author',
                'password'  => Hash::make('password'),
                'role'      => 'artist',
                'is_active' => true,
                'bio'       => 'Bestselling author and narrator.',
            ]
        );

        $artist2 = User::updateOrCreate(
            ['email' => 'artist2@darati.com'],
            [
                'name'      => 'John Narrator',
                'password'  => Hash::make('password'),
                'role'      => 'artist',
                'is_active' => true,
                'bio'       => 'Professional voice actor.',
            ]
        );

        $book1 = Audiobook::updateOrCreate(
            ['title' => 'The Silent Hour', 'artist_id' => $artist1->id],
            [
                'description' => 'A gripping thriller that keeps you on the edge.',
                'status'      => 'approved',
                'category_id' => $categories['Mystery & Thriller'] ?? null,
                'language'    => 'en',
            ]
        );

        Chapter::updateOrCreate(
            ['audiobook_id' => $book1->id, 'order' => 1],
            ['title' => 'Chapter 1: The Beginning']
        );

        Audiobook::updateOrCreate(
            ['title' => 'Mindful Living', 'artist_id' => $artist2->id],
            [
                'description' => 'A guide to living with intention and purpose.',
                'status'      => 'pending',
                'category_id' => $categories['Self-Development'] ?? null,
                'language'    => 'en',
            ]
        );
    }
}
