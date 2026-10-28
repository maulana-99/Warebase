<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // No factories here: Faker is a dev dependency and is not installed on the production server.
        foreach ([
            ['Budi Santoso', 'admin@warebase.test', Role::Admin],
            ['Andi Pratama', 'staff@warebase.test', Role::Staff],
        ] as [$name, $email, $role]) {
            $user = new User(['name' => $name, 'email' => $email, 'password' => 'password']);
            $user->role = $role;
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
