<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    protected $fillable = ['name', 'description', 'icon', 'color', 'category'];

    public function messages()
    {
        return $this->hasMany(CommunityMessage::class);
    }
}
