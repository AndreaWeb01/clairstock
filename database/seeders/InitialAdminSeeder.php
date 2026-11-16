<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('Compte administrateur non créé : définissez INITIAL_ADMIN_EMAIL et INITIAL_ADMIN_PASSWORD avant db:seed.');

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 12) {
            throw new \InvalidArgumentException('INITIAL_ADMIN_EMAIL doit être valide et INITIAL_ADMIN_PASSWORD doit contenir au moins 12 caractères.');
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('INITIAL_ADMIN_NAME', 'Administrateur'),
                'password' => Hash::make($password),
                'role_id' => Role::where('name', Role::ADMIN)->value('id'),
            ],
        );
    }
}
