<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationDocument extends Model
{
    use HasFactory;

    public const TYPE_CARNET = 'carnet_acreditacion';

    protected $fillable = [
        'entrepreneur_profile_id',
        'document_type',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size',
        'status',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer', 'uploaded_at' => 'datetime'];
    }

    public function entrepreneurProfile()
    {
        return $this->belongsTo(EntrepreneurProfile::class);
    }
}