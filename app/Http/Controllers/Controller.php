<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

abstract class Controller
{
    /**
     * Persist an entry in the audit trail.
     *
     * @param  array<string, mixed>|null  $details
     */
    protected function recordAudit(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $details = null
    ): void {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => Request::ip(),
        ]);
    }
}
