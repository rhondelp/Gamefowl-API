<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

/**
 * File: database/seeders/DatabaseSeeder.php
 *
 * Purpose:
 *   The entry point for `php artisan db:seed`. Currently only loads the
 *   knowledge base; add additional seeders to the call array as the project
 *   grows (e.g. a default admin account).
 *
 * How it fits into the project:
 *   `php artisan migrate --seed` runs this after migrations, giving every
 *   fresh install the diagnostic data Milestones 5-8 depend on.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            KnowledgeBaseSeeder::class,
        ]);
    }
}
