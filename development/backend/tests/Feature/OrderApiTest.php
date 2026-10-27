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
    
    public function test_checkout_with_multiple_products(): void
    {
        $product1 = Product::create([
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);

        $product2 = Product::create([
            'name' => 'Es Teh',
            'description' => 'Teh dingin',
            'price' => 5000,
            'image_key' => 'product_img_2',
            'is_available' => true,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Pelanggan Test',
            'customer_phone' => '081234567890',
            'customer_address' => 'Alamat untuk testing',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total_amount', '25000.00');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
    }

    
    public function test_checkout_rejects_unavailable_product(): void
    {
        $product = Product::create([
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => false,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Pelanggan Test',
            'customer_phone' => '081234567890',
            'customer_address' => 'Alamat untuk testing',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }
    
    public function test_checkout_rejects_invalid_quantity(): void
    {
        $product = Product::create([
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);

        foreach ([0, -1, 'abc'] as $quantity) {
            $response = $this->postJson('/api/orders', [
                'customer_name' => 'Pelanggan Test',
                'customer_phone' => '081234567890',
                'customer_address' => 'Alamat untuk testing',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                    ],
                ],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors('items.0.quantity');
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_duplicate_product_ids(): void
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
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('items.1.product_id');

        $this->assertDatabaseCount('orders', 0);
    }
    
    public function test_checkout_rolls_back_when_order_item_creation_fails(): void
    {
        $product = Product::create([
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);

        config([
            'app.testing_fail_order_items_insert' => true,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Pelanggan Test',
            'customer_phone' => '081234567890',
            'customer_address' => 'Alamat untuk testing',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertServerError();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

}