<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavMenu extends Model
{
    protected $fillable = [
        'label',
        'url',
        'order_position',
        'is_active',
        'is_external',
    ];
}
