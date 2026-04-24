<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // 1. Ensure core roles exist
        $roles = ['super_admin', 'admin', 'distributor', 'influencer', 'buyer'];
        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        $password = Hash::make('password');

        // 2. Create Sample Admins
        $admins = [
            ['name' => 'Alexander Pierce', 'phone' => '0711111111', 'email' => 'alex@example.com', 'role' => 'admin'],
            ['name' => 'Sarah Connor', 'phone' => '0722222222', 'email' => 'sarah@example.com', 'role' => 'super_admin'],
            ['name' => 'Tony Stark', 'phone' => '0733333333', 'email' => 'tony@example.com', 'role' => 'admin'],
        ];

        foreach ($admins as $data) {
            $user = User::updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $password,
                    'role' => ($data['role'] === 'super_admin' ? 'super_admin' : 'admin'),
                ]
            );
            $user->syncRoles([$data['role']]);
        }

        // 3. Create Sample Partners (Distributors & Influencers)
        $partners = [
            ['name' => 'Vogue Distributing', 'phone' => '0744444444', 'email' => 'vogue@example.com', 'role' => 'distributor'],
            ['name' => 'Elite Logistics', 'phone' => '0755555555', 'email' => 'elite@example.com', 'role' => 'distributor'],
            ['name' => 'Chef Emily', 'phone' => '0766666666', 'email' => 'emily@example.com', 'role' => 'influencer'],
            ['name' => 'Healthy Habits', 'phone' => '0777777777', 'email' => 'healthy@example.com', 'role' => 'influencer'],
        ];

        foreach ($partners as $data) {
            $user = User::updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $password,
                    'role' => 'user', 
                ]
            );
            $user->syncRoles([$data['role']]);
        }

        // 4. Create Sample Buyers
        for ($i = 0; $i < 5; $i++) {
            $num = 8000000 + $i;
            $user = User::updateOrCreate(
                ['phone' => "07{$num}"],
                [
                    'name' => "Customer {$i}",
                    'email' => "customer{$i}@example.com",
                    'password' => $password,
                    'role' => 'user',
                ]
            );
            $user->syncRoles(['buyer']);
        }
    }
}
