<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSupportAccess
{
    /**
     * Restrict access to support/admin roles.
     */
    public function handle(Request $request, Closure $next)
    {
        $user  = $request->user();
        $level = $user ? strtoupper((string) $user->access_level) : null;

        $allowed = ['ADMIN', 'SUPERADMIN', 'SUPPORT'];

        if (!$user || !in_array($level, $allowed, true)) {
            return response()->json([
                'status'  => false,
                'message' => 'Forbidden. Support access required.',
            ], 403);
        }

        return $next($request);
    }
}
