<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'used_count',
        'min_fare',
        'valid_from',
        'valid_until',
        'is_active'
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function isValid($fareAmount = null)
    {
        if (!$this->is_active) return false;
        
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        
        if ($this->valid_from && now()->lt($this->valid_from)) return false;
        
        if ($this->valid_until && now()->gt($this->valid_until)) return false;
        
        if ($fareAmount && $this->min_fare && $fareAmount < $this->min_fare) return false;
        
        return true;
    }

    public function calculateDiscount($fareAmount)
    {
        if ($this->type === 'percentage') {
            return $fareAmount * ($this->value / 100);
        }
        
        return min($this->value, $fareAmount); // Can't discount more than fare
    }
}