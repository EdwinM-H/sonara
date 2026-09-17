<?php

namespace Database\Seeders;

use App\Models\AccessibilityPreference;
use App\Models\CustomerProfile;
use App\Models\EntrepreneurProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Password123!';

    public function run(): void
    {
        // ------------------------------------------------ Administrador
        $admin = User::updateOrCreate(
            ['email' => 'admin@sonara.test'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Sonara',
                'name' => 'Admin Sonara',
                'phone' => '999000001',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVO,
            ],
        );
        $admin->syncRoles(['admin']);

        // ------------------------------------------------ Emprendedor
        $entrepreneur = User::updateOrCreate(
            ['email' => 'emprendedor@sonara.test'],
            [
                'first_name' => 'Ana',
                'last_name' => 'Huamán',
                'name' => 'Ana Huamán',
                'phone' => '999000002',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVO,
            ],
        );
        $entrepreneur->syncRoles(['entrepreneur']);

        EntrepreneurProfile::updateOrCreate(
            ['user_id' => $entrepreneur->id],
            [
                'personal_description' => 'Emprendedora con más de 5 años elaborando productos artesanales textiles.',
                'verification_status' => EntrepreneurProfile::VERIF_APROBADO,
                'document_deadline_at' => null,
                'validation_deadline_at' => null,
                'registered_fully_at' => now()->subDays(30),
                'verified_at' => now()->subDays(20),
            ],
        );

        AccessibilityPreference::updateOrCreate(
            ['user_id' => $entrepreneur->id],
            [
                'speech_rate' => 'normal',
                'volume' => 'normal',
                'auto_read' => true,
                'repeat_prompts' => true,
                'high_contrast' => false,
                'font_size' => 'normal',
                'navigation_mode' => 'voz',
            ],
        );

        // ------------------------------------------------ Cliente
        $customer = User::updateOrCreate(
            ['email' => 'cliente@sonara.test'],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Pérez',
                'name' => 'Carlos Pérez',
                'phone' => '999000003',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVO,
            ],
        );
        $customer->syncRoles(['customer']);
        CustomerProfile::updateOrCreate(
            ['user_id' => $customer->id],
            ['preferences' => ['busca' => 'productos artesanales']],
        );
    }
}