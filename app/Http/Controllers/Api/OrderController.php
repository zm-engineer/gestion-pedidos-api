<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Crea un pedido para el usuario autenticado con sus líneas.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        //DB::transaction envuelve todo en una operación atómica: si cualquier línea falla a mitad, Laravel revierte todo automáticamente y no queda un pedido a medias. 
        $order = DB::transaction(function () use ($request) {
            // 1. Crear el pedido del usuario autenticado
            $order = $request->user()->orders()->create([
                'total' => 0,
                'status' => 'pending',
            ]);

            // 2. Crear cada línea copiando el PRECIO ACTUAL del producto
            foreach ($request->validated('items') as $item) {
                $product = Product::findOrFail($item['product_id']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price, // Se congela el precio actual del producto en la línea.
                    'subtotal' => $product->price * $item['quantity'],
                ]);
            }

            // El total lo recalcula el OrderItemObserver al crear cada línea.
            // Disparamos el evento síncrono que descuenta stock; si no alcanza,
            // su excepción revierte toda la transacción.
            OrderCreated::dispatch($order);

            return $order;
        });

        return response()->json($order->refresh()->load('items.product'), 201);
    }
}
