<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\EntrepreneurProfile;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Estadísticas del admin, calculadas en cada visita desde la base de datos.
 * El rango de fechas solo afecta al gráfico de altas de emprendedores.
 */
class StatsController extends Controller
{
    public const GROUPS = ['dia' => 'Por día', 'semana' => 'Por semana', 'mes' => 'Por mes'];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'agrupar' => ['nullable', 'in:'.implode(',', array_keys(self::GROUPS))],
        ]);

        $to = Carbon::parse($validated['hasta'] ?? now())->endOfDay();
        $from = Carbon::parse($validated['desde'] ?? $to->copy()->subDays(29))->startOfDay();
        $group = $validated['agrupar'] ?? 'dia';

        $entrepreneurs = User::role('entrepreneur');

        $kpis = [
            'entrepreneurs' => (clone $entrepreneurs)->count(),
            'businesses' => Business::count(),
            'with_ai_image' => Business::whereNotNull('image_url')->where('image_url', '!=', '')->count(),
            'with_conadis' => EntrepreneurProfile::where('tiene_carnet_conadis', true)->count(),
            'without_conadis' => EntrepreneurProfile::where('tiene_carnet_conadis', false)->count(),
        ];

        $grades = EntrepreneurProfile::whereIn('grado_discapacidad', EntrepreneurProfile::GRADOS_DISCAPACIDAD)
            ->selectRaw('grado_discapacidad, count(*) as total')
            ->groupBy('grado_discapacidad')
            ->pluck('total', 'grado_discapacidad');
        $byGrade = collect(EntrepreneurProfile::GRADOS_DISCAPACIDAD)
            ->mapWithKeys(fn ($grade) => [$grade => (int) ($grades[$grade] ?? 0)]);

        $signups = $this->signups(
            (clone $entrepreneurs)->whereBetween('users.created_at', [$from, $to])->pluck('users.created_at'),
            $from, $to, $group,
        );

        return view('admin.stats.index', [
            'kpis' => $kpis,
            'byGrade' => $byGrade,
            'signups' => $signups,
            'from' => $from,
            'to' => $to,
            'group' => $group,
            'groups' => self::GROUPS,
        ]);
    }

    /**
     * Altas por período, con ceros en los períodos sin altas para que el
     * gráfico muestre la línea de tiempo completa.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    protected function signups($dates, Carbon $from, Carbon $to, string $group): array
    {
        [$step, $key, $label] = match ($group) {
            'semana' => ['1 week', fn (Carbon $d) => $d->copy()->startOfWeek()->format('Y-m-d'), fn (Carbon $d) => 'Sem. '.$d->copy()->startOfWeek()->format('d/m')],
            'mes' => ['1 month', fn (Carbon $d) => $d->format('Y-m'), fn (Carbon $d) => ucfirst($d->translatedFormat('M Y'))],
            default => ['1 day', fn (Carbon $d) => $d->format('Y-m-d'), fn (Carbon $d) => $d->format('d/m')],
        };

        $counts = $dates->countBy(fn ($date) => $key(Carbon::parse($date)));

        $start = match ($group) {
            'semana' => $from->copy()->startOfWeek(),
            'mes' => $from->copy()->startOfMonth(),
            default => $from->copy(),
        };

        $labels = $values = [];
        foreach (CarbonPeriod::create($start, $step, $to) as $period) {
            $labels[] = $label($period);
            $values[] = (int) ($counts[$key($period)] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
