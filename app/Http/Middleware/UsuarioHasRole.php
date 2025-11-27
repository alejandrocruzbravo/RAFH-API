<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsuarioHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,string ...$requiredRole): Response
    {

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Accede al rol desde la relación belongsTo
        $rol = $user->rol; // Esto devuelve el modelo Rol asociado
        $autorizado = !empty($requiredRole) && in_array($rol->rol_nombre,$requiredRole,true);
        if (!$rol || !$autorizado) {
            return response()->json([
                'message' => 'Acceso denegado!'
            ], 403);
        }

        return $next($request);
    }
}
