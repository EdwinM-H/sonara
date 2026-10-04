<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Revisa que los registros de emprendedores tengan los seis campos del
// registro por voz completos y el PIN hasheado. Nunca imprime el PIN.
Artisan::command('sonara:verificar-emprendedores', function (\App\Services\Verification\EntrepreneurRecordValidator $validator) {
    $rows = [];
    $incomplete = 0;
    foreach (\App\Models\User::role('entrepreneur')->with('entrepreneurProfile')->orderBy('id')->get() as $user) {
        $issues = $validator->issuesFor($user);
        $incomplete += $issues ? 1 : 0;
        $rows[] = [
            $user->id,
            $user->username ?: '—',
            $validator->pinStatus($user),
            $issues
                ? collect($issues)->map(fn ($problem, $field) => \App\Services\Verification\EntrepreneurRecordValidator::FIELDS[$field].' ('.$problem.')')->implode(', ')
                : 'OK',
        ];
    }

    $this->table(['ID', 'Usuario de voz', 'PIN', 'Problemas'], $rows);
    $this->line(count($rows).' emprendedores revisados, '.$incomplete.' con datos incompletos.');

    return $incomplete ? 1 : 0;
})->purpose('Verifica que los registros de emprendedores estén completos (sin mostrar el PIN)');

// Repara los emprendimientos registrados por voz antes de que el registro
// creara su publicación: contaban en su categoría pero no salían en el muro.
Artisan::command('sonara:sincronizar-muro', function () {
    $missing = \App\Models\Business::whereNotNull('publish_status')->doesntHave('publications')->get();
    foreach ($missing as $business) {
        $publication = $business->ensureAdPublication();
        $this->line('Publicado en el muro: '.$business->name.' → '.route('public.publication', $publication->slug));
    }
    $this->info($missing->count().' emprendimiento(s) reparado(s).');
})->purpose('Crea la publicación del muro para emprendimientos registrados por voz que no la tienen');

// Reintenta la imagen de los emprendimientos cuya generación falló al
// registrarse (image_pending). Corre solo con el planificador activo.
Artisan::command('sonara:reintentar-imagenes', function (\App\Services\Publishing\BusinessPublisher $publisher) {
    $pending = \App\Models\Business::where('image_pending', true)->get();
    $ok = 0;
    foreach ($pending as $business) {
        $done = $publisher->retryImage($business);
        $ok += $done ? 1 : 0;
        $this->line(($done ? 'Imagen generada: ' : 'Sigue pendiente: ').$business->name);
    }
    $this->info($ok.' de '.$pending->count().' imagen(es) generada(s).');
})->purpose('Reintenta las imágenes de anuncio pendientes');

\Illuminate\Support\Facades\Schedule::command('sonara:reintentar-imagenes')->everyFiveMinutes()->withoutOverlapping();
