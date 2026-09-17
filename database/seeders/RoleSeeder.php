<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'entrepreneur', 'customer'] as $role) {
            Role::updateOrCreate(['name' => $role], ['name' => $role, 'guard_name' => 'web']);
        }
    }
}