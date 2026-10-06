<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function record(
        string $module,
        string $event,
        ?string $action = null,
        ?string $description = null,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $bookingCode = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?string $status = null,
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create([
            'module'       => $module,
            'event'        => $event,
            'action'       => $action,
            'description'  => $description,
            'usercode'     => $user->usercode ?? null,
            'user_name'    => $user->full_names ?? null,
            'user_email'   => $user->email_address ?? null,
            'access_level' => $user->access_level ?? null,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'booking_code' => $bookingCode,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'url'          => request()->fullUrl(),
            'method'       => request()->method(),
            'old_values'   => $oldValues,
            'new_values'   => $newValues,
            'metadata'     => $metadata,
            'status'       => $status,
        ]);
    }
}
