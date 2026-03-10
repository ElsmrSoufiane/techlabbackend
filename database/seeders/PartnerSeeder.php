<?php
// database/seeders/PartnerSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PartnerSeeder extends Seeder
{
    public function run()
    {
        $partners = [
            ['name' => 'Bioelab', 'image' => 'https://www.teclab.ma/storage/products/partenaires/bioelab.png', 'url' => null, 'order' => 1],
            ['name' => 'Carestainer', 'image' => 'https://www.teclab.ma/storage/products/partenaires/carestainer.png', 'url' => null, 'order' => 2],
            ['name' => 'Succeeder', 'image' => 'https://www.teclab.ma/storage/products/partenaires/succeeder.png', 'url' => null, 'order' => 3],
            ['name' => 'Medconn', 'image' => 'https://www.teclab.ma/storage/products/partenaires/medconn.png', 'url' => null, 'order' => 4],
            ['name' => 'Rapid Lab', 'image' => 'https://www.teclab.ma/storage/products/partenaires/rapid-lab.png', 'url' => null, 'order' => 5],
            ['name' => 'Healgen', 'image' => 'https://www.teclab.ma/storage/products/partenaires/healgen.png', 'url' => null, 'order' => 6],
            ['name' => 'Hycel', 'image' => 'https://www.teclab.ma/storage/products/partenaires/hycel.png', 'url' => null, 'order' => 7],
            ['name' => 'Sigtuple', 'image' => 'https://www.teclab.ma/storage/products/partenaires/sigtuple.png', 'url' => null, 'order' => 8],
            ['name' => 'Labnovation', 'image' => 'https://www.teclab.ma/storage/products/partenaires/labnovation.png', 'url' => null, 'order' => 9],
            ['name' => 'Urit', 'image' => 'https://www.teclab.ma/storage/products/partenaires/urit.png', 'url' => null, 'order' => 10],
            ['name' => 'Eaglenos', 'image' => 'https://www.teclab.ma/storage/products/partenaires/eaglenos.png', 'url' => null, 'order' => 11],
            ['name' => 'HIPRO', 'image' => 'https://www.teclab.ma/storage/products/partenaires/hipro.png', 'url' => null, 'order' => 12],
        ];

        foreach ($partners as $partner) {
            DB::table('partners')->insert($partner);
        }

        $this->command->info('Partners seeded successfully!');
    }
}