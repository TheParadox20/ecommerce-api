<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageBanner extends Model
{
    protected $fillable = [
        'image',
        'title',
        'description',
        'link_text',
        'link_url',
        'order',
        'is_active'
    ];
}
