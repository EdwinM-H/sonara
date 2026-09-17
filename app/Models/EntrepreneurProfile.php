<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntrepreneurProfile extends Model
{
    use HasFactory;

    public const VERIF_PENDIENTE_DOCUMENTO = 'pendiente_documento';
    public const VERIF_DOCUMENTO_ENVIADO = 'documento_enviado';
    public const VERIF_EN_REVISION = 'en_revision';
    public const VERIF_APROBADO = 'aprobado';
    public const VERIF_RECHAZADO = 'rechazado';

    protected $fillable = [
        'user_id',
        'personal_description',
        'verification_status',
        'review_note',
        'registered_fully_at',
        'document_deadline_at',
        'validation_deadline_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_fully_at' => 'datetime',
            'document_deadline_at' => 'datetime',
            'validation_deadline_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function documents()
    {
        return $this->hasMany(VerificationDocument::class);
    }

    public function latestDocument()
    {
        return $this->hasOne(VerificationDocument::class)->latestOfMany('uploaded_at');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIF_APROBADO;
    }

    public function daysRemainingForDocument(): ?int
    {
        if (! $this->document_deadline_at || $this->isVerified()) {
            return null;
        }
        if (in_array($this->verification_status, [
            self::VERIF_DOCUMENTO_ENVIADO,
            self::VERIF_EN_REVISION,
        ])) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->document_deadline_at, false) + 1);
    }

    public function daysRemainingForValidation(): ?int
    {
        if (! $this->validation_deadline_at) {
            return null;
        }
        if ($this->verification_status !== self::VERIF_EN_REVISION) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->validation_deadline_at, false) + 1);
    }
}