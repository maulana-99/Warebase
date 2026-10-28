<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'admin@warebase.test',
            'role' => Role::Admin,
        ]);

        User::factory()->create([
            'name' => 'Andi Pratama',
            'email' => 'staff@warebase.test',
            'role' => Role::Staff,
        ]);
    }
}
