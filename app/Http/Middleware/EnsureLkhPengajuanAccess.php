<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLkhPengajuanAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canAccessLkhPengajuan()) {
            abort(403, 'Anda tidak memiliki akses ke halaman pengajuan LKH.');
        }

        return $next($request);
    }
}
