<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoritePlace extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'lat',
        'lng',
        'address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}