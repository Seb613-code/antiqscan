<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAntiqscanAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $password = (string) config('antiqscan.access_password');

        abort_if($password === '', 503, 'AntiQScan access password is not configured.');

        if (! $request->session()->get('antiqscan_access_granted', false)) {
            return redirect()->route('access.create');
        }

        return $next($request);
    }
}
