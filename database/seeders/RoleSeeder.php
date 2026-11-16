<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(['name' => Role::ADMIN], ['label' => 'Administrateur']);
        Role::updateOrCreate(['name' => Role::EMPLOYEE], ['label' => 'Employé']);
    }
}
