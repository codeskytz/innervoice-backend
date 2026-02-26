<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Confession extends Model
{
    /** @use HasFactory<\Database\Factories\ConfessionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'text',
        'category',
        'likes_count',
        'comments_count',
        'is_anonymous',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_comment_id')->with('user', 'replies');
    }

    public function allComments()
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('created_at');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
