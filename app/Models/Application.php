<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'url',
        'url_type',
        'sub_path',
        'base_domain',
        'logo',
        'icon',
        'is_active',
        'created_by',
        'supports_api_auth',
        'api_key',
        'api_secret',
        'allowed_domains',
        'api_key_expires_at',
        'api_active',
        'external_auth_enabled',
        'external_auth_callback_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supports_api_auth' => 'boolean',
        'api_active' => 'boolean',
        'external_auth_enabled' => 'boolean',
        'allowed_domains' => 'array',
        'api_key_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * URL type constants
     */
    const URL_TYPE_EXTERNAL = 'external';
    const URL_TYPE_SUB_APPLICATION = 'sub-application';
    const URL_TYPE_FOLDER = 'folder';

    /**
     * Get the user who created this application.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all roles required for this application.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'application_roles')
            ->withPivot(['assigned_by'])
            ->withTimestamps();
    }

    /**
     * Get all users who have access to this application through roles.
     */
    public function authorizedUsers()
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles()->pluck('roles.id'));
        });
    }

    /**
     * Check if a user has access to this application.
     */
    public function hasAccess(User $user): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $user->roles()->whereIn('roles.id', $this->roles()->pluck('roles.id'))->exists();
    }

    /**
     * Scope to filter active applications.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get applications visible to a specific user.
     */
    public function scopeVisibleToUser($query, User $user)
    {
        return $query->active()
            ->whereHas('roles', function ($roleQuery) use ($user) {
                $roleQuery->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id);
                });
            });
    }

    /**
     * Get the application's icon or default.
     */
    public function getIconAttribute($value): string
    {
        return $value ?: 'fas fa-cube';
    }

    /**
     * Get the application's logo URL or default.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    /**
     * Get the full URL for the application based on its type.
     */
    public function getFullUrlAttribute(): ?string
    {
        switch ($this->url_type) {
            case self::URL_TYPE_EXTERNAL:
                return $this->url;

            case self::URL_TYPE_SUB_APPLICATION:
                if ($this->base_domain && $this->sub_path) {
                    return rtrim($this->base_domain, '/') . '/' . ltrim($this->sub_path, '/');
                }
                return $this->url; // Fallback to original URL

            case self::URL_TYPE_FOLDER:
                if ($this->base_domain && $this->sub_path) {
                    return rtrim($this->base_domain, '/') . '/' . ltrim($this->sub_path, '/');
                }
                return $this->url; // Fallback to original URL

            default:
                return $this->url;
        }
    }

    /**
     * Get URL type options for form select.
     */
    public static function getUrlTypeOptions(): array
    {
        return [
            self::URL_TYPE_EXTERNAL => 'External Website',
            self::URL_TYPE_SUB_APPLICATION => 'Sub-Application (same domain)',
            self::URL_TYPE_FOLDER => 'Folder View (same domain)',
        ];
    }

    /**
     * Get human-readable URL type name.
     */
    public function getUrlTypeNameAttribute(): string
    {
        $options = self::getUrlTypeOptions();
        return $options[$this->url_type] ?? 'Unknown';
    }

    /**
     * Check if the application requires a sub-path.
     */
    public function requiresSubPath(): bool
    {
        return in_array($this->url_type, [self::URL_TYPE_SUB_APPLICATION, self::URL_TYPE_FOLDER]);
    }

    /**
     * Validate URL structure based on type.
     */
    public function validateUrlStructure(): bool
    {
        switch ($this->url_type) {
            case self::URL_TYPE_EXTERNAL:
                return !empty($this->url) && filter_var($this->url, FILTER_VALIDATE_URL);

            case self::URL_TYPE_SUB_APPLICATION:
            case self::URL_TYPE_FOLDER:
                return !empty($this->base_domain) &&
                    !empty($this->sub_path) &&
                    filter_var($this->base_domain, FILTER_VALIDATE_URL);

            default:
                return false;
        }
    }

    /**
     * Generate API credentials for external application authentication.
     */
    public function generateApiCredentials(): array
    {
        $apiKey = 'app_' . bin2hex(random_bytes(24));
        $apiSecret = bin2hex(random_bytes(32));

        $this->update([
            'api_key' => $apiKey,
            'api_secret' => hash('sha256', $apiSecret),
            'supports_api_auth' => true,
            'api_active' => true,
            'api_key_expires_at' => now()->addYear(), // Default 1 year expiration
        ]);

        // Return the plain secret (only time it will be available)
        return [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret, // Plain text for initial setup
        ];
    }

    /**
     * Verify API credentials.
     */
    public function verifyApiCredentials(string $apiKey, string $apiSecret): bool
    {
        if (!$this->supports_api_auth || !$this->api_active || !$this->is_active) {
            return false;
        }

        if ($this->api_key !== $apiKey) {
            return false;
        }

        if ($this->api_key_expires_at && $this->api_key_expires_at->isPast()) {
            return false;
        }

        return hash_equals($this->api_secret, hash('sha256', $apiSecret));
    }

    /**
     * Check if domain is allowed for API access.
     */
    public function isDomainAllowed(?string $domain): bool
    {
        if (empty($this->allowed_domains)) {
            return true; // No restrictions
        }

        if (!$domain) {
            return false;
        }

        return in_array($domain, $this->allowed_domains, true);
    }

    /**
     * Revoke API access for this application.
     */
    public function revokeApiAccess(): void
    {
        $this->update([
            'api_key' => null,
            'api_secret' => null,
            'api_active' => false,
        ]);
    }

    /**
     * Scope for applications that support API authentication.
     */
    public function scopeApiEnabled($query)
    {
        return $query->where('supports_api_auth', true)
            ->where('api_active', true)
            ->whereNotNull('api_key')
            ->whereNotNull('api_secret');
    }

    /**
     * Check if API credentials are expired.
     */
    public function isApiExpired(): bool
    {
        return $this->api_key_expires_at && $this->api_key_expires_at->isPast();
    }

    /**
     * Check whether this application can use external broker authentication.
     */
    public function canUseExternalAuthBroker(): bool
    {
        return $this->is_active
            && $this->supports_api_auth
            && $this->api_active
            && $this->external_auth_enabled
            && !empty($this->external_auth_callback_url);
    }

    /**
     * Validate a provided redirect URI against the registered callback.
     */
    public function isValidExternalCallback(string $redirectUri): bool
    {
        if (empty($this->external_auth_callback_url)) {
            return false;
        }

        return rtrim($redirectUri, '/') === rtrim($this->external_auth_callback_url, '/');
    }
}
