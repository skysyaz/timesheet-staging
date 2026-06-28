<?php

namespace App\Models;

use App\Models\Concerns\LogsAuditableChanges;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use LogsAuditableChanges;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'color',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('assigned_role')
            ->withTimestamps();
    }

    public function approvalLogs()
    {
        return $this->hasMany(ApprovalLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isProjectManager(): bool
    {
        return $this->role === 'project_manager';
    }

    public function isProjectDirector(): bool
    {
        return $this->role === 'project_director';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isApprover(): bool
    {
        return in_array($this->role, ['admin', 'project_manager', 'project_director']);
    }

    public function canApproveAsPm(): bool
    {
        return in_array($this->role, ['admin', 'project_manager']);
    }

    public function canApproveAsPd(): bool
    {
        return in_array($this->role, ['admin', 'project_director']);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [
            'employee',
            'project_manager',
            'project_director',
            'admin',
        ], true);
    }

    /**
     * @return list<string>
     */
    protected function auditableAttributes(): array
    {
        return ['name', 'email', 'role', 'color'];
    }
}
