<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Automatically selects the appropriate seeder based on environment:
     * - local/testing: LocalSeeder (full demo data)
     * - production/staging: ProductionSeeder (minimal data)
     *
     * Manual usage:
     * - php artisan db:seed --class=ProductionSeeder
     * - php artisan db:seed --class=LocalSeeder
     */
    public function run(): void
    {
        $environment = app()->environment();

        if (in_array($environment, ['local', 'testing'])) {
            $this->command->info("Environment: {$environment} → Running LocalSeeder");
            $this->call(LocalSeeder::class);
        } else {
            $this->command->info("Environment: {$environment} → Running ProductionSeeder");
            $this->call(ProductionSeeder::class);
        }
    }
}
