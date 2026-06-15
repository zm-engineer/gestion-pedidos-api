<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Lista los pedidos del usuario autenticado, del más reciente al más antiguo.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection(
            $request->user()->orders()->latest()->get()
        );
    }

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

        return (new OrderResource($order->refresh()->load('items.product')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Devuelve un pedido con sus líneas y productos.
     *
     * La propiedad la verifica el middleware check.order.owner.
     */
    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load('items.product'));
    }

    /**
     * Cancela un pedido. Solo se permite si está en estado 'pending'.
     *
     * No se restaura el stock (fuera de alcance).
     */
    public function cancel(Order $order): OrderResource|JsonResponse
    {
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Solo se pueden cancelar pedidos pendientes.',
            ], 422);
        }

        // El cambio de estado y la devolución de stock van juntos en una
        // transacción: si el listener fallara, no quedaría el pedido cancelado
        // sin haber restaurado el inventario.
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            OrderCancelled::dispatch($order);
        });

        return new OrderResource($order->load('items.product'));
    }
}
