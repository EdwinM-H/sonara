<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistanceRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDIENTE = 'pendiente';
    public const STATUS_EN_ATENCION = 'en_atencion';
    public const STATUS_ATENDIDA = 'atendida';
    public const STATUS_CERRADA = 'cerrada';

    public const TRANSITIONS = [
        self::STATUS_PENDIENTE => [self::STATUS_EN_ATENCION, self::STATUS_ATENDIDA, self::STATUS_CERRADA],
        self::STATUS_EN_ATENCION => [self::STATUS_ATENDIDA, self::STATUS_CERRADA],
        self::STATUS_ATENDIDA => [self::STATUS_CERRADA],
        self::STATUS_CERRADA => [],
    ];

    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'preferred_channel',
        'status',
        'admin_notes',
        'handled_by',
        'handled_at',
    ];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }
}