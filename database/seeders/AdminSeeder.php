<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@gundamshop.vn'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('123456'),
                'role' => 'admin',
            ]
        );
    }
}
