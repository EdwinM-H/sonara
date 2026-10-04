<?php

namespace App\Services\Assistant;

use App\Models\CatalogType;
use App\Services\Catalog\CatalogService;

/**
 * Registro de un emprendimiento por voz.
 *
 * Los campos con catálogo (categoría, sector, etiquetas) leen sus
 * opciones de CatalogService, la misma fuente de /api/catalogos: se
 * dictan al usuario y su respuesta se valida contra ellas. Si el admin
 * aún no creó opciones, se acepta un valor libre (marcado en "_libres"
 * para notificar al admin).
 */
class BusinessVoiceRegistrationService extends VoiceFlow
{
    public const INTRO = 'Vamos a registrar tu emprendimiento. ';

    public const NONE_WORDS = ['ninguna', 'ninguno', 'nada', 'no', 'no tengo', 'sin etiquetas', 'continuar'];

    protected const EMPTY_CATALOG = 'No hay opciones registradas para este campo. Por favor díctame el valor que deseas usar.';

    /** @var array<string, string> campo con catálogo => slug del catálogo */
    protected const CATALOG_FIELDS = [
        'categoria' => CatalogService::CATEGORIAS,
        'sector' => CatalogType::SECTORES,
        'etiquetas' => CatalogType::ETIQUETAS,
    ];

    public function __construct(protected CatalogService $catalogs, ?string $sessionKey = null)
    {
        parent::__construct($sessionKey);
    }

    protected function steps(): array
    {
        $categorias = $this->optionsFor('categoria');
        $sectores = $this->optionsFor('sector');
        $etiquetas = $this->optionsFor('etiquetas');

        return [
            'nombre' => '¿Cuál es el nombre de tu emprendimiento?',
            'descripcion' => 'Describe brevemente tu emprendimiento.',
            'tipo' => '¿Ofreces un producto o un servicio?',
            'categoria' => '¿Cuál es la categoría? '.($categorias
                ? 'Las opciones son: '.self::spokenList($categorias).'. Di el nombre de la categoría que corresponde.'
                : self::EMPTY_CATALOG),
            'sector' => '¿En qué sector se encuentra? '.($sectores
                ? 'Las opciones son: '.self::spokenList($sectores).'. Di el nombre del sector.'
                : self::EMPTY_CATALOG),
            'etiquetas' => '¿Tienes etiquetas para tu emprendimiento? '
                .($etiquetas ? 'Las opciones son: '.self::spokenList($etiquetas).'. ' : '')
                .'Di las palabras clave separadas por pausa, o di ninguna para continuar.',
            'telefono' => '¿Cuál es el teléfono de contacto de tu emprendimiento?',
            'ubicacion' => '¿Cuál es la dirección o ubicación del emprendimiento?',
            'precio' => '¿Cuál es el precio o rango de precios de tu emprendimiento? '
                .'Por ejemplo: desde diez dólares, o precio a consultar.',
            'horario' => '¿Cuál es el horario de atención de tu emprendimiento? '
                .'Por ejemplo: lunes a viernes de ocho a cinco.',
        ];
    }

    protected function intro(): string
    {
        return self::INTRO;
    }

    protected function help(): string
    {
        return 'Responde cada pregunta después del pitido. Puedes decir: repetir, atrás o cancelar. ';
    }

    protected function exitMessage(): string
    {
        return 'Se canceló el registro del emprendimiento. Puedes empezar de nuevo cuando quieras.';
    }

    protected function cachePrefix(): string
    {
        return 'voice_business';
    }

