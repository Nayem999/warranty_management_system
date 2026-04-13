<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'user_type',
        'is_admin',
        'role_id',
        'personal_permissions',
        'status',
        'image',
        'phone',
        'job_title',
        'disable_login',
        'note',
        'address',
        'dob',
        'gender',
        'language',
        'last_online',
        'enable_web_notification',
        'enable_email_notification',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'disable_login' => 'boolean',
        'dob' => 'date',
        'last_online' => 'datetime',
        'enable_web_notification' => 'boolean',
        'enable_email_notification' => 'boolean',
        'personal_permissions' => 'array',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        $backendUrl = rtrim(config('app.backend_url', env('BACKEND_URL', '')), '/');

        return $backendUrl.'/storage/'.$this->image;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function brandAccess(): HasMany
    {
        return $this->hasMany(UserBrandAccess::class);
    }

    public function serviceCenterAccess(): HasMany
    {
        return $this->hasMany(UserServiceCenterAccess::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'created_by');
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class, 'created_by');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'created_by');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'created_by');
    }

    public function assignedWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'assigned_by');
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'wms_user_brand_access')
            ->withPivot('created_by')
            ->withTimestamps();
    }

    public function serviceCenters(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCenter::class, 'wms_user_service_center_access')
            ->withPivot('created_by')
            ->withTimestamps();
    }

    public function isBrandRestricted(): bool
    {
        return ! $this->is_admin && $this->brandAccess()->exists();
    }

    public function accessibleBrandIds(): array
    {
        if ($this->is_admin) {
            return Brand::pluck('id')->toArray();
        }

        return $this->brandAccess()->pluck('brand_id')->toArray();
    }

    public function isServiceCenterRestricted(): bool
    {
        return ! $this->is_admin && $this->serviceCenterAccess()->exists();
    }

    public function accessibleServiceCenterIds(): array
    {
        if ($this->is_admin) {
            return ServiceCenter::pluck('id')->toArray();
        }

        return $this->serviceCenterAccess()->pluck('service_center_id')->toArray();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getPermissionsAttribute(): array
    {
        /* if ($this->is_admin) {
            return [
                'warranties' => ['view', 'create', 'edit', 'delete'],
                'claims' => ['view', 'create', 'edit', 'delete', 'convert_to_wo'],
                'work_orders' => ['view', 'create', 'edit', 'delete', 'assign'],
                'service_centers' => ['view', 'create', 'edit', 'delete'],
                'brands' => ['view', 'create', 'edit', 'delete'],
                'categories' => ['view', 'create', 'edit', 'delete'],
                'couriers' => ['view', 'create', 'edit', 'delete'],
                'users' => ['view', 'create', 'edit', 'delete'],
                'settings' => ['view', 'edit'],
                'reports' => ['view'],
            ];
        } */

        if (! empty($this->personal_permissions)) {
            return $this->personal_permissions;
        }

        return $this->role?->permissions ?? [];
    }

    public function hasPermission(string $module, string $action): bool
    {
        if ($this->is_admin) {
            return true;
        }

        $permissions = $this->permissions;

        return isset($permissions[$module]) && in_array($action, $permissions[$module]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('user_type', ['admin', 'staff']);
    }
}
