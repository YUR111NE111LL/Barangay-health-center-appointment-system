<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const ROLE_SUPER_ADMIN = 'Super Admin';
    public const ROLE_HEALTH_CENTER_ADMIN = 'Health Center Admin';
    public const ROLE_NURSE = 'Nurse';
    public const ROLE_STAFF = 'Staff';
    public const ROLE_RESIDENT = 'Resident';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'role',
        'name',
        'purok_address',
        'profile_picture',
        'email',
        'password',
        'google_id',
        'is_approved',
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
            'password' => 'hashed',
            'is_approved' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** Appointments this user approved (for staff/nurse). */
    public function approvedAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'approved_by');
    }

    /** Check if user is Super Admin (no tenant). */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN && $this->tenant_id === null;
    }

    /** Check if user belongs to a tenant (not Super Admin). */
    public function hasTenant(): bool
    {
        return $this->tenant_id !== null;
    }

    /** Roles that require admin approval before access. */
    public static function rolesRequiringApproval(): array
    {
        return [self::ROLE_STAFF, self::ROLE_NURSE, self::ROLE_HEALTH_CENTER_ADMIN, self::ROLE_SUPER_ADMIN];
    }

    /** Roles that only Super Admin can approve (new Barangay Admin and new Super Admin). */
    public static function rolesApprovedBySuperAdmin(): array
    {
        return [self::ROLE_HEALTH_CENTER_ADMIN, self::ROLE_SUPER_ADMIN];
    }

    /** Roles that Barangay Admin can approve (Staff, Nurse in their tenant). */
    public static function rolesApprovedByBarangayAdmin(): array
    {
        return [self::ROLE_STAFF, self::ROLE_NURSE];
    }

    /** Initials for profile avatar (e.g. "YN" from "Yuri Neil Bayron"). */
    public function getInitialsAttribute(): string
    {
        $name = trim((string) $this->name);
        if ($name === '') {
            return strtoupper(substr((string) $this->email, 0, 2));
        }
        $words = preg_split('/\s+/', $name, 3);
        if (count($words) >= 2) {
            return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }
        return strtoupper(mb_substr($name, 0, 2));
    }

    /** Whether this role must be approved before the user can log in. */
    public function roleRequiresApproval(): bool
    {
        return in_array($this->role, self::rolesRequiringApproval(), true);
    }

    /** Whether the user is still pending approval (requires approval and not yet approved). */
    public function isPendingApproval(): bool
    {
        return $this->roleRequiresApproval() && ! $this->is_approved;
    }

    /**
     * For tenant users: check permission from tenant_role_permissions only (no Spatie fallback).
     * Returns true only if (tenant_id, role, ability) exists. Used for RBAC and UI (e.g. Book button).
     */
    public function hasTenantPermission(string $ability): bool
    {
        if ($this->tenant_id === null || ! Schema::hasTable('tenant_role_permissions')) {
            return false;
        }
        $roleName = trim((string) $this->role);
        if ($roleName === '') {
            return false;
        }
        \App\Services\TenantRbacSeeder::seedTenant($this->tenant_id);

        return DB::table('tenant_role_permissions')
            ->where('tenant_id', $this->tenant_id)
            ->whereRaw('LOWER(TRIM(role_name)) = LOWER(?)', [$roleName])
            ->where('permission_name', $ability)
            ->exists();
    }
}
