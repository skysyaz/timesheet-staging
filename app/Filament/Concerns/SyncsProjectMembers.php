<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait SyncsProjectMembers
{
    /**
     * @param  list<array{user_id?: int|string|null, assigned_role?: string|null}>|mixed  $assignments
     * @return array<int, array{assigned_role: string}>
     */
    protected function resolveMemberAssignments(mixed $assignments): array
    {
        $rows = array_values(array_filter(
            Arr::wrap($assignments),
            fn (mixed $row): bool => is_array($row) && filled($row['user_id'] ?? null),
        ));

        if ($rows === []) {
            throw ValidationException::withMessages([
                'data.member_assignments' => 'Add at least one project member.',
            ]);
        }

        $sync = [];
        $userIds = [];

        foreach ($rows as $index => $row) {
            $userId = (int) $row['user_id'];
            $role = trim((string) ($row['assigned_role'] ?? ''));

            if ($role === '') {
                throw ValidationException::withMessages([
                    "data.member_assignments.{$index}.assigned_role" => 'Enter a role for this member.',
                ]);
            }

            if (in_array($userId, $userIds, true)) {
                throw ValidationException::withMessages([
                    "data.member_assignments.{$index}.user_id" => 'Each member can only be assigned once.',
                ]);
            }

            $userIds[] = $userId;
            $sync[$userId] = ['assigned_role' => $role];
        }

        $existingIds = User::query()
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->all();

        $missingIds = array_values(array_diff($userIds, $existingIds));

        if ($missingIds !== []) {
            throw ValidationException::withMessages([
                'data.member_assignments' => 'These user IDs do not exist in the system: ' . implode(', ', $missingIds) . '.',
            ]);
        }

        return $sync;
    }
}
