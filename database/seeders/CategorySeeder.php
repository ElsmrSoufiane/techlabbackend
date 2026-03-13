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
            ['name' => 'TUBES DE PRELEVEMENT', 'description' => 'Tubes sous vide et accessoires pour prélèvement sanguin'],
            ['name' => 'AIGUILLES ET ACCESSOIRES', 'description' => 'Aiguilles de prélèvement et accessoires associés'],
            ['name' => 'CONSOMMABLES DE LABORATOIRE', 'description' => 'Consommables pour laboratoire: boîtes de Pétri, flacons, écouvillons, etc.'],
            ['name' => 'REACTIFS DE LABORATOIRE', 'description' => 'Réactifs pour analyses hématologiques, biochimie, etc.'],
            ['name' => 'ANALYSEURS DE LABORATOIRE', 'description' => 'Analyseurs de biochimie, hématologie, coagulation, etc.'],
            ['name' => 'EQUIPEMENTS DE LABORATOIRE', 'description' => 'Équipements et instruments de laboratoire'],
        ];

        foreach ($categories as $categoryData) {
            Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
            ]);
            
            $this->command->info("Created category: {$categoryData['name']}");
        }

        $this->command->info('=====================================');
        $this->command->info('✅ Categories seeded successfully!');
        $this->command->info('=====================================');
    }
}
