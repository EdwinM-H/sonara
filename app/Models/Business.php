<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    public const TYPE_PRODUCTO = 'producto';
    public const TYPE_SERVICIO = 'servicio';

    public const STATUS_ACTIVO = 'activo';
    public const STATUS_INACTIVO = 'inactivo';

    protected $fillable = [
        'entrepreneur_profile_id',
        'name',
        'slug',
        'description',
        'category_id',
        'subcategory_id',
        'type',
        'status',
        'availability',
        'currency',
        'price',
        'price_min',
        'price_max',
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
    ];

    protected function casts(): array
    {
        return [
            'payment_methods' => 'array',
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
            return 'Horario no especificado';
        }

        $open = $hours->first(fn ($h) => ! $h->is_closed && $h->open_time);

        return $open
            ? sprintf('Lunes a Domingo · %s – %s', substr($open->open_time, 0, 5), substr($open->close_time, 0, 5))
            : 'Abierto según disponibilidad';
    }

    public function getLocationSummaryAttribute(): string
    {
        return collect([$this->district, $this->province, $this->region, $this->country])
            ->filter()
            ->implode(', ');
    }
}