<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(
        string $action,
        ?string $module = null,
        ?string $recordType = null,
        ?string $recordId = null,
        array $oldValues = [],
        array $newValues = [],
        ?User $user = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::create([
            'user_id' => $user?->id ?? Auth::id(),
            'action' => $action,
            'module' => $module,
            'record_type' => $recordType,
            'record_id' => $recordId,
            'old_values' => $oldValues !== [] ? $oldValues : null,
            'new_values' => $newValues !== [] ? $newValues : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public function logLogin(User $user): void
    {
        $this->log('login', 'auth', 'user', (string) $user->id, newValues: ['email' => $user->email]);
    }

    public function logLogout(User $user): void
    {
        $this->log('logout', 'auth', 'user', (string) $user->id, newValues: ['email' => $user->email]);
    }
}
