<?php

namespace BezhanSalleh\FilamentShield\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncShieldTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (app('filament')->hasTenancy()) {
            setPermissionsTeamId(app('filament')->getTenant());
        }

        return $next($request);
    }
}
