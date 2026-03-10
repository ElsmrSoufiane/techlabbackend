<?php

namespace Database\Seeders;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
$this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            PartnerSeeder::class,
        ]);


        $admin = Customer::create([
            'name' => 'Admin TECLAB',
            'email' => 'admin@teclab.ma',
            'password' => Hash::make('admin123'),
            'phone' => '+212 600-000000',
            'address' => 'Rue 7 N° 184/Q4, Fès, Maroc',
            'role' => 'admin',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✅ Admin account created: admin@teclab.ma / admin123');
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
