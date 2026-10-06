@extends('layouts.panel-admin')

@section('title', 'Estadísticas')

@section('panel-content')
    <x-panel-header title="Estadísticas" subtitle="Datos en tiempo real de emprendedores, emprendimientos y discapacidad." />

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
        <x-stat-card label="Emprendedores" :value="$kpis['entrepreneurs']" icon="user" />
        <x-stat-card label="Emprendimientos" :value="$kpis['businesses']" icon="tag" />
        <x-stat-card label="Con imagen IA" :value="$kpis['with_ai_image']" icon="sparkles" />
        <x-stat-card label="Con carnet CONADIS" :value="$kpis['with_conadis']" icon="verified" accent="text-green-700" />
        <x-stat-card label="Sin carnet CONADIS" :value="$kpis['without_conadis']" icon="alert" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 2xl:grid-cols-3">
        {{-- Altas por fecha --}}
        <section aria-labelledby="stats-altas" class="card card-body !p-5 2xl:col-span-2">
            <h2 id="stats-altas" class="text-lg font-bold">Nuevos emprendedores</h2>

            <form method="GET" action="{{ route('admin.stats') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 sm:items-end">
                <div>
                    <label for="desde" class="input-label">Desde</label>
                    <input type="date" id="desde" name="desde" value="{{ $from->toDateString() }}" class="input-text w-full">
                </div>
                <div>
                    <label for="hasta" class="input-label">Hasta</label>
                    <input type="date" id="hasta" name="hasta" value="{{ $to->toDateString() }}" class="input-text w-full">
                </div>
                <div>
                    <label for="agrupar" class="input-label">Agrupar</label>
                    <select id="agrupar" name="agrupar" class="input-text w-full">
                        @foreach ($groups as $value => $label)
                            <option value="{{ $value }}" @selected($group === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-full">Aplicar</button>
            </form>
            @if ($errors->any())
                <p class="error-message mt-2" role="alert">{{ $errors->first() }}</p>
            @endif

            <p class="mt-4 text-sm text-gray-600">
                {{ array_sum($signups['values']) }} altas entre el {{ $from->format('d/m/Y') }} y el {{ $to->format('d/m/Y') }}.
            </p>
            <div class="relative mt-2 h-64 sm:h-80">
                <canvas role="img" aria-label="Gráfico de nuevos emprendedores {{ strtolower($groups[$group]) }}"
                        x-data="statsChart(@js(['type' => 'bar', 'label' => 'Nuevos emprendedores'] + $signups))"></canvas>
            </div>
        </section>

        {{-- Grado de discapacidad --}}
        <section aria-labelledby="stats-grado" class="card card-body !p-5">
            <h2 id="stats-grado" class="text-lg font-bold">Por grado de discapacidad</h2>
            @if ($byGrade->sum() === 0)
                <p class="mt-4 text-sm text-gray-600">Aún no hay emprendedores con grado de discapacidad registrado.</p>
            @else
                <div class="relative mt-4 h-64 sm:h-72">
                    <canvas role="img" aria-label="Gráfico por grado de discapacidad"
                            x-data="statsChart(@js(['type' => 'doughnut', 'label' => 'Emprendedores', 'labels' => $byGrade->keys()->map(fn ($g) => ucfirst(strtolower($g)))->all(), 'values' => $byGrade->values()->all()]))"></canvas>
                </div>
            @endif
            {{-- Los mismos datos en texto, para lectores de pantalla y sin JavaScript. --}}
            <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                @foreach ($byGrade as $grade => $total)
                    <div class="rounded-xl bg-gray-50 p-2">
                        <dt class="text-xs font-semibold text-gray-600">{{ ucfirst(strtolower($grade)) }}</dt>
                        <dd class="text-xl font-extrabold" data-grade="{{ $grade }}">{{ $total }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>
@endsection
