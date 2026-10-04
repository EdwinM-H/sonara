<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogType extends Model
{
    public const SECTORES = 'sectores';
    public const ETIQUETAS = 'etiquetas';

    protected $fillable = ['name', 'slug', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function options()
    {
        return $this->hasMany(CatalogOption::class)->orderBy('sort_order')->orderBy('name');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
