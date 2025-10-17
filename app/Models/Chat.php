<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Ride;

class Chat extends Model
{


    use HasFactory;

    // Mass assignable fields
    protected $fillable = [ 'ride_id', 'sender_id', 'receiver_id', 'message', 'message_type', 'is_read' ];

    /**
     * The ride this chat belongs to
     */
    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    /**
     * The sender of the message
     */
    public function sender()
        {
            return $this->belongsTo(User::class, 'sender_id');
        }

        public function receiver()
        {
            return $this->belongsTo(User::class, 'receiver_id');
        }



}
