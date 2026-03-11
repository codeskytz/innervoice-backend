<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatingMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'sender_id',
        'text',
    ];

    public function match()
    {
        return $this->belongsTo(DatingMatch::class, 'match_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
