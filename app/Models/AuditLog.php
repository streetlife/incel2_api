<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'module',
        'event',
        'action',
        'description',
        'usercode',
        'user_name',
        'user_email',
        'access_level',
        'subject_type',
        'subject_id',
        'booking_code',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'old_values',
        'new_values',
        'metadata',
        'status',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata'   => 'array',
    ];

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForBooking($query, string $bookingCode)
    {
        return $query->where('booking_code', $bookingCode);
    }

    public function scopeForUser($query, string $usercode)
    {
        return $query->where('usercode', $usercode);
    }
}
