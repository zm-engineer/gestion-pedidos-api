<?php

namespace App\Listeners;

use App\Events\OrderCancelled;

class RestoreStock
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * Síncrono (no implementa ShouldQueue) para correr dentro de la misma
     * transacción del controlador: devuelve al inventario las unidades que
     * el pedido había descontado, de forma simétrica a DiscountStock.
     */
    public function handle(OrderCancelled $event): void
    {
        foreach ($event->order->items()->with('product')->get() as $item) {
            $item->product->increment('stock', $item->quantity);
        }
    }
}
