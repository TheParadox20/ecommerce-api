<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpesa extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_request_id',
        'result_code',
        'result_desc',
        'merchant_request_id',
        'phone',
        'amount',
        'account_reference',
        'status',
        'mpesa_receipt_number',
    ];
}