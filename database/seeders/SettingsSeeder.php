<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Settings::set('document_deadline_days', 7);
        Settings::set('validation_deadline_days', 7);
        Settings::set('ai_limit_per_user', config('services.ai.max_per_user', 20));
        Settings::set('site_name', 'SONARA');
    }
}