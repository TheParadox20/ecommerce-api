<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'purpose',
        'file',
        'url',
    ];
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
