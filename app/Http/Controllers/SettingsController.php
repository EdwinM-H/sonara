<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function index()
    {
        // Los valores se leen de la tabla settings (parámetros del sistema)
        $parameters = [
            'document_deadline_days' => Settings::get('document_deadline_days', 7),
            'validation_deadline_days' => Settings::get('validation_deadline_days', 7),
            'ai_limit_per_user' => Settings::get('ai_limit_per_user', config('services.ai.max_per_user')),
            'ai_provider' => config('services.ai.provider'),
            'stt_provider' => config('services.voice.stt_provider'),
            'tts_provider' => config('services.voice.tts_provider'),
        ];

        return view('admin.settings.index', compact('parameters'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'document_deadline_days' => ['required', 'integer', 'min:1', 'max:60'],
            'validation_deadline_days' => ['required', 'integer', 'min:1', 'max:60'],
            'ai_limit_per_user' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        Settings::set('document_deadline_days', $validated['document_deadline_days']);
        Settings::set('validation_deadline_days', $validated['validation_deadline_days']);
        Settings::set('ai_limit_per_user', $validated['ai_limit_per_user']);

        $this->audit->log('settings_updated', Settings::class, null, 'Parámetros del sistema actualizados.');

        return back()->with('success', 'Configuración guardada.');
    }
}