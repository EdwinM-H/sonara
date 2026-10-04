<?php

namespace App\Models;

use App\Services\Assistant\VoiceText;
use Illuminate\Database\Eloquent\Model;

class CatalogOption extends Model
{
    protected $fillable = ['catalog_type_id', 'name', 'sort_order'];

    protected static function booted(): void
    {
        static::saving(function (CatalogOption $option) {
            $option->normalized_name = VoiceText::normalize($option->name);
        });
    }

    public function type()
    {
        return $this->belongsTo(CatalogType::class, 'catalog_type_id');
    }
}
