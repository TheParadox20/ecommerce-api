<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@ngwindsongk.com'],
            [
                'name' => 'System Administrator',
                'phone' => '0718156421',
                'password' => Hash::make('Admin@2024!'), // Strong default password
                'status' => 'active',
                'role' => 'super_admin', // Legacy field support
            ]
        );

        // Assign Spatie Role
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        $this->command->info('✅ Super Admin created successfully.');
        $this->command->info('Email: admin@ngwindsongk.com');
        $this->command->info('Password: Admin@2024!');
    }
}