    protected function validate(string $field, string $transcript, array $data): array
    {
        $normalized = VoiceText::normalize($transcript);

        switch ($field) {
            case 'nombre':
                if (mb_strlen($normalized) < 2) {
                    return ['value' => null, 'error' => 'No te entendí.'];
                }

                return ['value' => mb_substr($normalized, 0, 150), 'error' => null];

            case 'descripcion':
                if (mb_strlen($normalized) < 3) {
                    return ['value' => null, 'error' => 'No te entendí.'];
                }

                return ['value' => mb_substr($normalized, 0, 1000), 'error' => null];

            case 'tipo':
                $words = explode(' ', $normalized);
                $producto = (bool) array_intersect($words, ['producto', 'productos', 'vendo']);
                $servicio = (bool) array_intersect($words, ['servicio', 'servicios']);
                if ($producto === $servicio) {
                    return ['value' => null, 'error' => 'Di producto o servicio.'];
                }

                return ['value' => $producto ? 'producto' : 'servicio', 'error' => null];

            case 'precio':
                if (mb_strlen($normalized) < 2 && ! ctype_digit($normalized)) {
                    return ['value' => null, 'error' => 'No te entendí.'];
                }

                return ['value' => mb_substr($normalized, 0, 190), 'error' => null];

            case 'horario':
                if (mb_strlen($normalized) < 3) {
                    return ['value' => null, 'error' => 'No te entendí.'];
                }

                return ['value' => mb_substr($normalized, 0, 190), 'error' => null];

            case 'categoria':
            case 'sector':
                return $this->chooseOne($field, $normalized);

            case 'etiquetas':
                return $this->chooseTags($normalized);

            case 'telefono':
                $phone = SpokenDigits::parsePhone($transcript);
                if (! $phone) {
                    return ['value' => null, 'error' => 'No entendí el número. Dilo número por número.'];
                }

                return ['value' => $phone, 'error' => null];

            case 'ubicacion':
                if (mb_strlen($normalized) < 2) {
                    return ['value' => null, 'error' => 'No te entendí.'];
                }

                return ['value' => mb_substr($normalized, 0, 255), 'error' => null];
        }

        return ['value' => $normalized, 'error' => null];
    }

    /** Categoría o sector: una opción del catálogo, o valor libre si está vacío. */
    protected function chooseOne(string $field, string $normalized): array
    {
        if (mb_strlen($normalized) < 2) {
            return ['value' => null, 'error' => 'No te entendí.'];
        }

        $options = $this->optionsFor($field);
        if (! $options) {
            return ['value' => ['id' => null, 'nombre' => mb_substr($normalized, 0, 150)], 'error' => null, 'free' => true];
        }

        $match = $this->catalogs->match($normalized, $options);
        if (! $match) {
            return ['value' => null, 'error' => self::invalidOption($options), 'speak_only' => true];
        }

        return ['value' => ['id' => $match['id'], 'nombre' => $match['nombre']], 'error' => null];
    }

    /** Etiquetas: opcionales; del catálogo, o palabras libres si está vacío. */
    protected function chooseTags(string $normalized): array
    {
        if ($normalized === '') {
            return ['value' => null, 'error' => 'No te entendí.'];
        }
        if (in_array($normalized, self::NONE_WORDS, true)) {
            return ['value' => [], 'error' => null];
        }

        $options = $this->optionsFor('etiquetas');
        if (! $options) {
            // Las pausas no llegan en el texto dictado: cada palabra clave
            // es una etiqueta (sin conectores como "y" o "coma").
            $words = array_diff(explode(' ', $normalized), ['y', 'e', 'coma', 'la', 'el', 'de']);
            $tags = array_slice(array_values(array_unique(array_filter($words))), 0, 10);

            return ['value' => $tags, 'error' => null, 'free' => (bool) $tags];
        }

        $matches = $this->catalogs->matchAll($normalized, $options);
        if (! $matches) {
            return [
                'value' => null,
                'error' => 'Esas etiquetas no están disponibles. Las opciones son: '.self::spokenList($options)
                    .'. Por favor elige una, o di ninguna para continuar.',
                'speak_only' => true,
            ];
        }

        return ['value' => array_column($matches, 'nombre'), 'error' => null];
    }

    // Sin memoizar: el admin puede cambiar un catálogo a mitad del
    // registro y la siguiente pregunta debe reflejarlo.
    protected function optionsFor(string $field): array
    {
        return $this->catalogs->options(self::CATALOG_FIELDS[$field]);
    }

    public static function invalidOption(array $options): string
    {
        return 'Esa opción no está disponible. Las opciones son: '.self::spokenList($options).'. Por favor elige una.';
    }

    protected static function spokenList(array $options): string
    {
        return implode(', ', array_column($options, 'nombre'));
    }
}
