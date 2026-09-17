<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'icon', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class)->orderBy('sort_order');
    }

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }
}