<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    private function productsCollection()
    {
        return DB::connection('mongodb')->getCollection('products');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan test hanya memakai database khusus testing.
        $databaseName = config('database.connections.mongodb.database');

        if ($databaseName !== 'kedai_takjil_mongodb_test') {
            $this->fail(
                'Test dihentikan: koneksi MongoDB bukan database testing.'
            );
        }

        // Hapus hanya ID yang dicadangkan untuk test ini.
        $this->productsCollection()->deleteMany([
            'id' => ['$in' => [9001, 9002]],
        ]);
    }

    protected function tearDown(): void
    {
        if (
            config('database.connections.mongodb.database')
            === 'kedai_takjil_mongodb_test'
        ) {
            $this->productsCollection()->deleteMany([
                'id' => ['$in' => [9001, 9002]],
            ]);
        }

        parent::tearDown();
    }

    public function test_products_endpoint_returns_available_products(): void
    {
        $this->productsCollection()->insertOne([
            'id' => 9001,
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);

        $response = $this->getJson('/api/products');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => 9001,
                'name' => 'Es Buah',
                'image_key' => 'product_img_1',
                'sold' => 0,
            ]);
    }

    public function test_products_endpoint_excludes_unavailable_products(): void
    {
        $this->productsCollection()->insertMany([
            [
                'id' => 9001,
                'name' => 'Es Buah',
                'description' => 'Minuman segar',
                'price' => 10000,
                'image_key' => 'product_img_1',
                'is_available' => true,
            ],
            [
                'id' => 9002,
                'name' => 'Es Teh',
                'description' => 'Teh dingin',
                'price' => 5000,
                'image_key' => 'product_img_9',
                'is_available' => false,
            ],
        ]);

        $response = $this->getJson('/api/products');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => 9001,
                'name' => 'Es Buah',
            ])
            ->assertJsonMissing([
                'name' => 'Es Teh',
            ]);
    }
}