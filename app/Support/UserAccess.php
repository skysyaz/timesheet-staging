<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserAccess
{
  /**
   * @return array<string, string>
   */
  public static function roleLabels(): array
  {
    return [
      'employee' => 'Employee',
      'project_manager' => 'Project Manager',
      'program_manager' => 'Program Manager',
      'project_admin' => 'Project Admin',
      'admin' => 'Admin',
    ];
  }

  public static function canManageUsers(?User $actor): bool
  {
    return $actor?->canManageUsers() ?? false;
  }

  public static function scopeVisibleUsers(Builder $query, User $actor): Builder
  {
    if ($actor->isAdmin()) {
      return $query;
    }

    if ($actor->isProjectAdmin()) {
      return $query->where('role', '!=', 'admin');
    }

    return $query->whereRaw('0 = 1');
  }

  /**
   * Who an actor may pick from when assigning team members to a project.
   *
   * This is deliberately separate from {@see scopeVisibleUsers()}, which
   * governs the Administration -> Users resource. Project Managers and
   * Program Managers cannot manage users there, but they can create and
   * edit their own projects and must be able to see the employee directory
   * to staff them.
   */
  public static function scopeAssignableProjectMembers(Builder $query, ?User $actor): Builder
  {
    if (! $actor) {
      return $query->whereRaw('0 = 1');
    }

    if ($actor->isAdmin() || $actor->isProjectAdmin() || $actor->isProjectManager() || $actor->isProgramManager()) {
      return $query;
    }

    return $query->whereRaw('0 = 1');
  }

  public static function canViewUser(User $actor, User $target): bool
  {
    if ($actor->isAdmin()) {
      return true;
    }

    if ($actor->isProjectAdmin()) {
      return ! $target->isAdmin();
    }

    return false;
  }

  public static function canEditUser(User $actor, User $target): bool
  {
    return self::canViewUser($actor, $target);
  }

  public static function canDeleteUser(User $actor, User $target): bool
  {
    return self::canViewUser($actor, $target);
  }

  /**
   * @return array<string, string>
   */
  public static function assignableRoleOptions(?User $actor): array
  {
    $labels = self::roleLabels();

    if ($actor?->isAdmin()) {
      return $labels;
    }

    if ($actor?->isProjectAdmin()) {
      return collect($labels)
        ->except('admin')
        ->all();
    }

    return [];
  }

  public static function assertAssignableRole(User $actor, string $role): void
  {
    $allowed = array_keys(self::assignableRoleOptions($actor));

    if (! in_array($role, $allowed, true)) {
      throw \Illuminate\Validation\ValidationException::withMessages([
        'role' => 'You are not allowed to assign this role.',
      ]);
    }
  }
}
