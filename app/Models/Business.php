<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory;

    public const TYPE_PRODUCTO = 'producto';
    public const TYPE_SERVICIO = 'servicio';

    public const STATUS_ACTIVO = 'activo';
    public const STATUS_INACTIVO = 'inactivo';

    public const PUBLISH_PENDIENTE = 'pendiente';
    public const PUBLISH_PUBLICADO = 'publicado';
    public const PUBLISH_ERROR = 'error';

    protected $fillable = [
        'entrepreneur_profile_id',
        'name',
        'slug',
        'description',
        'category_id',
        'subcategory_id',
        'custom_category',
        'sector',
        'tags',
        'type',
        'status',
        'availability',
        'currency',
        'price',
        'price_min',
        'price_max',
        'price_text',
        'schedule_text',
        'payment_methods',
        'country',
        'region',
        'province',
        'district',
        'address',
        'reference',
        'latitude',
        'longitude',
        'phone',
        'whatsapp',
        'contact_email',
        'image_prompt',
        'image_url',
        'image_pending',
        'external_ad_id',
        'external_ad_url',
        'publish_status',
        'publish_error',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_methods' => 'array',
            'tags' => 'array',
            'published_at' => 'datetime',
            'image_pending' => 'boolean',
            'price' => 'decimal:2',
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function entrepreneurProfile()
    {
        return $this->belongsTo(EntrepreneurProfile::class);
    }

    public function entrepreneur()
    {
        return $this->entrepreneurProfile();
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, EntrepreneurProfile::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /** Publicación que muestra en el muro el anuncio del registro por voz. */
    public function adPublication()
    {
        return $this->hasOne(Publication::class)->oldestOfMany();
    }

    /**
     * Crea (o completa) la publicación del anuncio en estado publicada,
     * para que el emprendimiento aparezca en el muro. Si la imagen aún no
     * está lista, la tarjeta muestra el marcador de posición.
     */
    public function ensureAdPublication(): Publication
    {
        $publication = $this->adPublication ?? new Publication([
            'business_id' => $this->id,
            'slug' => Publication::uniqueSlug($this->name),
        ]);

        $publication->fill([
            'entrepreneur_profile_id' => $this->entrepreneur_profile_id,
            'name' => $this->name,
            'description' => $this->description,
            // Recién creado, el modelo aún no trae los valores por defecto de la tabla.
            'type' => $this->type ?? self::TYPE_PRODUCTO,
            'currency' => $this->currency ?? 'PEN',
            'price' => $this->price,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'flyer_image' => $publication->flyer_image ?? self::localAssetPath($this->image_url),
            'status' => Publication::STATUS_PUBLICADA,
            'published_at' => $publication->published_at ?? now(),
        ])->save();

        $this->setRelation('adPublication', $publication);

        return $publication;
    }

    /**
     * Las vistas usan asset($ruta): una imagen de este mismo sitio se guarda
     * como ruta relativa (sobrevive a cambios de dominio); una externa, tal cual.
     */
    protected static function localAssetPath(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $base = rtrim(asset(''), '/').'/';

        return str_starts_with($url, $base) ? substr($url, strlen($base)) : $url;
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'emprendimiento';
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** Categoría del catálogo o, si se dictó un valor libre, ese valor. */
    public function categoryLabel(): ?string
    {
        return $this->category?->name ?? $this->custom_category;
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function hours()
    {
        return $this->hasMany(BusinessHour::class)->orderBy('day_of_week');
    }

    public function publications()
    {
        return $this->hasMany(Publication::class);
    }

    public function publishedPublications()
    {
        return $this->hasMany(Publication::class)->where('status', Publication::STATUS_PUBLICADA);
    }

    public function aiGenerations()
    {
        return $this->hasMany(AIGeneration::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function getDisplayPriceAttribute(): ?string
    {
        if ($this->price !== null) {
            return number_format($this->price, 2);
        }
        if ($this->price_min !== null || $this->price_max !== null) {
            return number_format($this->price_min ?? 0, 2).' - '.number_format($this->price_max ?? 0, 2);
        }

        return null;
    }

    public function getScheduleSummaryAttribute(): string
    {
        $hours = $this->hours;
        if ($hours->isEmpty()) {
            return $this->schedule_text ?: 'Horario no especificado';
        }

        $open = $hours->first(fn ($h) => ! $h->is_closed && $h->open_time);

        return $open
            ? sprintf('Lunes a Domingo · %s – %s', substr($open->open_time, 0, 5), substr($open->close_time, 0, 5))
            : 'Abierto según disponibilidad';
    }

    public function getLocationSummaryAttribute(): string
    {
        return collect([$this->address, $this->district, $this->province, $this->region, $this->country])
            ->filter()
            ->implode(', ');
    }
}