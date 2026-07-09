<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'manage_users',
            'manage_courses',
            'manage_subjects',
            'manage_enrollments',
            'manage_settings',
            'manage_alerts',
            'view_reports',
            'export_xml',
            'view_audit',
        ],
        'teacher' => [
            'manage_own_scores',
            'manage_own_alerts',
            'view_own_subjects',
        ],
        'student' => [
            'view_own_scores',
            'view_own_alerts',
            'use_ai_assistant',
        ],
    ];

    public function rolePermissions(?User $user): array
    {
        return self::ROLE_PERMISSIONS[$user?->role] ?? [];
    }

    public function can(?User $user, string $permission): bool
    {
        return in_array($permission, $this->rolePermissions($user), true);
    }
}
