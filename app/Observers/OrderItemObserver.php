<?php

namespace App\Observers;

use App\Models\OrderItem;

class OrderItemObserver
{
  
    public function created(OrderItem $orderItem): void
    {
        $this->recalculateOrderTotal($orderItem);
    }

    public function updated(OrderItem $orderItem): void
    {
        $this->recalculateOrderTotal($orderItem);
    }

    public function deleted(OrderItem $orderItem): void
    {
        $this->recalculateOrderTotal($orderItem);
    }

    /**
     * Recalcula orders.total como la suma de los subtotales de sus líneas.
     */
    protected function recalculateOrderTotal(OrderItem $orderItem): void
    {
        $order = $orderItem->order;

        $order->update(['total' => $order->items()->sum('subtotal')]);
    }
}
