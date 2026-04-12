<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'user_id',
        'guest_name',
        'comment',
        'is_approved',
    ];

    /**
     * Relationship: Belongs-to Blog
     */
    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Relationship: Belongs-to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
