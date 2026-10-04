<?php

namespace App\Services\Catalog;

use App\Models\CatalogType;
use App\Models\Category;
use App\Services\Assistant\VoiceText;

/**
 * Fuente única de los catálogos que el admin gestiona: la consumen la
 * API (/api/catalogos) y el registro de emprendimiento por voz, para que
 * ambos vean exactamente las mismas opciones.
 *
 * "categorias" sale de la tabla de categorías (activas); los demás tipos
 * (sectores, etiquetas y los que el admin cree) de catalog_options.
 */
class CatalogService
{
    public const CATEGORIAS = 'categorias';

    /** @return array<string, array{nombre: string, opciones: list<array{id: int, nombre: string, valor: string}>}> */
    public function all(): array
    {
        $catalogs = [self::CATEGORIAS => [
            'nombre' => 'Categorías',
            'opciones' => $this->categoryOptions(),
        ]];

        foreach (CatalogType::with('options')->orderBy('id')->get() as $type) {
            $catalogs[$type->slug] = [
                'nombre' => $type->name,
                'opciones' => $type->options->map(fn ($o) => [
                    'id' => $o->id,
                    'nombre' => $o->name,
                    'valor' => $o->normalized_name,
                ])->all(),
            ];
        }

        return $catalogs;
    }

    /** @return ?array{nombre: string, opciones: list<array{id: int, nombre: string, valor: string}>} */
    public function get(string $slug): ?array
    {
        return $this->all()[$slug] ?? null;
    }

    /** @return list<array{id: int, nombre: string, valor: string}> */
    public function options(string $slug): array
    {
        return $this->get($slug)['opciones'] ?? [];
    }

    /**
     * Busca la opción que el usuario dictó. Ambos lados se normalizan
     * (minúsculas, sin tildes ni signos). Acepta la opción exacta o dicha
     * dentro de una frase ("la categoría es tecnología"); si varias
     * coinciden, gana la más larga ("comida rapida" sobre "comida").
     *
     * @param  list<array{id: int, nombre: string, valor: string}>  $options
     * @return ?array{id: int, nombre: string, valor: string}
     */
    public function match(string $spoken, array $options): ?array
    {
        $spoken = VoiceText::normalize($spoken);
        if ($spoken === '') {
            return null;
        }

        $best = null;
        foreach ($options as $option) {
            $value = $option['valor'];
            if ($value === '') {
                continue;
            }
            if ($value === $spoken) {
                return $option;
            }
            if (self::containsPhrase($spoken, $value) || self::containsPhrase($value, $spoken)) {
                if (! $best || strlen($value) > strlen($best['valor'])) {
                    $best = $option;
                }
            }
        }

        return $best;
    }

    /**
     * Todas las opciones mencionadas en el texto (para etiquetas).
     *
     * @param  list<array{id: int, nombre: string, valor: string}>  $options
     * @return list<array{id: int, nombre: string, valor: string}>
     */
    public function matchAll(string $spoken, array $options): array
    {
        $spoken = VoiceText::normalize($spoken);

        return array_values(array_filter(
            $options,
            fn ($option) => $option['valor'] !== '' && self::containsPhrase($spoken, $option['valor']),
        ));
    }

    /** ¿$needle aparece en $haystack como palabras completas? */
    protected static function containsPhrase(string $haystack, string $needle): bool
    {
        return str_contains(' '.$haystack.' ', ' '.$needle.' ');
    }

    protected function categoryOptions(): array
    {
        return Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'nombre' => $c->name,
                'valor' => VoiceText::normalize($c->name),
            ])
            ->all();
    }
}
