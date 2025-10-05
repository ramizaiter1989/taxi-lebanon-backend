<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    protected $table = 'otps'; // 👈 force table name
    protected $fillable = ['phone', 'code', 'expires_at'];

    public function isExpired()
{
    return $this->expires_at < now();
}

}
