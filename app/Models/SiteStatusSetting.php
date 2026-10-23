<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteStatusSetting extends Model
{
    public const MODE_NONE = 'none';
    public const MODE_MAINTENANCE = 'maintenance';
    public const MODE_TECHNICAL_ISSUE = 'technical_issue';
    public const MODE_CUSTOM_MESSAGE = 'custom_message';

    protected $fillable = [
        'mode',
        'message',
        'updated_by',
    ];

    public static function current(): self
    {
        $current = static::query()->orderBy('id')->first();
        if ($current) {
            return $current;
        }

        return static::query()->create([
            'mode' => self::MODE_NONE,
            'message' => null,
        ]);
    }

    public static function availableModes(): array
    {
        return [
            self::MODE_NONE,
            self::MODE_MAINTENANCE,
            self::MODE_TECHNICAL_ISSUE,
            self::MODE_CUSTOM_MESSAGE,
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->mode !== self::MODE_NONE;
    }

    public function displayTitle(): string
    {
        return match ($this->mode) {
            self::MODE_MAINTENANCE => 'Maintenance Mode',
            self::MODE_TECHNICAL_ISSUE => 'Experiencing Technical Issue',
            self::MODE_CUSTOM_MESSAGE => 'Important Notice',
            default => 'Site Available',
        };
    }

    public function displayMessage(): string
    {
        if ($this->message) {
            return $this->message;
        }

        return match ($this->mode) {
            self::MODE_MAINTENANCE => 'The site is currently under scheduled maintenance. Please check back soon.',
            self::MODE_TECHNICAL_ISSUE => 'We are currently experiencing technical issues. Our team is working to resolve this as quickly as possible.',
            self::MODE_CUSTOM_MESSAGE => 'A temporary message has been posted by the administrator.',
            default => 'The site is operating normally.',
        };
    }
}
