@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Solicitud de asistencia" subtitle="¿Necesitas ayuda con el uso de la plataforma? Escríbenos y un administrador te contactará." />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="card card-body">
            <h2 class="text-lg font-bold mb-4">Enviar nueva solicitud</h2>
            <form method="POST" action="{{ route('entrepreneur.assistance.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="subject" class="input-label">Asunto *</label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required class="input-text @error('subject') input-error @enderror" placeholder="Ej.: Necesito ayuda para subir mi documento">
                    @error('subject')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="message" class="input-label">Describe tu problema *</label>
                    <textarea id="message" name="message" rows="4" required class="input-text @error('message') input-error @enderror">{{ old('message') }}</textarea>
                    @error('message')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="preferred_channel" class="input-label">¿Cómo prefieres que te contactemos? *</label>
                    <select id="preferred_channel" name="preferred_channel" required class="input-text">
                        @foreach (['sistema' => 'Por el sistema (notificaciones)', 'telefono' => 'Por teléfono', 'whatsapp' => 'Por WhatsApp', 'voz' => 'Por asistente de voz'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('preferred_channel') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('preferred_channel')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Enviar solicitud</button>
            </form>
        </div>

        <div class="card card-body">
            <h2 class="text-lg font-bold mb-4">Historial de solicitudes</h2>
            @if ($assistanceRequests->isEmpty())
                <p class="text-gray-500 text-sm">Aún no has enviado solicitudes de asistencia.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($assistanceRequests as $assistance)
                        <li class="rounded-lg border border-gray-100 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-sm truncate">{{ $assistance->subject }}</p>
                                <x-status-badge :status="$assistance->status" />
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $assistance->created_at->format('d/m/Y H:i') }} · Preferencia: {{ $assistance->preferred_channel }}</p>
                            @if ($assistance->admin_notes)
                                <p class="text-xs mt-2 rounded-lg bg-purple-50 p-2 text-purple-800"><strong>Respuesta:</strong> {{ $assistance->admin_notes }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
                {{ $assistanceRequests->links() }}
            @endif
        </div>
    </div>
@endsection