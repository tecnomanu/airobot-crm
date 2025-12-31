<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Production Seeder - Creates only the minimum required data for a fresh production deployment.
 *
 * Usage: php artisan db:seed --class=ProductionSeeder
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Seeding production environment...');

        // Create root admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@airobot.com'],
            [
                'name' => 'Admin Root',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'is_seller' => false,
                'client_id' => null,
            ]
        );

        $this->command->info("✅ Admin user created: {$admin->email}");
        $this->command->warn('⚠️  Remember to change the default password!');
        $this->command->newLine();
        $this->command->info('Production seeding completed.');
    }
}

