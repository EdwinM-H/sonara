<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessibilityPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'speech_rate',
        'volume',
        'auto_read',
        'repeat_prompts',
        'high_contrast',
        'font_size',
        'navigation_mode',
    ];

    protected function casts(): array
    {
        return [
            'auto_read' => 'boolean',
            'repeat_prompts' => 'boolean',
            'high_contrast' => 'boolean',
        ];
    }

    public static function defaultFor(User $user): self
    {
        $pref = new self([
            'speech_rate' => 'normal',
            'volume' => 'normal',
            'auto_read' => false,
            'repeat_prompts' => true,
            'high_contrast' => false,
            'font_size' => 'normal',
            'navigation_mode' => $user->isEntrepreneur() ? 'voz' : 'visual',
        ]);
        $pref->user_id = $user->id;

        return $pref;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}