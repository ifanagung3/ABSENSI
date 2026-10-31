<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function record(
        Request $request,
        string $action,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
