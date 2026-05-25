<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLkhMonitoringAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canAccessLkhMonitoring()) {
            abort(403, 'Anda tidak memiliki akses ke halaman monitoring LKH.');
        }

        return $next($request);
    }
}
