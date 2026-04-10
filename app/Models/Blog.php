<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'youtube_url',
        'status',
        'allow_comments',
    ];

    protected $with = ['brands', 'products'];

    /**
     * Relationship: Many-to-Many with Brand
     */
    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'blog_brand');
    }

    /**
     * Relationship: Many-to-Many with Product
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'blog_product');
    }

    /**
     * Relationship: Many-to-Many with Recipe
     */
    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'blog_recipe');
    }

    /**
     * Relationship: One-to-Many with BlogComment
     */
    public function comments()
    {
        return $this->hasMany(BlogComment::class);
    }

    /**
     * Get comment count (approved only for public, maybe all for admin)
     * For now, a simple count attribute.
     */
    public function getCommentCountAttribute()
    {
        return $this->comments()->where('is_approved', true)->count();
    }

    /**
     * Boot function to handle slug generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($blog) {
            if (!$blog->slug) {
                $blog->slug = Str::slug($blog->title);
            }
        });
    }
}
