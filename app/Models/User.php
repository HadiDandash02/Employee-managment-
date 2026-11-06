<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Search;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
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
        'roles',
        'employee_id',
        'is_active',
    ];

    /**
     * A user can have several roles (users.roles, e.g. ["manager", "employee"]).
     * users.role is the MAIN role: the highest of them (Admin > HR > Manager > Employee, the order in
     * config/permissions.php). It decides the URL prefix (/manager/...) and the dashboard.
     * Kept in sync here, so code that only sets "role" (factories, seeders) still works.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('roles') && $user->roles) {
                $user->roles = self::sortRoles($user->roles);
                $user->role = $user->roles[0];
            } elseif ($user->isDirty('role') || ! $user->roles) {
                $user->roles = [$user->role];
            }
        });
    }

    /**
     * Role keys in the order of config/permissions.php (highest first), without duplicates or unknown roles.
     *
     * @param  array<int, string>  $roles
     * @return list<string>
     */
    public static function sortRoles(array $roles): array
    {
        return array_values(array_intersect(array_keys(config('permissions.roles', [])), $roles));
    }

    /**
     * Every role of this user, highest first.
     *
     * @return list<string>
     */
    public function allRoles(): array
    {
        return self::sortRoles($this->roles ?: [$this->role]) ?: [$this->role];
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->allRoles(), true);
    }

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
            'roles' => 'array',
        ];
    }

    /**
     * The employee record this login belongs to (users.employee_id), used by the Employee role.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Search every column of the users list: # (id), name, username, email, role (e.g. "manager"),
     * the linked employee's name and the created date (dd/mm/yyyy or mm/yyyy). Every word must match.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $roles = config('permissions.roles');

        foreach (Search::words($term) as $word) {
            $like = Search::like($word);
            $date = Search::date($word);
            $matchingRoles = array_keys(array_filter($roles, fn (array $role) => stripos($role['label'], $word) !== false));

            $query->where(function (Builder $q) use ($word, $like, $date, $matchingRoles) {
                $q->where('users.name', 'like', $like)
                    ->orWhere('users.username', 'like', $like)
                    ->orWhere('users.email', 'like', $like)
                    ->orWhereHas('employee', fn (Builder $e) => $e->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like));

                // any of the user's roles, not only the main one
                foreach ($matchingRoles as $role) {
                    $q->orWhere('users.roles', 'like', '%"'.$role.'"%');
                }

                if (ctype_digit($word)) {
                    $q->orWhere('users.id', (int) $word);
                }

                if ($date) {
                    $q->orWhere('users.created_at', 'like', $date.'%');
                }
            });
        }

        return $query;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Whether ANY of this user's roles grants the given permission (see config/permissions.php):
     * an Employee + Manager has the permissions of both.
     */
    public function hasPermission(string $permission): bool
    {
        foreach ($this->allRoles() as $role) {
            $granted = config("permissions.roles.{$role}.permissions", []);

            if (in_array('*', $granted, true) || in_array($permission, $granted, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Role keys whose accounts this user may see and manage (config "manages" of all their roles).
     *
     * @return list<string>
     */
    public function manageableRoles(): array
    {
        $allRoles = array_keys(config('permissions.roles', []));
        $manages = collect($this->allRoles())->flatMap(fn (string $role) => config("permissions.roles.{$role}.manages", []))->all();

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
        // every role of the other account must be one this user manages (HR never manages anyone who is also Admin)
        return $this->hasPermission('users.manage')
            && collect($other->allRoles())->every(fn (string $role) => $this->canManageRole($role));
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

    /**
     * Labels of all roles, highest first: ['Manager', 'Employee'].
     *
     * @return list<string>
     */
    public function getRoleLabelsAttribute(): array
    {
        return array_map(fn (string $role) => config("permissions.roles.{$role}.label", ucfirst($role)), $this->allRoles());
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);

        return mb_strtoupper(collect($words)->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode(''));
    }
}
