<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(
        ?User $user,
        string $action,
        string $description,
        ?string $targetType = null,
        int|string|null $targetId = null,
        array $metadata = [],
        ?Request $request = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id,
            'role' => $user?->role,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
        ]);
    }
}
