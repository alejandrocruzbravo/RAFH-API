<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class CleanExpiredTokens
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log para debug - verificar que el middleware se ejecuta
        \Log::info('CleanExpiredTokens middleware ejecutándose', [
            'url' => $request->url(),
            'method' => $request->method(),
            'timestamp' => now()
        ]);
        
        // Solo limpiar tokens expirados ocasionalmente (cada 5 minutos máximo)
        if ($this->shouldCleanTokens()) {
            \Log::info('Iniciando limpieza de tokens desde middleware');
            $this->cleanExpiredTokens();
        } else {
            \Log::info('Limpieza de tokens omitida - muy reciente');
        }
        
        return $next($request);
    }

    /**
     * Determina si es necesario limpiar tokens expirados
     */
    private function shouldCleanTokens(): bool
    {
        $cacheKey = 'last_token_cleanup';
        $lastCleanup = Cache::get($cacheKey);
        
        // Si no hay registro de la última limpieza o han pasado más de 5 minutos
        if (!$lastCleanup || now()->diffInMinutes($lastCleanup) >= 5) {
            Cache::put($cacheKey, now(), 60); // Cache por 1 hora
            return true;
        }
        
        return false;
    }

    /**
     * Elimina todos los tokens expirados de la base de datos
     */
    private function cleanExpiredTokens(): void
    {
        try {
            $deletedCount = PersonalAccessToken::where('expires_at', '<', now())->delete();
            
            // Log solo si se eliminaron tokens
            if ($deletedCount > 0) {
                \Log::info("Se eliminaron {$deletedCount} tokens expirados automáticamente.");
            }
        } catch (\Exception $e) {
            \Log::error("Error al limpiar tokens expirados: " . $e->getMessage());
        }
    }
}

