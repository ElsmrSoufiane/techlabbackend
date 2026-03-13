<?php

namespace Database\Seeders;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use CustmerSeeder;
use Illuminate\Support\Facades\DB;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
DB::table('products')->truncate();
DB::table('order_items')->truncate();      // Optional: clear related tables
DB::table('cart_items')->truncate();       // Optional: clear related tables
DB::table('favorites')->truncate();        // Optional: clear related tables
DB::statement('SET FOREIGN_KEY_CHECKS=1');
        // \App\Models\User::factory(10)->create();
$this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            PartnerSeeder::class,
            CustomerSeeder::class,
            CouponSeeder::class,
        ]);



        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
