<?php
// database/seeders/CouponSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CouponSeeder extends Seeder
{
    public function run()
    {
   
        $admin = Customer::where('email', 'admin@teclab.ma')->first();
        
        if (!$admin) {
            $this->command->error('❌ Admin account not found! Please run CustomerSeeder first.');
            return;
        }

        $this->command->info("✅ Found admin user with ID: {$admin->id}");
        
        // Define coupons array
        $coupons = [
            [
                'code' => 'JOYEUXADMIN',
                'name' => 'Bonne Anniversaire Admin',
                'description' => '10% de réduction pour votre anniversaire',
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 400,
                'max_uses' => 1,
                'customer_id' => $admin->id,
                'starts_at' => Carbon::now()->addDays(rand(30, 300)),
                'expires_at' => Carbon::now()->addDays(rand(330, 400)),
            ],
            [
                'code' => 'ADMIN20',
                'name' => 'Remise Spéciale Admin',
                'description' => '20% de réduction sur votre prochaine commande',
                'type' => 'percentage',
                'value' => 20,
                'min_order_amount' => 500,
                'max_uses' => 2,
                'customer_id' => $admin->id,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(3),
            ],
            [
                'code' => 'ADMIN100',
                'name' => 'Coupon Fidélité Admin',
                'description' => '100 MAD de réduction',
                'type' => 'fixed',
                'value' => 100,
                'min_order_amount' => 800,
                'max_uses' => 1,
                'customer_id' => $admin->id,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(2),
            ],
            [
                'code' => 'ADMINWEEKEND',
                'name' => 'Weekend Admin',
                'description' => '15% de réduction le weekend',
                'type' => 'percentage',
                'value' => 15,
                'min_order_amount' => 300,
                'max_uses' => 3,
                'customer_id' => $admin->id,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(6),
            ],
            [
                'code' => 'ADMINLIVRAISON',
                'name' => 'Livraison Offerte Admin',
                'description' => 'Livraison gratuite',
                'type' => 'fixed',
                'value' => 50,
                'min_order_amount' => 200,
                'max_uses' => 5,
                'customer_id' => $admin->id,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(4),
            ],
        ];

        // Method 1: Using firstOrCreate to avoid duplicates
        foreach ($coupons as $couponData) {
            $defaults = [
                'used_count' => 0,
                'is_public' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            Coupon::firstOrCreate(
                ['code' => $couponData['code']], // Check by unique code
                array_merge($couponData, $defaults)
            );
        }

        // Method 2: Using updateOrCreate if you want to update existing coupons
        /*
        foreach ($coupons as $couponData) {
            Coupon::updateOrCreate(
                ['code' => $couponData['code']],
                array_merge($couponData, [
                    'used_count' => 0,
                    'is_public' => false,
                    'is_active' => true,
                    'updated_at' => now(),
                ])
            );
        }
        */

        // Method 3: Clear and reseed (if you want fresh coupons every time)
        /*
        Coupon::where('customer_id', $admin->id)->delete();
        foreach ($coupons as $couponData) {
            Coupon::create(array_merge($couponData, [
                'used_count' => 0,
                'is_public' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
        */

        // Re-enable foreign key checks
        

        // Display summary
        $totalCoupons = Coupon::where('customer_id', $admin->id)->count();
        $percentageCoupons = Coupon::where('customer_id', $admin->id)->where('type', 'percentage')->count();
        $fixedCoupons = Coupon::where('customer_id', $admin->id)->where('type', 'fixed')->count();

        $this->command->info('=====================================');
        $this->command->info('✅ COUPONS CREATED/VERIFIED SUCCESSFULLY!');
        $this->command->info('=====================================');
        $this->command->info("👤 Admin ID: {$admin->id}");
        $this->command->info("📊 Total coupons: {$totalCoupons}");
        $this->command->info("📈 Percentage coupons: {$percentageCoupons}");
        $this->command->info("💰 Fixed coupons: {$fixedCoupons}");
        $this->command->info('=====================================');
        $this->command->info('🎁 Available coupons:');
        
        $adminCoupons = Coupon::where('customer_id', $admin->id)->get();
        foreach ($adminCoupons as $coupon) {
            $status = $coupon->is_active ? '✅' : '❌';
            $expiry = $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : 'No expiry';
            $discount = $coupon->type === 'percentage' ? "{$coupon->value}%" : "{$coupon->value} MAD";
            
            $this->command->info("   {$status} {$coupon->code} - {$discount} (exp: {$expiry})");
        }
        $this->command->info('=====================================');
    }
}
