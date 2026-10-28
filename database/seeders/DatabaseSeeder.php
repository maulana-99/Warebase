<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo data for a campus warehouse.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            MasterDataSeeder::class,
            StockSeeder::class,
        ]);
    }
}
