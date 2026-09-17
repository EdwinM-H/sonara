<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIGeneration extends Model
{
    use HasFactory;

    protected $table = 'ai_generations';

    public const STATUS_PENDIENTE = 'pendiente';
    public const STATUS_GENERANDO = 'generando';
    public const STATUS_COMPLETADA = 'completada';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'user_id',
        'business_id',
        'publication_id',
        'prompt',
        'style',
        'provider',
        'status',
        'image_path',
        'reference',
        'error',
        'generation_time_ms',
        'cost',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:6',
            'generation_time_ms' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }
}