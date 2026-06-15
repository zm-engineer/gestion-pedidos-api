<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrderOwner
{
    /**
     * Asegura que el pedido de la ruta pertenece al usuario autenticado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $order = $request->route('order');

        if ($order->user_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para acceder a este pedido.');
        }

        return $next($request);
    }
}
