<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleAccessMiddleware
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Bu islem icin yetkiniz yok.');
        }

        if (! $user->canAccessModule($moduleKey)) {
            abort(403, 'Bu modul icin yetkiniz yok.');
        }

        return $next($request);
    }
}
