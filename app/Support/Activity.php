<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class Activity
{
    public static function log(string $action, ?string $description = null, array $metadata = [], ?int $schoolId = null): void
    {
        $user = Auth::user();

        ActivityLog::create([
            'school_id' => $schoolId ?? $user?->school_id,
            'user_id' => $user?->id,
            'role' => $user?->role,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()?->ip(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
