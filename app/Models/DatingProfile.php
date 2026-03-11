<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatingProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'bio',
        'age',
        'gender',
        'photos',
        'location',
        'interests'
    ];

    protected $casts = [
        'photos' => 'array',
        'interests' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
