<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $url
 * @property string $url_type
 * @property string|null $sub_path
 * @property string|null $base_domain
 * @property string|null $logo
 * @property string $icon
 * @property bool $is_active
 * @property bool $supports_api_auth
 * @property string|null $api_key
 * @property string|null $api_secret
 * @property array<array-key, mixed>|null $allowed_domains
 * @property \Illuminate\Support\Carbon|null $api_key_expires_at
 * @property bool $api_active
 * @property bool $external_auth_enabled
 * @property string|null $external_auth_callback_url
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read string|null $full_url
 * @property-read string|null $logo_url
 * @property-read string $url_type_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application apiEnabled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application visibleToUser(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereAllowedDomains($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereApiActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereApiKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereApiKeyExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereApiSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereBaseDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereExternalAuthCallbackUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereExternalAuthEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereSubPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereSupportsApiAuth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereUrlType($value)
 */
	class Application extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $application_id
 * @property int $user_id
 * @property string $code_hash
 * @property string $redirect_uri
 * @property string|null $state
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $consumed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Application $application
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereApplicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereCodeHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereConsumedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereRedirectUri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalAuthorizationCode whereUserId($value)
 */
	class ExternalAuthorizationCode extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property string $color
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Application> $applications
 * @property-read int $applications_count
 * @property-read \App\Models\User|null $creator
 * @property-read string $badge
 * @property-read int|null $users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $name
 * @property string $net_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $department
 * @property string|null $title
 * @property string|null $supervisor_net_id
 * @property string|null $supervisor_name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_admin
 * @property bool $is_manager
 * @property bool $is_supervisor
 * @property bool $is_active
 * @property-read string $full_name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User active()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsManager($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsSupervisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSupervisorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSupervisorNetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withRole(string $role)
 */
	class User extends \Eloquent {}
}

