<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ride_id', 'amount', 'status', 'payment_method', 'transaction_id', 'currency', 'paid_at'
    ];

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }
}
