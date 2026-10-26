<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class EmailDispatchLog extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'status',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'reply_to_recipients',
        'subject',
        'from_email',
        'from_name',
        'html_body',
        'text_body',
        'queued_at',
        'sent_at',
        'failed_at',
        'attempts',
        'error_message',
    ];

    protected $casts = [
        'to_recipients' => 'array',
        'cc_recipients' => 'array',
        'bcc_recipients' => 'array',
        'reply_to_recipients' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
