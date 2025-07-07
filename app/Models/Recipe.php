<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'image',
        'ingredients',
        'instructions',
        'cooking_time',
        'servings',
        'difficulty',
        'category',
        'is_featured',
        'views'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'ingredients' => 'array',
        'instructions' => 'array',
    ];

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
