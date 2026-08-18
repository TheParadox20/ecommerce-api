<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content', // Used as description
        'image',
        'video_url',
        'ingredients',
        'instructions', // Used as steps
        'cooking_time',
        'servings',
        'difficulty',
        'category',
        'status',
        'is_featured',
        'views',
        // SEO fields
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'noindex',
    ];

    protected $casts = [
        'is_featured'  => 'boolean',
        'ingredients'  => 'array',
        'instructions' => 'array',
        'status'       => 'string',
        'noindex'      => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_recipe');
    }

    public function getFormattedCookingTimeAttribute()
    {
        if ($this->cooking_time < 60) {
            return $this->cooking_time . ' min';
        }
        
        $hours = floor($this->cooking_time / 60);
        $minutes = $this->cooking_time % 60;
        
        if ($minutes === 0) {
            return $hours . ' hr';
        }
        
        return $hours . ' hr ' . $minutes . ' min';
    }

    public function getDifficultyColorAttribute()
    {
        return match($this->difficulty) {
            'easy' => 'green',
            'medium' => 'yellow',
            'hard' => 'red',
            default => 'gray'
        };
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('views', 'desc');
    }
}
