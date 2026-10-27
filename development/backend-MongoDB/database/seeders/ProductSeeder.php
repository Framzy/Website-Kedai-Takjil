<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use MongoDB\BSON\Decimal128;
use RuntimeException;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/products.json');

        if (!File::exists($path)) {
            throw new RuntimeException(
                "File products.json tidak ditemukan: {$path}"
            );
        }

        $products = json_decode(
            File::get($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $collection = DB::connection('mongodb')->getCollection('products');

        foreach ($products as $product) {
            $collection->updateOne(
                ['name' => $product['name']],
                ['$set' => [
                    'id' => (int) $product['id'],
                    'name' => $product['name'],
                    'price' => new Decimal128(
                        number_format((float) $product['price'], 2, '.', '')
                    ),                    'image_key' => $product['imageKey'],
                    'description' => $product['description'] ?? null,
                    'is_available' => $product['is_available'] ?? false,
                ]],
                ['upsert' => true]
            );
        }
    }
}