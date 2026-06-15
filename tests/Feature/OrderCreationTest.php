<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_order_and_stock_is_discounted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $productA = Product::factory()->create(['price' => 10.00, 'stock' => 20]);
        $productB = Product::factory()->create(['price' => 5.50, 'stock' => 8]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 3], // 30.00
                ['product_id' => $productB->id, 'quantity' => 2], // 11.00
            ],
        ]);

        $response->assertStatus(201);

        // El pedido existe en BD con el total correcto (41.00).
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => '41.00',
        ]);

        // El stock se descontó en cada producto.
        $this->assertEquals(17, $productA->fresh()->stock);
        $this->assertEquals(6, $productB->fresh()->stock);
    }

    public function test_order_with_insufficient_stock_returns_422_and_keeps_state_intact(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['price' => 10.00, 'stock' => 2]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5], // excede el stock
            ],
        ]);

        $response->assertStatus(422);

        // No se creó ningún pedido y el stock quedó intacto.
        $this->assertDatabaseCount('orders', 0);
        $this->assertEquals(2, $product->fresh()->stock);
    }
}
