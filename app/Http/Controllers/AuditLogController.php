<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    /**
     * List audit logs with optional filters.
     */
    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        if ($event = $request->query('event')) {
            $query->where('event', $event);
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        if ($usercode = $request->query('usercode')) {
            $query->where('usercode', $usercode);
        }

        if ($bookingCode = $request->query('booking_code')) {
            $query->where('booking_code', $bookingCode);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%")
                    ->orWhere('usercode', 'like', "%{$search}%")
                    ->orWhere('booking_code', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 50), 200);

        $logs = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Audit logs fetched successfully',
            'data'    => $logs,
        ]);
    }

    /**
     * Show a single audit log entry.
     */
    public function show($id)
    {
        $log = AuditLog::find($id);

        if (!$log) {
            return response()->json([
                'status'  => false,
                'message' => 'Audit log not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Audit log fetched successfully',
            'data'    => $log,
        ]);
    }

    /**
     * All audit entries for a given booking.
     */
    public function byBooking($bookingCode)
    {
        $logs = AuditLog::where('booking_code', $bookingCode)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Booking audit trail fetched successfully',
            'data'    => $logs,
        ]);
    }

    /**
     * All audit entries performed by a given user.
     */
    public function byUser($usercode)
    {
        $logs = AuditLog::where('usercode', $usercode)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'User audit trail fetched successfully',
            'data'    => $logs,
        ]);
    }

    /**
     * Aggregate statistics over the audit log.
     */
    public function stats(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $byModule = AuditLog::select('module', DB::raw('count(*) as total'))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->groupBy('module')
            ->orderByDesc('total')
            ->get();

        $byEvent = AuditLog::select('event', DB::raw('count(*) as total'))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->groupBy('event')
            ->orderByDesc('total')
            ->get();

        $totals = [
            'total'      => AuditLog::count(),
            'today'      => AuditLog::whereDate('created_at', today())->count(),
            'successful' => AuditLog::where('status', 'success')->count(),
            'failed'     => AuditLog::where('status', 'failure')->count(),
        ];

        return response()->json([
            'status' => true,
            'message' => 'Audit statistics fetched successfully',
            'data'    => [
                'totals'   => $totals,
                'byModule' => $byModule,
                'byEvent'  => $byEvent,
            ],
        ]);
    }

    /**
     * Export filtered audit logs as CSV.
     */
    public function export(Request $request)
    {
        $query = AuditLog::query();

        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        if ($event = $request->query('event')) {
            $query->where('event', $event);
        }

        if ($usercode = $request->query('usercode')) {
            $query->where('usercode', $usercode);
        }

        if ($bookingCode = $request->query('booking_code')) {
            $query->where('booking_code', $bookingCode);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->orderByDesc('id')->limit(10000)->get();

        $filename = 'audit_logs_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = [
            'ID', 'Date', 'Module', 'Event', 'Action', 'Description',
            'Usercode', 'User', 'Email', 'Role', 'Subject', 'Booking Code',
            'IP Address', 'URL', 'Method', 'Old Values', 'New Values', 'Status',
        ];

        $callback = function () use ($logs, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at,
                    $log->module,
                    $log->event,
                    $log->action,
                    $log->description,
                    $log->usercode,
                    $log->user_name,
                    $log->user_email,
                    $log->access_level,
                    $log->subject_type . ':' . $log->subject_id,
                    $log->booking_code,
                    $log->ip_address,
                    $log->url,
                    $log->method,
                    json_encode($log->old_values),
                    json_encode($log->new_values),
                    $log->status,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
