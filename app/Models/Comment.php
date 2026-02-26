<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['user_id', 'confession_id', 'text', 'parent_comment_id'];
    protected $withCount = ['replies'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function confession()
    {
        return $this->belongsTo(Confession::class);
    }

    // Parent comment (for replies)
    public function parentComment()
    {
        return $this->belongsTo(Comment::class, 'parent_comment_id');
    }

    // Child comments (replies)
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_comment_id')->with('user', 'replies');
    }

    // Check if this is a root comment (not a reply)
    public function isRootComment(): bool
    {
        return $this->parent_comment_id === null;
    }

    // Get all child comments recursively
    public function getAllDescendants()
    {
        return $this->replies()->with('user')->get();
    }
}
