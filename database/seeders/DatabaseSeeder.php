<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // For PostgreSQL - disable triggers
        DB::statement('SET session_replication_role = replica;');
        
        // Truncate tables
        DB::table('favorites')->truncate();
        DB::table('cart_items')->truncate();
        DB::table('order_items')->truncate();
        DB::table('products')->truncate();
        DB::table('coupons')->truncate();
        DB::table('customers')->truncate();
        DB::table('partners')->truncate();
        DB::table('categories')->truncate();
        
        // Re-enable triggers
        DB::statement('SET session_replication_role = DEFAULT;');
        
        // Reset sequences
        $tables = ['categories', 'products', 'partners', 'customers', 'coupons', 'order_items', 'cart_items', 'favorites'];
        foreach ($tables as $table) {
            try {
                DB::statement("ALTER SEQUENCE {$table}_id_seq RESTART WITH 1;");
            } catch (\Exception $e) {
                // Ignore if sequence doesn't exist
            }
        }

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            PartnerSeeder::class,
            CustomerSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
