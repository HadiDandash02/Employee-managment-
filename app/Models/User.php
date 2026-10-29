<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'employee_id',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * The employee record this login belongs to (users.employee_id), used by the Employee role.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Whether this user's role grants the given permission (see config/permissions.php).
     */
    public function hasPermission(string $permission): bool
    {
        $granted = config("permissions.roles.{$this->role}.permissions", []);

        return in_array('*', $granted, true) || in_array($permission, $granted, true);
    }

    /**
     * Role keys whose accounts this user may see and manage (config "manages").
     *
     * @return list<string>
     */
    public function manageableRoles(): array
    {
        $allRoles = array_keys(config('permissions.roles', []));
        $manages = config("permissions.roles.{$this->role}.manages", []);

        return in_array('*', $manages, true) ? $allRoles : array_values(array_intersect($allRoles, $manages));
    }

    public function canManageRole(?string $role): bool
    {
        return in_array($role, $this->manageableRoles(), true);
    }

    /**
     * Whether this user may see, edit or delete the other user's account.
     */
    public function canManageUser(User $other): bool
    {
        return $this->hasPermission('users.manage') && $this->canManageRole($other->role);
    }

    /**
     * Name of the management area for this user's role: "Admin" for admins, "HR" for HR (config "panel").
     */
    public function getPanelLabelAttribute(): string
    {
        return config("permissions.roles.{$this->role}.panel.label", 'Management');
    }

    /**
     * First part of every URL for this user's role: admin, hr, manager or employee (config "prefix").
     * SetPanelPrefix makes route() fill it in, so route('employees.index') is /hr/employees for HR.
     */
    public function getUrlPrefixAttribute(): string
    {
        return config("permissions.roles.{$this->role}.prefix", 'employee');
    }

    /**
     * The management area's prefix: "admin" or "hr", null for roles without one (Manager, Employee).
     */
    public function getPanelSlugAttribute(): ?string
    {
        return $this->hasPermission('users.manage') ? $this->url_prefix : null;
    }

    /**
     * Where this user lands after logging in: the dashboard (Admin, HR, Employee), or the employee list
     * for a Manager. The prefix is passed explicitly: right after login, SetPanelPrefix has not run yet.
     */
    public function homeUrl(): string
    {
        $prefix = ['panel' => $this->url_prefix];

        return ! $this->panel_slug && $this->hasPermission('employees.view')
            ? route('employees.index', $prefix)
            : route('dashboard', $prefix);
    }

    public function getPanelIconAttribute(): string
    {
        return config("permissions.roles.{$this->role}.panel.icon", 'bi-sliders');
    }

    /**
     * Bootstrap colour for the role badge.
     */
    public function getRoleColorAttribute(): string
    {
        return ['admin' => 'danger', 'hr' => 'warning', 'manager' => 'primary', 'employee' => 'info'][$this->role] ?? 'secondary';
    }

    public function getRoleLabelAttribute(): string
    {
        return config("permissions.roles.{$this->role}.label", ucfirst((string) $this->role));
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);

        return mb_strtoupper(collect($words)->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode(''));
    }
}
