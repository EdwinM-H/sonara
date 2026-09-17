@extends('layouts.panel-entrepreneur')

@section('panel-content')
    <x-panel-header :title="'Flyer IA · '.$publication->name" subtitle="La inteligencia artificial crea una imagen promocional a partir de los datos de tu publicación." />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Generador --}}
        <div class="card card-body space-y-4" x-data="flyerGenerator" data-generate-route="{{ route('entrepreneur.flyers.generate', $publication) }}" data-remaining="{{ $remaining }}">
            <div>
                <label for="style" class="input-label">Estilo (opcional)</label>
                <input type="text" id="style" name="style" x-model="style" class="input-text" placeholder="Ej.: moderno, cálido, minimalista…" maxlength="100">
            </div>

            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Generaciones disponibles en este período:</span>
                <strong class="badge badge-info" x-text="remaining">0</strong>
            </div>

            <button type="button" @click="generate()" :disabled="loading" class="btn btn-primary w-full">
                <span x-show="!loading"><x-icon name="sparkles" class="w-5 h-5" /> Generar flyer</span>
                <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                    <span class="skeleton !w-4 !h-4 !rounded-full" aria-hidden="true"></span> Generando…
                </span>
            </button>

            <div role="status" aria-live="polite">
                <p x-show="message" x-text="message" :class="loading ? 'text-gray-600' : 'text-red-700'" class="text-sm"></p>
            </div>

            <template x-if="resultImage">
                <figure class="mt-2">
                    <div class="rounded-xl overflow-hidden border border-gray-200 bg-purple-50">
                        <img :src="resultImage" alt="Flyer generado por IA para {{ $publication->name }}" class="w-full h-auto">
                    </div>
                    <figcaption class="text-xs text-gray-500 mt-2 flex items-start gap-1.5">
                        <x-icon name="info" class="w-4 h-4 text-purple-500 shrink-0 mt-0.5" />
                        <span>Este es el flyer propuesto por la IA. Revísalo y, si te gusta, aprueba una versión como tu flyer oficial.</span>
                    </figcaption>
                </figure>
            </template>
        </div>

        {{-- Historial de generaciones --}}
        <section aria-labelledby="history-title" class="card card-body">
            <h2 id="history-title" class="section-title !mb-0 mb-4">Historial de este flyer</h2>

            <div class="space-y-3">
                @forelse ($generations as $gen)
                    <article class="rounded-xl border {{ $publication->flyer_image === $gen->image_path ? 'border-green-300 bg-green-50' : 'border-gray-200' }} p-3 flex gap-3 flex-col sm:flex-row">
                        <img src="{{ asset($gen->image_path) }}" alt="Generación {{ $gen->created_at->format('d/m/Y H:i') }}" class="h-24 w-full sm:w-32 object-cover rounded-lg shrink-0">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold">{{ ucfirst($gen->provider_name) }}</span>
                                <x-status-badge :status="$gen->status" />
                                @if ($publication->flyer_image === $gen->image_path)
                                    <span class="badge badge-success"><x-icon name="check-circle" class="w-4 h-4" /> Flyer oficial</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $gen->created_at->format('d/m/Y H:i') }}</p>
                            @if ($gen->status === \App\Models\AIGeneration::STATUS_COMPLETADA && $publication->flyer_image !== $gen->image_path)
                                <form method="POST" action="{{ route('entrepreneur.flyers.approve', [$publication, $gen]) }}" class="mt-2">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Usar este flyer</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state !py-8">
                        <span class="empty-icon"><x-icon name="sparkles" /></span>
                        <p class="mt-3 font-semibold text-gray-800">Aún no se han generado flyers para esta publicación</p>
                        <p class="text-sm text-gray-600 mt-1">Usa el generador para crear tu primera imagen promocional.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection