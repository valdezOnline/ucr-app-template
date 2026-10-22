<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationAudit extends Model
{
    protected $fillable = [
        'impersonator_user_id',
        'impersonated_user_id',
        'auth_type',
        'started_at',
        'ended_at',
        'start_ip',
        'end_ip',
        'start_user_agent',
        'end_user_agent',
        'stop_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_user_id');
    }

    public function impersonatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_user_id');
    }
}
