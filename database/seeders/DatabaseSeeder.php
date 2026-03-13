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
 <?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable foreign key constraints (PostgreSQL way)
        DB::statement('SET session_replication_role = replica;');
        
        // Truncate tables in the correct order (children first, then parents)
        DB::table('order_items')->truncate();
        DB::table('cart_items')->truncate();
        DB::table('favorites')->truncate();
        DB::table('products')->truncate();
        
        // Re-enable foreign key constraints
        DB::statement('SET session_replication_role = DEFAULT;');

        // Call seeders
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            PartnerSeeder::class,
            CustomerSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
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
