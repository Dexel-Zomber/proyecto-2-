<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege rutas por rol sin depender de que cada controlador recuerde
 * hacer la validación manualmente (esa falta de middleware fue lo que
 * dejaba /admin/alerts/*  sin ninguna protección).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userId = $request->session()->get('user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return redirect('/login');
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        $request->attributes->set('authUser', $user);

        return $next($request);
    }
}
