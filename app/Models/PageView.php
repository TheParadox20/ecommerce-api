<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $fillable = [
        'path',
        'referrer',
        'session_id',
        'user_agent',
        'device_type',
        'browser',
        'user_id',
        'country',
        'country_code',
        'city',
    ];
}
