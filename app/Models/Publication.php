<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Publication extends Model
{
    use HasFactory;

    public const STATUS_BORRADOR = 'borrador';
    public const STATUS_PENDIENTE = 'pendiente';
    public const STATUS_PUBLICADA = 'publicada';
    public const STATUS_RECHAZADA = 'rechazada';
    public const STATUS_SUSPENDIDA = 'suspendida';

    public const TYPE_PRODUCTO = 'producto';
    public const TYPE_SERVICIO = 'servicio';

    public const TRANSITIONS = [
        self::STATUS_BORRADOR => [self::STATUS_PENDIENTE],
        self::STATUS_PENDIENTE => [self::STATUS_PUBLICADA, self::STATUS_RECHAZADA],
        self::STATUS_PUBLICADA => [self::STATUS_SUSPENDIDA],
        self::STATUS_RECHAZADA => [self::STATUS_PENDIENTE, self::STATUS_BORRADOR],
        self::STATUS_SUSPENDIDA => [self::STATUS_PUBLICADA, self::STATUS_BORRADOR],
    ];

    protected $fillable = [
        'business_id',
        'entrepreneur_profile_id',
        'name',
        'slug',
        'description',
        'type',
        'price',
        'price_min',
        'price_max',
        'currency',
        'status',
        'flyer_image',
        'rejection_reason',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function entrepreneurProfile()
    {
        return $this->belongsTo(EntrepreneurProfile::class);
    }

    public function aiGenerations()
    {
        return $this->hasMany(AIGeneration::class);
    }

    public function images()
    {
        return $this->hasMany(PublicationImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(PublicationImage::class)->where('is_primary', true);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLICADA;
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'publicacion';
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getPriceDisplayAttribute(): string
    {
        $currency = $this->currency === 'PEN' ? 'S/' : $this->currency;
        // Sin monto numérico (anuncios registrados por voz): el precio
        // dictado, o "Precio a consultar" en lugar de un engañoso "S/ 0.00".
        if ($this->price === null && $this->price_min === null && $this->price_max === null) {
            return $this->business?->price_text ?: 'Precio a consultar';
        }
        if ($this->price !== null) {
            return $currency.' '.number_format($this->price, 2);
        }
        $min = $this->price_min ?? 0;
        $max = $this->price_max;

        return $max !== null
            ? $currency.' '.number_format($min, 2).' – '.$currency.' '.number_format($max, 2)
            : $currency.' '.number_format($min, 2);
    }
}