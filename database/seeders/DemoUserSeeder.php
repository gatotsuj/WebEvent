<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Product Manager
        $productManager = User::create([
            'name' => 'John Product Manager',
            'email' => 'product@eventticket.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $productManager->assignRole('product_manager');

        // Sales Staff
        $salesStaff = User::create([
            'name' => 'Jane Sales Staff',
            'email' => 'sales@eventticket.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $salesStaff->assignRole('sales_staff');

        // Regular Customer
        $customer = User::create([
            'name' => 'Bob Customer',
            'email' => 'customer@eventticket.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $customer->assignRole('customer');

        $this->command->info('Demo users created successfully!');
    }
}
