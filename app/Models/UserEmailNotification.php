<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEmailNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'event_key', 'payload', 'scheduled_at', 'sent_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}

