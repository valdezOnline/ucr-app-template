<?php

namespace Modules\Core\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'net_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'department',
        'title',
        'supervisor_net_id',
        'supervisor_name',
        'is_active',
        'last_login_at',
        'last_logout_at',
        'last_seen_at',
        'dashboard_application_order',
        'dashboard_shortcuts',
        'dashboard_display_mode',
        'dashboard_groups',
        'dashboard_group_display_mode',
        'dashboard_full_width',
        'dashboard_application_groups',
        'dashboard_locked',
        'password',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_logout_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'dashboard_locked' => 'boolean',
            'dashboard_full_width' => 'boolean',
            'dashboard_application_order' => 'array',
            'dashboard_shortcuts' => 'array',
            'dashboard_groups' => 'array',
            'dashboard_application_groups' => 'array',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get dynamic roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    /**
     * Get applications this user has access to.
     */
    public function accessibleApplications()
    {
        return Application::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles()->pluck('roles.id'));
        })->active();
    }

    /**
     * Get the display names of the user's active roles.
     */
    public function getRoles(): array
    {
        return $this->roles()->active()->pluck('display_name')->unique()->values()->toArray();
    }

    /**
     * Check if user has a specific role by name or display name.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->active()
            ->where(function ($query) use ($role) {
                $query->where('name', strtolower($role))
                    ->orWhere('display_name', $role);
            })
            ->exists();
    }

    /**
     * Assign a role to this user.
     */
    public function assignRole($role, $assignedBy = null): void
    {
        $roleModel = $role instanceof Role ? $role : Role::where('name', $role)->first();

        if ($roleModel) {
            $this->roles()->syncWithoutDetaching([
                $roleModel->id => [
                    'assigned_by' => $assignedBy ?? auth()->id(),
                    'assigned_at' => now(),
                ]
            ]);
        }
    }

    /**
     * Remove a role from this user.
     */
    public function removeRole($role): void
    {
        $roleModel = $role instanceof Role ? $role : Role::where('name', $role)->first();

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }
    }

    /**
     * Check if user is an administrator (has the 'admin' system role).
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a manager (has the 'manager' or 'library_manager' role).
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager') || $this->hasRole('library_manager');
    }

    /**
     * Check if user has access to a specific application.
     */
    public function hasAccessToApplication(Application $application): bool
    {
        return $application->hasAccess($this);
    }

    /**
     * Get the user's activity log entries.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class)->orderByDesc('created_at');
    }

    /**
     * Scope to filter active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter users active within the recent window.
     */
    public function scopeOnline($query, int $minutes = 5)
    {
        return $query->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Determine if this user has been seen recently.
     */
    public function isOnline(int $minutes = 5): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->greaterThanOrEqualTo(now()->subMinutes($minutes));
    }

    /**
     * Scope to filter users by role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', strtolower($role))->orWhere('display_name', $role);
        });
    }
}
