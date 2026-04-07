<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CashierUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'kasir@admin.com'],
            [
                'name' => 'Kasir',
                'role' => 'kasir',
                'password' => Hash::make('password123'),
            ],
        );
    }
}
