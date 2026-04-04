<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PmsNotification extends Model
{
    use HasFactory;

    protected $table = 'pms_notifications';

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'type',
        'data',
        'channel',
        'read_at',
        'sent_at',
        'scheduled_for',
        'is_critical',
    ];

    protected $casts = [
        'data'          => 'array',
        'channel'       => NotificationChannel::class,
        'read_at'       => 'datetime',
        'sent_at'       => 'datetime',
        'scheduled_for' => 'datetime',
        'is_critical'   => 'boolean',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }
}
