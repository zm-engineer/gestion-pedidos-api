<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Validation\ValidationException;

class DiscountStock
{

    public function handle(OrderCreated $event): void
    {
        foreach ($event->order->items()->with('product')->get() as $item) {
            $product = $item->product;

            if ($product->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'items' => ["Stock insuficiente para el producto «{$product->name}»."],
                ]);
            }

            $product->decrement('stock', $item->quantity);
        }
    }
}
