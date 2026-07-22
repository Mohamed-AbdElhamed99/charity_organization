<?php

namespace App\Http\Middleware;

use App\Enums\ModulePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the admin panel to staff-facing roles. Without this, any
 * authenticated donor could load /admin/dashboard since it previously only
 * required `auth`+`verified`.
 *
 * Intentionally aborts with 404 (not 403 and not a redirect to login) for
 * both guests and non-staff users (e.g. donors), so the existence of the
 * admin panel is never revealed to unauthorized visitors. This middleware
 * replaces the need for a separate `auth` middleware entry on admin routes,
 * since it performs its own authentication check first.
 */
class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->can(ModulePermission::SYSTEM->permission('access_dashboard')),
            404,
        );

        return $next($request);
    }
}
