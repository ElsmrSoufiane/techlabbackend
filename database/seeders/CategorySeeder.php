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
        $this->command->info('Seeding categories...');
        
      
        $categories = [
            [
                'name' => 'TUBES DE PRELEVEMENT',
                'color' => '#6d9eeb',
                'image' => 'https://teclab.ma/storage/labs-icon-22px.png',
            ],
            [
                'name' => 'AIGUILLES ET ACCESSOIRES',
                'color' => '#ff6b6b',
                'image' => 'https://teclab.ma/storage/mixture-med-64dp-1f1f1f-fill0-wght500-grad0-opsz48.png',
            ],
            [
                'name' => 'CONSOMMABLES DE LABORATOIRE',
                'color' => '#4ecdc4',
                'image' => 'https://teclab.ma/storage/science-64dp-1f1f1f-fill0-wght500-grad0-opsz48.png',
            ],
            [
                'name' => 'REACTIFS DE LABORATOIRE',
                'color' => '#45b7d1',
                'image' => 'https://teclab.ma/storage/microbiology-64dp-1f1f1f-fill0-wght500-grad0-opsz48.png',
            ],
            [
                'name' => 'ANALYSEURS DE LABORATOIRE',
                'color' => '#96ceb4',
                'image' => 'https://teclab.ma/storage/multicooker-64dp-1f1f1f-fill0-wght500-grad0-opsz48.png',
            ],
            [
                'name' => 'EQUIPEMENTS DE LABORATOIRE',
                'color' => '#ffeead',
                'image' => 'https://teclab.ma/storage/biotech-64dp-1f1f1f-fill0-wght500-grad0-opsz48.png',
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'color' => $categoryData['color'],
                'product_count' => 0, // Will be updated automatically by the system
                'image' => $categoryData['image'],
            ]);
            
            $this->command->info("Created category: {$categoryData['name']}");
        }

        // Display summary
        $count = Category::count();
        $this->command->info('=====================================');
        $this->command->info("✅ {$count} categories seeded successfully!");
        $this->command->info('=====================================');
        
        // List all categories
        $this->command->info('📋 Categories created:');
        foreach (Category::all() as $category) {
            $this->command->info("   - {$category->name} (ID: {$category->id})");
        }
        $this->command->info('=====================================');
    }
}
