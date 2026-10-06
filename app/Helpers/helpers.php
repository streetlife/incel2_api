<?php

  if (!function_exists('secured_path')) {
    function secured_path($path)
    {
        return url($path);
    }
    
}

if (!function_exists('audit_log')) {
    /**
     * Record an entry in the audit log for the support team.
     *
     * @param  string       $module       e.g. flight, hotel, tour, visa, booking, payment, auth
     * @param  string       $event        e.g. created, updated, search, login, cancelled
     * @param  string|null  $action       more specific action, e.g. flight.book
     * @param  string|null  $description  human readable summary
     * @param  string|null  $subjectType  model/record type being acted upon
     * @param  string|null  $subjectId    identifier of the record being acted upon
     * @param  string|null  $bookingCode  associated booking code (if any)
     * @param  array|null   $oldValues    previous values (for updates/deletes)
     * @param  array|null   $newValues    new values (for creates/updates)
     * @param  array|null   $metadata     any additional context
     * @param  string|null  $status       e.g. success, failure, pending
     */
    function audit_log(
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
    ) {
        try {
            return app(\App\Services\AuditLogService::class)->record(
                $module,
                $event,
                $action,
                $description,
                $subjectType,
                $subjectId,
                $bookingCode,
                $oldValues,
                $newValues,
                $metadata,
                $status,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Unable to write audit log', [
                'module' => $module,
                'event'  => $event,
                'error'  => $e->getMessage(),
            ]);
            return null;
        }
    }
}
