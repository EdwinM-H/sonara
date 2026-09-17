@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header title="Documentación" subtitle="Carga tu documento de acreditación para verificar tu identidad como emprendedor." />

    @if ($profile->isVerified())
        <div role="status" class="rounded-2xl mb-6 border-[1.5px] p-4 flex items-center gap-3" style="background-color: var(--color-success-soft); border-color: var(--color-success-border); color: var(--color-success)">
            <x-icon name="check-circle" class="w-5 h-5 shrink-0" />
            <p>¡Felicidades! Tu cuenta está <strong>verificada</strong>. Ya puedes mostrar tu insignia de emprendedor verificado.</p>
        </div>
    @else
        @if ($profile->verification_status === 'rechazado')
            <div role="alert" class="rounded-2xl mb-6 border-[1.5px] p-4" style="background-color: var(--color-error-soft); border-color: var(--color-error-border); color: var(--color-error)">
                <p class="font-bold flex items-center gap-2"><x-icon name="alert" class="w-5 h-5" /> Tu documentación fue rechazada</p>
                @if ($profile->review_note)
                    <p class="mt-2 rounded-xl bg-white/70 px-3 py-2 text-sm"><strong>Motivo:</strong> {{ $profile->review_note }}</p>
                @endif
                <p class="mt-2 text-sm">Carga nuevamente tu documento de acreditación para continuar el proceso de verificación.</p>
            </div>
        @elseif ($profile->verification_status === 'en_revision')
            <div role="status" class="rounded-2xl mb-6 border-[1.5px] p-4 text-sm" style="background-color: var(--color-info-soft); border-color: var(--color-info-border); color: var(--color-info)">
                Tu documento está en revisión por el administrador. Tienes un máximo de <strong>{{ $validationDays }} día(s)</strong> para obtener respuesta.
            </div>
        @else
            <div role="alert" class="rounded-2xl mb-6 border-[1.5px] p-4" style="background-color: var(--color-warning-soft); border-color: var(--color-warning-border); color: var(--color-warning)">
                @if ($profile->review_note)
                    <p class="mb-2 rounded-xl bg-white/70 px-3 py-2 text-sm"><strong>El administrador pidió corregir:</strong> {{ $profile->review_note }}</p>
                @endif
                <p class="font-bold">Plazo vigente: {{ $documentDays }} día(s) restante(s)</p>
                <p class="mt-1 text-sm">Sube tu carné de acreditación dentro de este plazo para no perder tu cuenta de emprendedor.</p>
            </div>
        @endif
        @if ($errors->has('document'))
            <div role="alert" class="rounded-2xl mb-6 border-[1.5px] p-4 text-sm" style="background-color: var(--color-error-soft); border-color: var(--color-error-border); color: var(--color-error)">{{ $errors->first('document') }}</div>
        @endif
    @endif

    @if (! $profile->isVerified() && $profile->verification_status !== 'en_revision')
        <div class="card card-body max-w-2xl">
            <h2 class="text-lg font-display font-bold mb-3">Subir documento</h2>
            <p class="text-sm text-gray-600 mb-4">Formatos permitidos: PDF, JPG, PNG, WEBP. Tamaño máximo: 5 MB.</p>
            <form method="POST" action="{{ route('entrepreneur.documents.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="document" class="input-label">Archivo del documento</label>
                    <input type="file" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="input-text">
                    @error('document')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Subir documento</button>
            </form>
        </div>
    @endif

    <div class="card card-body mt-6 !p-0">
        <h2 class="text-lg font-display font-bold !p-5 pb-0">Documentos enviados</h2>
        @if ($documents->isEmpty())
            <p class="text-gray-500 text-sm !p-5">Aún no has enviado documentos.</p>
        @else
            <div class="overflow-x-auto mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Archivo</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Estado</th>
                            <th scope="col"><span class="sr-only">Acción</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $doc)
                            <tr>
                                <td class="font-semibold">{{ $doc->original_name }}</td>
                                <td>{{ $doc->uploaded_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ ucfirst($doc->status) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('entrepreneur.documents.show', $doc) }}" class="btn btn-sm btn-outline"><x-icon name="download" class="w-4 h-4" /> Descargar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
