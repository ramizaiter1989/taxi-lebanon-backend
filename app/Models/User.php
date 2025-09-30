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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'is_locked' => 'boolean',
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
        ];
    }

  protected static function boot()
{
    parent::boot();

    static::created(function ($user) {
        if ($user->role === 'driver') {
            \App\Models\Driver::create([
                'user_id' => $user->id,
            ]);
        }
    });
}

public function isLocked(): bool
{
    return (bool) $this->is_locked;
}


    // Relationships
    public function rides()
    {
        return $this->hasMany(Ride::class, 'passenger_id');
    }

    public function driver()
{
    return $this->hasOne(\App\Models\Driver::class);
}
}
