<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = ['user_id', 'confession_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function confession()
    {
        return $this->belongsTo(Confession::class);
    }
}
