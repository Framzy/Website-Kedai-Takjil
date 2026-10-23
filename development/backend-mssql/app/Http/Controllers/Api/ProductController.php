<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            $products = DB::select(
                'EXEC dbo.GetAvailableProducts'
            );
            
            return response()->json([
                'data' => $products,
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
            $products = DB::select(
                'EXEC dbo.GetAvailableProductById ?',
                [$id]
            );

            if (empty($products)) {
                throw new ModelNotFoundException();
            }

            $product = $products[0];

            $product->id = (int) $product->id;
            $product->sold = (int) $product->sold;

            return response()->json(['data' => $product]);

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