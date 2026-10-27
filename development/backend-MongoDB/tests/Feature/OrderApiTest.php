<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    private const TEST_DATABASE = 'kedai_takjil_mongodb_test';
    private const TEST_PRODUCT_IDS = [9101, 9102];
    private const TEST_CUSTOMER = 'Pelanggan Test MongoDB';

    private function collection(string $name)
    {
        return DB::connection('mongodb')->getCollection($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $databaseName = config('database.connections.mongodb.database');

        if ($databaseName !== self::TEST_DATABASE) {
            $this->fail(
                'Test dihentikan: koneksi bukan database testing.'
            );
        }

        $this->cleanTestData();
    }

    protected function tearDown(): void
    {
        if (
            config('database.connections.mongodb.database')
            === self::TEST_DATABASE
        ) {
            $this->cleanTestData();
        }

        parent::tearDown();
    }

    private function cleanTestData(): void
    {
        $orders = $this->collection('orders');
        $orderItems = $this->collection('order_items');
        $products = $this->collection('products');

        $testOrders = $orders->find([
            'customer_name' => self::TEST_CUSTOMER,
        ])->toArray();

        $orderIds = array_map(
            fn ($order) => $order->_id,
            $testOrders
        );

        if ($orderIds !== []) {
            $orderItems->deleteMany([
                'order_id' => ['$in' => $orderIds],
            ]);
        }

        $orders->deleteMany([
            'customer_name' => self::TEST_CUSTOMER,
        ]);

        $orderItems->deleteMany([
            'product_id' => ['$in' => self::TEST_PRODUCT_IDS],
        ]);

        $products->deleteMany([
            'id' => ['$in' => self::TEST_PRODUCT_IDS],
        ]);
    }

    private function createTestProduct(
        int $id = 9101,
        int $price = 10000
    ): void {
        $this->collection('products')->insertOne([
            'id' => $id,
            'name' => 'Es Buah Test',
            'description' => 'Produk khusus testing',
            'price' => (string) $price,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);
    }

    private function checkoutPayload(array $items): array
    {
        return [
            'customer_name' => self::TEST_CUSTOMER,
            'customer_phone' => '081234567890',
            'customer_address' => 'Alamat khusus testing',
            'items' => $items,
        ];
    }

    public function test_valid_checkout_creates_order_and_order_items(): void
    {
        $this->createTestProduct();

        $response = $this->postJson(
            '/api/orders',
            $this->checkoutPayload([
                [
                    'product_id' => 9101,
                    'quantity' => 2,
                ],
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Pesanan berhasil dibuat.')
            ->assertJsonPath('data.total_amount', '20000.00')
            ->assertJsonPath('data.status', 'pending');

        $orderId = new ObjectId($response->json('data.id'));

        $order = $this->collection('orders')->findOne([
            '_id' => $orderId,
        ]);

        $this->assertNotNull($order);
        $this->assertSame(self::TEST_CUSTOMER, $order->customer_name);
        $this->assertSame('pending', $order->status);
        $this->assertSame('20000.00', (string) $order->total_amount);

        $items = $this->collection('order_items')->find([
            'order_id' => $orderId,
        ])->toArray();

        $this->assertCount(1, $items);
        $this->assertSame(9101, $items[0]->product_id);
        $this->assertSame(2, $items[0]->quantity);
        $this->assertSame('10000.00', (string) $items[0]->unit_price);
        $this->assertSame('20000.00', (string) $items[0]->subtotal);
    }

    public function test_checkout_rejects_empty_items(): void
    {
        $response = $this->postJson(
            '/api/orders',
            $this->checkoutPayload([])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertSame(
            0,
            $this->collection('orders')->countDocuments([
                'customer_name' => self::TEST_CUSTOMER,
            ])
        );
    }

    public function test_checkout_rejects_nonexistent_product(): void
    {
        $response = $this->postJson(
            '/api/orders',
            $this->checkoutPayload([
                [
                    'product_id' => 919999,
                    'quantity' => 1,
                ],
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertSame(
            0,
            $this->collection('orders')->countDocuments([
                'customer_name' => self::TEST_CUSTOMER,
            ])
        );
    }

    public function test_checkout_calculates_total_using_database_price(): void
    {
        $this->createTestProduct();

        $response = $this->postJson(
            '/api/orders',
            $this->checkoutPayload([
                [
                    'product_id' => 9101,
                    'quantity' => 2,
                    'price' => 1,
                    'subtotal' => 2,
                ],
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.total_amount', '20000.00');

        $orderId = new ObjectId($response->json('data.id'));

        $item = $this->collection('order_items')->findOne([
            'order_id' => $orderId,
            'product_id' => 9101,
        ]);

        $this->assertNotNull($item);
        $this->assertSame('10000.00', (string) $item->unit_price);
        $this->assertSame('20000.00', (string) $item->subtotal);
    }
    
    public function test_checkout_rolls_back_when_order_items_insert_fails(): void
    {
        $this->createTestProduct();

        // Aktifkan simulasi kegagalan hanya untuk test ini.
        config([
            'app.testing_fail_order_items_insert' => true,
        ]);

        $response = $this->postJson(
            '/api/orders',
            $this->checkoutPayload([
                [
                    'product_id' => 9101,
                    'quantity' => 2,
                ],
            ])
        );

        $response->assertStatus(500);

        // Order harus ikut dibatalkan oleh transaksi.
        $this->assertSame(
            0,
            $this->collection('orders')->countDocuments([
                'customer_name' => self::TEST_CUSTOMER,
            ])
        );

        // Tidak boleh ada item yatim dari checkout yang gagal.
        $this->assertSame(
            0,
            $this->collection('order_items')->countDocuments([
                'product_id' => 9101,
            ])
        );
    }
    public function test_checkout_with_multiple_products(): void
    {
        $this->createTestProduct(9101, 10000);
        $this->createTestProduct(9102, 15000);

        $response = $this->postJson(
            '/api/orders',
            $this->checkoutPayload([
                [
                    'product_id' => 9101,
                    'quantity' => 2,
                ],
                [
                    'product_id' => 9102,
                    'quantity' => 1,
                ],
            ])
        );

        $response->assertStatus(201);

        $this->assertSame(
            '35000.00',
            $response->json('data.total_amount')
        );

        $this->assertSame(
            2,
            $this->collection('order_items')->countDocuments([
                'product_id' => ['$in' => [9101, 9102]],
            ])
        );
    }

    public function test_checkout_rejects_unavailable_product(): void
    {
        $this->createTestProduct(9101, 10000);

        $this->collection('products')->updateOne(
            ['id' => 9101],
            ['$set' => ['is_available' => false]]
        );

        $response = $this->postJson(
            '/api/orders',
            $this->checkoutPayload([
                [
                    'product_id' => 9101,
                    'quantity' => 1,
                ],
            ])
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertSame(
            0,
            $this->collection('orders')->countDocuments([
                'customer_name' => self::TEST_CUSTOMER,
            ])
        );
    }

    public function test_checkout_rejects_invalid_quantity(): void
    {
        $this->createTestProduct(9101, 10000);

        foreach ([0, -1, 'abc'] as $quantity) {
            $response = $this->postJson(
                '/api/orders',
                $this->checkoutPayload([
                    [
                        'product_id' => 9101,
                        'quantity' => $quantity,
                    ],
                ])
            );

            $response->assertStatus(422)
                ->assertJsonValidationErrors('items.0.quantity');
        }

        $this->assertSame(
            0,
            $this->collection('orders')->countDocuments([
                'customer_name' => self::TEST_CUSTOMER,
            ])
        );
    }

    public function test_checkout_rejects_duplicate_product_ids(): void
    {
        $this->createTestProduct(9101, 10000);

        $response = $this->postJson(
            '/api/orders',
            $this->checkoutPayload([
                ['product_id' => 9101, 'quantity' => 1],
                ['product_id' => 9101, 'quantity' => 2],
            ])
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items.1.product_id');

        $this->assertSame(
            0,
            $this->collection('orders')->countDocuments([
                'customer_name' => self::TEST_CUSTOMER,
            ])
        );
    }

}