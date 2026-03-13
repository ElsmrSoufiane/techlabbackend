<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Delete records in reverse order of dependencies
        DB::table('order_items')->delete();
        DB::table('cart_items')->delete();
        DB::table('favorites')->delete();
        DB::table('products')->delete();
        DB::table('coupons')->delete();
        DB::table('customers')->delete();
        DB::table('partners')->delete();
        DB::table('categories')->delete();
        
        // Reset sequences
        $tables = [
            'categories', 'products', 'partners', 
            'customers', 'coupons', 'order_items', 
            'cart_items', 'favorites'
        ];
        
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
