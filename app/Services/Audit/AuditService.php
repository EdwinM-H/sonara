<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request as RequestFacade;

class AuditService
{
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        array $data = [],
    ): AuditLog {
        $user = auth()->user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => RequestFacade::ip(),
            'user_agent' => mb_substr(RequestFacade::userAgent() ?? '', 0, 255),
        ]);
    }
}