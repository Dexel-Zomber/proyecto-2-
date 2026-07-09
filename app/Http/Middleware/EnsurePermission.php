<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->attributes->get('authUser');

        if (! $user) {
            $userId = $request->session()->get('user_id');
            $user = $userId ? User::find($userId) : null;
        }

        if (! app(PermissionService::class)->can($user, $permission)) {
            abort(403, 'No tienes el permiso requerido para realizar esta accion.');
        }

        return $next($request);
    }
}
