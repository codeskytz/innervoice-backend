<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'confession_count',
    ];

    public function confessions(): HasMany
    {
        return $this->hasMany(Confession::class);
    }

    public function scopeActive($query)
    {
        return $query->where('confession_count', '>', 0);
    }
}
