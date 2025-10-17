<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Driver;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'gender',
        'role',
        'profile_photo',
        'status',
        'is_locked',
        'is_verified', // optional
        'wallet_balance', // optional
        'verification_code', // optional
        'verification_code_expires_at', // optional
        'current_lat', // optional
        'current_lng', // optional
        'last_location_update', // optional
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token', // optional
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'status' => 'boolean',
    'is_locked' => 'boolean',
    'verification_code_expires_at' => 'datetime',
    'is_verified' => 'boolean',
    'current_lat' => 'decimal:7',
    'current_lng' => 'decimal:7',
    'wallet_balance' => 'decimal:2',
    ];



  protected static function boot()
{
    parent::boot();

    static::created(function ($user) {
        if ($user->role === 'driver') {
            Driver::create([
                'user_id' => $user->id,
            ]);
        }
    });
}

public function isLocked(): bool
{
    return (bool) $this->is_locked;
}

public function routeNotificationForFcm()
{
    return $this->fcm_token;
}
    // Relationships
    public function rides()
    {
        return $this->hasMany(Ride::class, 'passenger_id');
    }

    public function driver()
        {
            return $this->hasOne(Driver::class);
        }
        public function sentMessages()
        {
            return $this->hasMany(Chat::class, 'sender_id');
        }

        public function receivedMessages()
        {
            return $this->hasMany(Chat::class, 'receiver_id');
        }


}
