@extends('layouts.panel-admin')

@section('panel-content')
    <x-panel-header title="Configuración del sistema" subtitle="Parámetros globales de plazos y límites." />

    <div class="card card-body max-w-2xl">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
            @csrf

            <div>
                <label for="document_deadline_days" class="input-label">Plazo para cargar documentación (días)</label>
                <input type="number" id="document_deadline_days" name="document_deadline_days" min="1" max="60" value="{{ old('document_deadline_days', $parameters['document_deadline_days']) }}" required class="input-text">
                @error('document_deadline_days')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="validation_deadline_days" class="input-label">Plazo de revisión de documentos (días)</label>
                <input type="number" id="validation_deadline_days" name="validation_deadline_days" min="1" max="60" value="{{ old('validation_deadline_days', $parameters['validation_deadline_days']) }}" required class="input-text">
                @error('validation_deadline_days')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="ai_limit_per_user" class="input-label">Límite de generaciones IA por usuario / período</label>
                <input type="number" id="ai_limit_per_user" name="ai_limit_per_user" min="1" max="1000" value="{{ old('ai_limit_per_user', $parameters['ai_limit_per_user']) }}" required class="input-text">
                @error('ai_limit_per_user')<p class="error-message" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-xl bg-purple-50 border border-purple-200 p-4 text-sm text-gray-700">
                <p class="font-semibold text-purple-800 mb-2">Proveedores de servicios (solo lectura)</p>
                <ul class="space-y-1">
                    <li><strong>Generación de imágenes IA:</strong> {{ $parameters['ai_provider'] }}</li>
                    <li><strong>Reconocimiento de voz (STT):</strong> {{ $parameters['stt_provider'] }}</li>
                    <li><strong>Síntesis de voz (TTS):</strong> {{ $parameters['tts_provider'] }}</li>
                </ul>
                <p class="mt-2 text-xs text-gray-500">Se configuran mediante las variables de entorno AI_PROVIDER, STT_PROVIDER y TTS_PROVIDER.</p>
            </div>

            <button type="submit" class="btn btn-primary">Guardar configuración</button>
        </form>
    </div>
@endsection