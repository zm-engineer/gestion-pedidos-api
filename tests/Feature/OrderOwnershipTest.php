<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_or_cancel_another_users_order(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00, 'stock' => 20]);

        // El usuario A crea un pedido.
        Sanctum::actingAs($userA);
        $orderId = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertStatus(201)->json('data.id');

        // El usuario B no puede verlo ni cancelarlo.
        Sanctum::actingAs($userB);
        $this->getJson("/api/orders/{$orderId}")->assertStatus(403);
        $this->putJson("/api/orders/{$orderId}/cancel")->assertStatus(403);

        // El pedido sigue intacto (pending) tras los intentos de B.
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'pending',
        ]);
    }
}
