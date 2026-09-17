<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Request extends Model
{
    use HasFactory;

    public const STATUS_ENVIADA = 'enviada';
    public const STATUS_VISTA = 'vista';
    public const STATUS_ACEPTADA = 'aceptada';
    public const STATUS_RECHAZADA = 'rechazada';
    public const STATUS_COMPLETADA = 'completada';
    public const STATUS_CANCELADA = 'cancelada';

    public const TRANSITIONS = [
        self::STATUS_ENVIADA => [self::STATUS_VISTA, self::STATUS_ACEPTADA, self::STATUS_RECHAZADA, self::STATUS_CANCELADA],
        self::STATUS_VISTA => [self::STATUS_ACEPTADA, self::STATUS_RECHAZADA, self::STATUS_CANCELADA],
        self::STATUS_ACEPTADA => [self::STATUS_COMPLETADA, self::STATUS_RECHAZADA, self::STATUS_CANCELADA],
        self::STATUS_RECHAZADA => [],
        self::STATUS_COMPLETADA => [],
        self::STATUS_CANCELADA => [],
    ];

    protected $fillable = [
        'code',
        'customer_id',
        'business_id',
        'publication_id',
        'customer_name',
        'customer_phone',
        'item_name',
        'quantity',
        'preferred_date',
        'preferred_time',
        'message',
        'status',
        'seen_at',
        'response_note',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'preferred_date' => 'date',
            'preferred_time' => 'datetime',
            'seen_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Request $request) {
            if (empty($request->code)) {
                $request->code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        return 'SOL-'.date('Ymd').'-'.strtoupper(Str::random(6));
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Fecha y hora preferidas combinadas para mostrar al emprendedor/cliente.
     * `preferred_time` ya incluye la fecha (ver CustomerRequestController::store);
     * si solo se indicó fecha, se usa esa.
     */
    public function getPreferredAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->preferred_time ?: $this->preferred_date;
    }
}