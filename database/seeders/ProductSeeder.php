<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all categories
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Please run CategorySeeder first.');
            return;
        }
     $products = [
            // Microscopes Category
            [
                'name' => 'Microscope Optique Binoculaire',
                'category' => 'Microscopes',
                'sku' => 'MIC-001',
                'price' => 4500,
                'original_price' => 5200,
                'brand' => 'Zeiss',
                'image' => 'https://images.unsplash.com/photo-1582719508461-905c673ccfd8?w=800&auto=format',
                'description' => 'Microscope optique binoculaire professionnel avec éclairage LED intégré. Idéal pour les laboratoires de recherche et d\'enseignement.',
                'features' => [
                    'Grossissement: 40x-1000x',
                    'Éclairage LED réglable',
                    'Tête binoculaire 360° rotative',
                    'Condenseur Abbe avec diaphragme',
                    'Alimentation secteur et batterie'
                ],
                'attributes' => [
                    'type' => 'Binoculaire',
                    'grossissement' => '40x-1000x',
                    'éclairage' => 'LED',
                    'alimentation' => '110-240V'
                ],
                'stock' => 15,
                'rating' => 4.5,
                'reviews_count' => 12,
                'badge' => 'Nouveau',
                'featured' => true,
            ],
            [
                'name' => 'Microscope Numérique HD',
                'category' => 'Microscopes',
                'sku' => 'MIC-002',
                'price' => 6800,
                'original_price' => 7500,
                'brand' => 'Leica',
                'image' => 'https://images.unsplash.com/photo-1576086213368-97a306c9e7ab?w=800&auto=format',
                'description' => 'Microscope numérique avec caméra HD intégrée pour capture d\'images et vidéos. Parfait pour la documentation et l\'enseignement.',
                'features' => [
                    'Caméra 5MP intégrée',
                    'Grossissement numérique: 40x-1600x',
                    'Sortie HDMI/USB',
                    'Logiciel d\'analyse inclus',
                    'Écran tactile 7"'
                ],
                'attributes' => [
                    'type' => 'Numérique',
                    'caméra' => '5MP',
                    'grossissement' => '40x-1600x',
                    'connectivité' => 'HDMI, USB'
                ],
                'stock' => 8,
                'rating' => 4.8,
                'reviews_count' => 8,
                'badge' => 'Populaire',
                'featured' => true,
            ],
            
            // Centrifugeuses Category
            [
                'name' => 'Centrifugeuse de Laboratoire 16 tubes',
                'category' => 'Centrifugeuses',
                'sku' => 'CEN-001',
                'price' => 8900,
                'original_price' => 9500,
                'brand' => 'Thermo Scientific',
                'image' => 'https://images.unsplash.com/photo-1579154204601-01588f4c2d3b?w=800&auto=format',
                'description' => 'Centrifugeuse de laboratoire haute performance pour tubes de 15ml et 50ml. Idéale pour les applications de routine.',
                'features' => [
                    'Capacité: 16 tubes (15ml) ou 8 tubes (50ml)',
                    'Vitesse maximale: 6000 rpm',
                    'Force centrifuge: 5000 xg',
                    'Minuteur digital',
                    'Système de sécurité avec verrouillage'
                ],
                'attributes' => [
                    'capacité' => '16 tubes',
                    'vitesse_max' => '6000 rpm',
                    'force' => '5000 xg',
                    'type' => 'Polyvalente'
                ],
                'stock' => 6,
                'rating' => 4.6,
                'reviews_count' => 5,
                'badge' => null,
                'featured' => false,
            ],
            [
                'name' => 'Micro-centrifugeuse 24 places',
                'category' => 'Centrifugeuses',
                'sku' => 'CEN-002',
                'price' => 4200,
                'original_price' => null,
                'brand' => 'Eppendorf',
                'image' => 'https://images.unsplash.com/photo-1581093458791-9d15429632d8?w=800&auto=format',
                'description' => 'Micro-centrifugeuse compacte pour tubes de 1.5ml et 2ml. Parfaite pour les laboratoires de biologie moléculaire.',
                'features' => [
                    'Capacité: 24 tubes (1.5/2ml)',
                    'Vitesse maximale: 15000 rpm',
                    'Force centrifuge: 20000 xg',
                    'Design compact',
                    'Faible niveau sonore'
                ],
                'attributes' => [
                    'capacité' => '24 tubes',
                    'vitesse_max' => '15000 rpm',
                    'force' => '20000 xg',
                    'type' => 'Micro-centrifugeuse'
                ],
                'stock' => 12,
                'rating' => 4.7,
                'reviews_count' => 9,
                'badge' => 'Haute vitesse',
                'featured' => true,
            ],
            
            // Balances Category
            [
                'name' => 'Balance Analytique de Précision 0.1mg',
                'category' => 'Balances',
                'sku' => 'BAL-001',
                'price' => 7500,
                'original_price' => 8200,
                'brand' => 'Mettler Toledo',
                'image' => 'https://images.unsplash.com/photo-1581093458791-9d15429632d8?w=800&auto=format',
                'description' => 'Balance analytique de haute précision pour pesées de laboratoire. Idéale pour les applications nécessitant une grande exactitude.',
                'features' => [
                    'Précision: 0.1mg',
                    'Capacité maximale: 220g',
                    'Calibration automatique',
                    'Écran tactile couleur',
                    'Interface USB et RS232'
                ],
                'attributes' => [
                    'précision' => '0.1mg',
                    'capacité' => '220g',
                    'type' => 'Analytique',
                    'calibration' => 'Automatique'
                ],
                'stock' => 7,
                'rating' => 4.9,
                'reviews_count' => 6,
                'badge' => 'Premium',
                'featured' => true,
            ],
            [
                'name' => 'Balance de Laboratoire 2000g',
                'category' => 'Balances',
                'sku' => 'BAL-002',
                'price' => 2300,
                'original_price' => 2600,
                'brand' => 'Sartorius',
                'image' => 'https://images.unsplash.com/photo-1581093458791-9d15429632d8?w=800&auto=format',
                'description' => 'Balance de laboratoire polyvalente pour pesées courantes. Robuste et facile à utiliser.',
                'features' => [
                    'Précision: 0.01g',
                    'Capacité maximale: 2000g',
                    'Plateau inox 180mm',
                    'Fonction comptage de pièces',
                    'Interface RS232'
                ],
                'attributes' => [
                    'précision' => '0.01g',
                    'capacité' => '2000g',
                    'type' => 'Précision',
                    'plateau' => '180mm inox'
                ],
                'stock' => 14,
                'rating' => 4.4,
                'reviews_count' => 11,
                'badge' => null,
                'featured' => false,
            ],
            
            // Étuves Category
            [
                'name' => 'Étuve de Séchage 50L',
                'category' => 'Étuves',
                'sku' => 'ETU-001',
                'price' => 5800,
                'original_price' => 6300,
                'brand' => 'Memmert',
                'image' => 'https://images.unsplash.com/photo-1579154204601-01588f4c2d3b?w=800&auto=format',
                'description' => 'Étuve de séchage et d\'incubation avec circulation d\'air forcée. Idéale pour les applications de routine.',
                'features' => [
                    'Volume: 50 litres',
                    'Plage de température: ambiante +5°C à 250°C',
                    'Circulation d\'air forcée',
                    'Contrôleur numérique',
                    'Fonction de minuterie'
                ],
                'attributes' => [
                    'volume' => '50L',
                    'temp_max' => '250°C',
                    'circulation' => 'Forcée',
                    'type' => 'Séchage'
                ],
                'stock' => 5,
                'rating' => 4.5,
                'reviews_count' => 4,
                'badge' => 'Économique',
                'featured' => false,
            ],
            
            // Réactifs Category
            [
                'name' => 'Kit de Réactifs pour Analyse d\'Eau',
                'category' => 'Réactifs',
                'sku' => 'REA-001',
                'price' => 850,
                'original_price' => null,
                'brand' => 'Merck',
                'image' => 'https://images.unsplash.com/photo-1582719508461-905c673ccfd8?w=800&auto=format',
                'description' => 'Kit complet de réactifs pour analyse de la qualité de l\'eau. Inclus les tests pour pH, chlore, nitrates et plus.',
                'features' => [
                    'Tests pour pH, chlore, nitrates, nitrites',
                    '100 tests par paramètre',
                    'Coffret de rangement inclus',
                    'Guide d\'utilisation détaillé',
                    'Durée de conservation: 2 ans'
                ],
                'attributes' => [
                    'type' => 'Kit analyse',
                    'paramètres' => 'pH, Chlore, Nitrates, Nitrites',
                    'nombre_tests' => '400 tests',
                    'conservation' => '2 ans'
                ],
                'stock' => 25,
                'rating' => 4.3,
                'reviews_count' => 15,
                'badge' => 'Meilleure vente',
                'featured' => true,
            ],
            
            // Verre Category
            [
                'name' => 'Set de Béchers en Verre Borosilicaté',
                'category' => 'Verre',
                'sku' => 'VER-001',
                'price' => 450,
                'original_price' => 550,
                'brand' => 'Duran',
                'image' => 'https://images.unsplash.com/photo-1576086213368-97a306c9e7ab?w=800&auto=format',
                'description' => 'Set de 6 béchers en verre borosilicaté de différentes tailles. Résistant à la chaleur et aux produits chimiques.',
                'features' => [
                    'Matériau: Verre borosilicaté 3.3',
                    'Tailles: 50ml, 100ml, 250ml, 500ml, 1000ml',
                    'Résistance thermique: -50°C à 500°C',
                    'Graduations blanches',
                    'Bec verseur'
                ],
                'attributes' => [
                    'matériau' => 'Borosilicaté',
                    'contenance' => '50-1000ml',
                    'type' => 'Bécher',
                    'résistance' => 'Thermique et chimique'
                ],
                'stock' => 30,
                'rating' => 4.8,
                'reviews_count' => 22,
                'badge' => 'Populaire',
                'featured' => true,
            ],
            [
                'name' => 'Pipettes Graduées en Verre (x10)',
                'category' => 'Verre',
                'sku' => 'VER-002',
                'price' => 320,
                'original_price' => 380,
                'brand' => 'Brand',
                'image' => 'https://images.unsplash.com/photo-1581093458791-9d15429632d8?w=800&auto=format',
                'description' => 'Lot de 10 pipettes graduées en verre de haute qualité. Idéales pour les mesures précises en laboratoire.',
                'features' => [
                    'Matériau: Verre borosilicaté',
                    'Tailles: 1ml, 2ml, 5ml, 10ml',
                    'Graduations précises',
                    'Emballage individuel stérile',
                    'Certificat de calibration inclus'
                ],
                'attributes' => [
                    'matériau' => 'Borosilicaté',
                    'type' => 'Pipette graduée',
                    'stérilité' => 'Stérile',
                    'calibration' => 'Certifiée'
                ],
                'stock' => 45,
                'rating' => 4.6,
                'reviews_count' => 18,
                'badge' => null,
                'featured' => false,
            ],
        ];

        foreach ($products as $productData) {
            $category = $categories->where('name', $productData['category'])->first();
            
            if ($category) {
                Product::create([
                    'name' => $productData['name'],
                    'slug' => Str::slug($productData['name']),
                    'sku' => $productData['sku'],
                    'price' => $productData['price'],
                    'original_price' => $productData['original_price'],
                    'category_id' => $category->id,
                    'brand' => $productData['brand'],
                    'image' => $productData['image'],
                    'description' => $productData['description'],
                    'features' => json_encode($productData['features']),
                    'attributes' => json_encode($productData['attributes']),
                    'stock' => $productData['stock'],
                    'rating' => $productData['rating'],
                    'reviews_count' => $productData['reviews_count'],
                    'badge' => $productData['badge'],
                    'featured' => $productData['featured'],
                ]);
            }
        }

        $this->command->info('Products seeded successfully!');
    }
}