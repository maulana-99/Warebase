<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo data for a campus warehouse.
     */
    public function run(): void
    {
        // Safe to run twice (e.g. again on the server): demo data is only created once.
        if (User::where('email', 'admin@warebase.test')->exists()) {
            $this->command?->warn('Data contoh sudah ada, seeder dilewati.');

            return;
        }

        $this->call([
            UserSeeder::class,
            MasterDataSeeder::class,
            StockSeeder::class,
        ]);
    }
}
