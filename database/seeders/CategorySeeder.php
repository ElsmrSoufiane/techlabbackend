<?php
// database/seeders/CategorySeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'TUBES DE PRELEVEMENT',
                'slug' => 'tubes-de-prelevement',
                'color' => '#6d9eeb',
                'image' => 'https://teclab.ma/storage/product-categories/chatgpt-image-27-fevr-2026-13-13-12-4.jpg',
               
            ],
            [
                'name' => 'AIGUILLES ET ACCESSOIRES',
                'slug' => 'aiguilles-et-accessoires',
                'color' => '#ff6b6b',
                'image' => 'https://teclab.ma/storage/product-categories/chatgpt-image-27-fevr-2026-13-13-12.jpg',
               
            ],
            [
                'name' => 'AIGUILLES DE PRELEVEMENT',
                'slug' => 'aiguilles-de-prelevement',
                'color' => '#ff8a8a',
                'image' => null
                 // Will need to adjust after first insert
            ],
            [
                'name' => 'ACCESSOIRES POUR AIGUILLES',
                'slug' => 'accessoires-pour-aiguilles',
                'color' => '#ffa5a5',
                'image' => null
               
            ],
            [
                'name' => 'CONSOMMABLES DE LABORATOIRE',
                'slug' => 'consommables-de-laboratoire',
                'color' => '#4ecdc4',
                'image' => 'https://teclab.ma/storage/product-categories/chatgpt-image-27-fevr-2026-13-13-12-1.jpg',
               
            ],
            [
                'name' => 'LES BOITES DE PETRIES',
                'slug' => 'les-boites-de-petries',
                'color' => '#5edcd3',
                'image' => null
               
            ],
            [
                'name' => 'LES FLACONS',
                'slug' => 'les-flacons',
                'color' => '#6eebe2',
                'image' => null
                
            ],
            [
                'name' => 'LES ECOUVILLONS',
                'slug' => 'les-ecouvillons',
                'color' => '#7efcf3',
                'image' => null
                 
            ],
            [
                'name' => 'LES EMBOUTS',
                'slug' => 'les-embouts',
                'color' => '#8efff4',
                'image' => null
                
            ],
            [
                'name' => 'LES GANTS',
                'slug' => 'les-gants',
                'color' => '#9efff5',
                'image' => null
                
            ],
            [
                'name' => 'LES PIPETTES',
                'slug' => 'les-pipettes',
                'color' => '#aefff6',
                'image' => null
             
            ],
            [
                'name' => 'LES SERINGUES',
                'slug' => 'les-seringues',
                'color' => '#befff7',
                'image' => null
            ],
            [
                'name' => 'LES LAMES & LAMELLES',
                'slug' => 'les-lames-lamelles',
                'color' => '#cefff8',
                'image' => null
            
            ],
            [
                'name' => 'LES PLAQUES & MICROPLAQUES',
                'slug' => 'les-plaques-microplaques',
                'color' => '#defff9',
                'image' => null
              
            ],
            [
                'name' => 'LES CELLULES DE NUMERATION',
                'slug' => 'les-cellules-de-numeration',
                'color' => '#eefffa',
                'image' => null
                
            ],
            [
                'name' => 'LES ANSES/ INOCULATEURS/ MANCHES',
                'slug' => 'les-anses-inoculateurs-manches',
                'color' => '#fdfffb',
                'image' => null
            
            ],
            [
                'name' => 'LES VERRERIES',
                'slug' => 'les-verreries',
                'color' => '#c0c0c0',
                'image' => null
        
            ],
            [
                'name' => 'CUVETTES/CUPULES/FLACONS DE BILLES',
                'slug' => 'cuvettes-cupules-flacons-de-billes',
                'color' => '#d0d0d0',
                'image' => null
            
            ],
            [
                'name' => 'MICROPIPETTES',
                'slug' => 'micropipettes',
                'color' => '#e0e0e0',
                'image' => null
                 
            ],
            [
                'name' => 'REACTIFS DE LABORATOIRE',
                'slug' => 'reactifs-de-laboratoire',
                'color' => '#45b7d1',
                'image' => 'https://teclab.ma/storage/product-categories/chatgpt-image-27-fevr-2026-13-13-12-2.jpg',
               
            ],
            [
                'name' => 'REACTIFS HEMATOLOGIES SYSMEX',
                'slug' => 'reactifs-hematologies-sysmex',
                'color' => '#55c7e1',
                'image' => null
                 
            ],
            [
                'name' => 'SYSMEX XT/XS',
                'slug' => 'sysmex-xt-xs',
                'color' => '#65d7f1',
                'image' => null
                 
            ],
            [
                'name' => 'SYSMEX KX',
                'slug' => 'sysmex-kx',
                'color' => '#75e7ff',
                'image' => null
                 
            ],
            [
                'name' => 'SYSMEX XN',
                'slug' => 'sysmex-xn',
                'color' => '#85f7ff',
                'image' => null
                 
            ],
            [
                'name' => 'REACTIFS HEMATOLOGIES MINDRAY',
                'slug' => 'reactifs-hematologies-mindray',
                'color' => '#35a7c1',
                'image' => null
                 
            ],
            [
                'name' => 'MINDRAY BC-6800',
                'slug' => 'mindray-bc-6800',
                'color' => '#2597b1',
                'image' => null
                 
            ],
            [
                'name' => 'MINDRAY BC-5800',
                'slug' => 'mindray-bc-5800',
                'color' => '#1587a1',
                'image' => null
                 
            ],
            [
                'name' => 'MINDRAY BC-5390',
                'slug' => 'mindray-bc-5390',
                'color' => '#057791',
                'image' => null
                 
            ],
            [
                'name' => 'SYPHILIS',
                'slug' => 'syphilis',
                'color' => '#b537d1',
                'image' => null
                 
            ],
            [
                'name' => 'REACTIFS LATEX',
                'slug' => 'reactifs-latex',
                'color' => '#c547e1',
                'image' => null
                 
            ],
            [
                'name' => 'GROUPAGES SANGUINS',
                'slug' => 'groupages-sanguins',
                'color' => '#d557f1',
                'image' => null
                 
            ],
            [
                'name' => 'BANDELETTES URINAIRES',
                'slug' => 'bandelettes-urinaires',
                'color' => '#e567ff',
                'image' => null
                 
            ],
            [
                'name' => 'TESTS RAPIDES',
                'slug' => 'tests-rapides',
                'color' => '#f577ff',
                'image' => null
                 
            ],
            [
                'name' => 'COLORANTS',
                'slug' => 'colorants',
                'color' => '#ff87ff',
                'image' => null
                 
            ],
            [
                'name' => 'DISQUES D\'ANTIBIOTIQUES',
                'slug' => 'disques-dantibiotiques',
                'color' => '#ff97ff',
                'image' => null
                 
            ],
            [
                'name' => 'MILIEUX DE CULTURE',
                'slug' => 'milieux-de-culture',
                'color' => '#ffa7ff',
                'image' => null
                 
            ],
            [
                'name' => 'TUBERCULOSE',
                'slug' => 'tuberculose',
                'color' => '#ffb7ff',
                'image' => null
                 
            ],
            [
                'name' => 'REACTIFS DE COAGULATION',
                'slug' => 'reactifs-de-coagulation',
                'color' => '#ffc7ff',
                'image' => null
                 
            ],
            [
                'name' => 'REACTIFS DE BIOCHIMIE',
                'slug' => 'reactifs-de-biochimie',
                'color' => '#ffd7ff',
                'image' => null
                 
            ],
            [
                'name' => 'ANALYSEURS',
                'slug' => 'analyseurs',
                'color' => '#f9ca24',
                'image' => 'https://teclab.ma/storage/product-categories/chatgpt-image-27-fevr-2026-13-13-12-3.jpg',
               
            ],
            [
                'name' => 'ANALYSEURS DE BIOCHIMIE',
                'slug' => 'analyseurs-de-biochimie',
                'color' => '#ffda34',
                'image' => null
                 
            ],
            [
                'name' => 'ANALYSEURS D\'HEMOGLOBINE GLYCQUEE',
                'slug' => 'analyseurs-dhemoglobine-glycquee',
                'color' => '#ffea44',
                'image' => null
                 
            ],
            [
                'name' => 'ANALYSEURS DE COAGULATION',
                'slug' => 'analyseurs-de-coagulation',
                'color' => '#fffa54',
                'image' => null
                 
            ],
            [
                'name' => 'ANALYSEURS D\'HEMATOLOGIE',
                'slug' => 'analyseurs-dhematologie',
                'color' => '#ffff64',
                'image' => null
                 
            ],
            [
                'name' => 'ANALYSEUR POUR VITESSE DE SÉDIMENTATION (ESR / VS)',
                'slug' => 'analyseur-vitesse-sedimentation',
                'color' => '#ffff74',
                'image' => null
                 
            ],
            [
                'name' => 'ANALYSEURS DE PROTEINES SPECIFIQUES',
                'slug' => 'analyseurs-de-proteines-specifiques',
                'color' => '#ffff84',
                'image' => null
                 
            ],
            [
                'name' => 'ANALYSEURS D\'ELECTROLYTES',
                'slug' => 'analyseurs-delectrolytes',
                'color' => '#ffff94',
                'image' => null
                 
            ],
            [
                'name' => 'ANALYSEURS DE GAZ DU SANG',
                'slug' => 'analyseurs-de-gaz-du-sang',
                'color' => '#ffffa4',
                'image' => null
                 
            ],
            [
                'name' => 'EQUIPEMENTS DE LABORATOIRE',
                'slug' => 'equipements-de-laboratoire',
                'color' => '#a55eea',
                'image' => 'https://teclab.ma/storage/product-categories/bunsen-burner-photoroom.png',
               
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Categories seeded successfully!');
    }
}