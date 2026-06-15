<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_an_order_restores_product_stock(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['price' => 10.00, 'stock' => 20]);

        // Crear el pedido: el stock baja de 20 a 16.
        $create = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4],
            ],
        ]);
        $create->assertStatus(201);
        $orderId = $create->json('data.id');
        $this->assertEquals(16, $product->fresh()->stock);

        // Cancelar el pedido.
        $cancel = $this->putJson("/api/orders/{$orderId}/cancel");
        $cancel->assertStatus(200);

        // El estado queda 'cancelled' y el stock vuelve a su valor original.
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'cancelled',
        ]);
        $this->assertEquals(20, $product->fresh()->stock);
    }
}
