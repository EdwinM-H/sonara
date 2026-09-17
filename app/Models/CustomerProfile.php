<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'preferences'];

    protected function casts(): array
    {
        return ['preferences' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}