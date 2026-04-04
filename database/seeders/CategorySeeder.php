<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Fiction',            'icon' => '📖'],
            ['name' => 'Non-Fiction',        'icon' => '📚'],
            ['name' => 'Science & Tech',     'icon' => '🔬'],
            ['name' => 'History',            'icon' => '🏛️'],
            ['name' => 'Biography',          'icon' => '👤'],
            ['name' => 'Self-Development',   'icon' => '🌱'],
            ['name' => 'Mystery & Thriller', 'icon' => '🔍'],
            ['name' => 'Romance',            'icon' => '❤️'],
            ['name' => 'Philosophy',         'icon' => '💭'],
            ['name' => 'Children',           'icon' => '🧒'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['name' => $cat['name']], [...$cat, 'is_active' => true]);
        }
    }
}
