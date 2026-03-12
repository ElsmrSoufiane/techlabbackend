<?php
// database/seeders/CustomerSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        // Create Admin Account
        Customer::create([
            'name' => 'Admin TECLAB',
            'email' => 'admin@teclab.ma',
            'password' => Hash::make('admin123'),
            'phone' => '+212 600-000000',
            'address' => 'Rue 7 N° 184/Q4, Fès, Maroc',
            'role' => 'admin',
            'tier' => 'regular',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create PRO Accounts with different discounts
        $proAccounts = [
            [
                'name' => 'Laboratoire Central SARL',
                'email' => 'contact@laboratoire-central.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 661-234567',
                'address' => 'Angle Avenue Hassan II et Rue Allah Ben Abdellah, Casablanca',
                'company_name' => 'Laboratoire Central SARL',
                'tier' => 'pro',
                'pro_discount' => 15, // 15% discount for bulk orders
                'role' => 'customer',
            ],
            [
                'name' => 'Clinique Atlas',
                'email' => 'achats@clinique-atlas.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 522-345678',
                'address' => 'Boulevard Zerktouni, N° 45, Casablanca',
                'company_name' => 'Clinique Atlas SA',
                'tier' => 'pro',
                'pro_discount' => 20, // 20% discount for medical equipment
                'role' => 'customer',
            ],
            [
                'name' => 'Université Mohammed V',
                'email' => 'laboratoire@um5.ac.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 537-456789',
                'address' => 'Avenue Ibn Battouta, B.P. 1014, Rabat',
                'company_name' => 'Université Mohammed V - Faculté des Sciences',
                'tier' => 'pro',
                'pro_discount' => 25, // 25% discount for educational institutions
                'role' => 'customer',
            ],
            [
                'name' => 'ONEE - Office National',
                'email' => 'achats@onee.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 537-567890',
                'address' => '65, Avenue Aspirant Lafuente, Casablanca',
                'company_name' => 'Office National de l\'Electricité',
                'tier' => 'pro',
                'pro_discount' => 18, // 18% discount for government institutions
                'role' => 'customer',
            ],
            [
                'name' => 'OCP SA',
                'email' => 'fournisseurs@ocp.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 522-678901',
                'address' => '2-4, Rue Al Abtal, Hay Erraha, Casablanca',
                'company_name' => 'OCP S.A.',
                'tier' => 'pro',
                'pro_discount' => 22, // 22% discount for industrial partners
                'role' => 'customer',
            ],
            [
                'name' => 'Institut Pasteur Maroc',
                'email' => 'commandes@pasteur.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 522-789012',
                'address' => '1, Place Louis Pasteur, Casablanca',
                'company_name' => 'Institut Pasteur du Maroc',
                'tier' => 'pro',
                'pro_discount' => 20, // 20% discount for research institutions
                'role' => 'customer',
            ],
            [
                'name' => 'Les Eaux Minérales d\'Oulmès',
                'email' => 'laboratoire@oulmes.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 537-890123',
                'address' => 'Zone Industrielle, Aïn Sebaâ, Casablanca',
                'company_name' => 'Les Eaux Minérales d\'Oulmès',
                'tier' => 'pro',
                'pro_discount' => 15, // 15% discount for quality control labs
                'role' => 'customer',
            ],
            [
                'name' => 'ONSSA',
                'email' => 'laboratoire@onssa.gov.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 537-901234',
                'address' => 'Avenue Hadj Ahmed Cherkaoui, Agdal, Rabat',
                'company_name' => 'Office National de Sécurité Sanitaire',
                'tier' => 'pro',
                'pro_discount' => 18, // 18% discount for government labs
                'role' => 'customer',
            ],
            [
                'name' => 'MANAGEM',
                'email' => 'achats@managem.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 522-012345',
                'address' => 'Twin Center, Tour A, Angle Bd Zerktouni, Casablanca',
                'company_name' => 'MANAGEM S.A.',
                'tier' => 'pro',
                'pro_discount' => 20, // 20% discount for mining industry
                'role' => 'customer',
            ],
            [
                'name' => 'Pharma 5',
                'email' => 'laboratoire@pharma5.ma',
                'password' => Hash::make('pro123'),
                'phone' => '+212 522-123456',
                'address' => 'Zone Industrielle, Bouskoura, Casablanca',
                'company_name' => 'PHARMA 5',
                'tier' => 'pro',
                'pro_discount' => 15, // 15% discount for pharmaceutical industry
                'role' => 'customer',
            ],
        ];

        foreach ($proAccounts as $account) {
            Customer::create(array_merge($account, [
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Create some regular customer accounts for testing
        $regularAccounts = [
            [
                'name' => 'Ahmed Benani',
                'email' => 'ahmed.benani@email.com',
                'password' => Hash::make('customer123'),
                'phone' => '+212 661-111111',
                'address' => '123 Rue de la Liberté, Casablanca',
                'role' => 'customer',
                'tier' => 'regular',
            ],
            [
                'name' => 'Fatima Zahra Alaoui',
                'email' => 'fatima.alaoui@email.com',
                'password' => Hash::make('customer123'),
                'phone' => '+212 662-222222',
                'address' => '456 Avenue Mohammed V, Rabat',
                'role' => 'customer',
                'tier' => 'regular',
            ],
            [
                'name' => 'Youssef Idrissi',
                'email' => 'youssef.idrissi@email.com',
                'password' => Hash::make('customer123'),
                'phone' => '+212 663-333333',
                'address' => '789 Boulevard Hassan II, Fès',
                'role' => 'customer',
                'tier' => 'regular',
            ],
        ];

        foreach ($regularAccounts as $account) {
            Customer::create(array_merge($account, [
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('✅ Admin account created');
        $this->command->info('✅ 10 PRO accounts created with varying discounts');
        $this->command->info('✅ 3 Regular customer accounts created');
    }
}