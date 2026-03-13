<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data first (optional)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $categories = [
            ['name' => 'Microscopes'],
            ['name' => 'Centrifugeuses'],
            ['name' => 'Balances'],
            ['name' => 'Étuves'],
            ['name' => 'Instrumentation'],
            ['name' => 'Réactifs'],
            ['name' => 'Verre'],
            ['name' => 'Consommables'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                // 'description' is removed
            ]);
        }

        $this->command->info('Categories seeded successfully!');
    }
}