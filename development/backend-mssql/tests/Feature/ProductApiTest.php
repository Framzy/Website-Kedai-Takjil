<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_endpoint_returns_available_products(): void
    {
        Product::create([
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);

        $response = $this->getJson('/api/products');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Es Buah')
            ->assertJsonPath('data.0.image_key', 'product_img_1');
    }

    public function test_products_endpoint_returns_available_products_by_id(): void
    {
        $product = Product::create([
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);
        
        $response = $this->getJson('/api/products/' . $product->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Es Buah');
    }
    public function test_products_endpoint_excludes_unavailable_products(): void
    {
        Product::create([
            'name' => 'Es Buah',
            'description' => 'Minuman segar',
            'price' => 10000,
            'image_key' => 'product_img_1',
            'is_available' => true,
        ]);

        Product::create([
            'name' => 'Es Teh',
            'description' => 'Teh dingin',
            'price' => 5000,
            'image_key' => 'product_img_9',
            'is_available' => false,
        ]);

        $response = $this->getJson('/api/products');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Es Buah');
    }
}