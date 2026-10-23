<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_checkout_creates_order_and_order_items(): void
    {
        $product = Product::create([
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Pelanggan Test',
            'customer_phone' => '081234567890',
            'customer_address' => 'Alamat untuk testing',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);
        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Pesanan berhasil dibuat.')
            ->assertJsonPath('data.total_amount', '20000.00')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Pelanggan Test',
            'customer_phone' => '081234567890',
            'total_amount' => 20000,
            'status' => 'pending',
        ]);

        $order = Order::with('items')->firstOrFail();

        $this->assertCount(1, $order->items);
        $this->assertSame($product->id, $order->items->first()->product_id);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertEquals(10000, $order->items->first()->unit_price);
        $this->assertEquals(20000, $order->items->first()->subtotal);
    }

    
    public function test_checkout_rejects_empty_items(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Pelanggan Test',
            'customer_phone' => '081234567890',
            'customer_address' => 'Alamat untuk testing',
            'items' => [],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_nonexistent_product(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Pelanggan Test',
            'customer_phone' => '081234567890',
            'customer_address' => 'Alamat untuk testing',
            'items' => [
                [
                    'product_id' => 999999,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.product_id');

        $this->assertDatabaseCount('orders', 0);
    }

    
    public function test_checkout_calculates_total_using_database_price(): void
    {
        $product = Product::create([
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Pelanggan Test',
            'customer_phone' => '081234567890',
            'customer_address' => 'Alamat untuk testing',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => 1,
                    'subtotal' => 2,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.total_amount', '20000.00');

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'subtotal' => 20000,
        ]);
    }

}