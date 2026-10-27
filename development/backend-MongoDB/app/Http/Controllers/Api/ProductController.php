<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $database = DB::connection('mongodb');

            $productsCollection = $database->getCollection('products');
            $orderItemsCollection = $database->getCollection('order_items');

            $products = iterator_to_array($productsCollection->find(
                ['is_available' => true],
                ['sort' => ['id' => 1]]
            ));

            $sales = $orderItemsCollection->aggregate([
                [
                    '$group' => [
                        '_id' => '$product_id',
                        'sold' => ['$sum' => '$quantity']
                ]],
            ]);

            $soldByProduct = [];

            foreach ($sales as $sale) {
                $soldByProduct[(string) $sale->_id] = $sale->sold;
            }

            $data = array_map(function ($product) use ($soldByProduct) {
                $item = (array) $product;

                $productId = $product->id;

                $item['price'] = (float) (string) $product->price;
                $item['sold'] = $soldByProduct[(string) $productId] ?? 0;

                unset($item['_id']);

                return $item;
            }, $products);

            return response()->json([
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal mengambil daftar produk.', [
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan pada server.',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $productsCollection = DB::connection('mongodb')->getCollection('products');
            $orderItemsCollection = DB::connection('mongodb')->getCollection('order_items');

            $product = $productsCollection->findOne([
                'id' => $id,
                'is_available' => true
            ]);

            if ($product === null) {
                throw (new ModelNotFoundException())
                    ->setModel(Product::class, [$id]);
            }

            $sales = $orderItemsCollection->aggregate([
                [
                    '$match' => [
                        'product_id' => $id
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$product_id',
                        'sold' => ['$sum' => '$quantity']
                ]],
            ])
            ->toArray();

            $data = (array) $product;

            $data['price'] = (float) (string) $product->price;
            $data['sold'] = isset($sales[0]) ? $sales[0]->sold : 0;

            unset($data['_id']);

            return response()->json([
                'data' => $data,
            ]);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Gagal mengambil detail produk.', [
                'product_id' => $id,
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan pada server.',
            ], 500);
        }
    }
}