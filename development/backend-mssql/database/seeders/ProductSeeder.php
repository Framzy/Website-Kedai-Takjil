<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/products.json');

        if (!File::exists($path)) {
            throw new RuntimeException("File products.json tidak ditemukan: {$path}");
        }

        $products = json_decode(
            File::get($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                [
                    'name' => $product['name'],
                ],
                [
                    'price' => $product['price'],
                    'image_key' => $product['imageKey'],
                    'description' => $product['description'] ?? null,
                    'is_available' =>  $product['is_available'] ?? false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}