<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {                                                   
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9\s\-\+]+$/'],
            'customer_address' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer','distinct','exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ],  [
            'customer_name.required' => 'Nama pembeli wajib diisi.',
            'customer_name.max' => 'Nama pembeli maksimal 255 karakter.',

            'customer_phone.required' => 'Nomor handphone wajib diisi.',
            'customer_phone.min' => 'Nomor handphone minimal 10 karakter.',
            'customer_phone.max' => 'Nomor handphone maksimal 20 karakter.',
            'customer_phone.regex' => 'Format nomor handphone tidak valid.',

            'customer_address.required' => 'Alamat pengantaran wajib diisi.',

            'items.required' => 'Keranjang tidak boleh kosong.',
            'items.array' => 'Format keranjang tidak valid.',
            'items.min' => 'Tambahkan minimal satu produk ke keranjang.',

            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.integer' => 'ID produk tidak valid.',
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh dikirim berulang kali.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak ditemukan.',

            'items.*.quantity.required' => 'Jumlah produk wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah produk harus berupa bilangan bulat.',
            'items.*.quantity.min' => 'Jumlah produk minimal 1.',
            ]
        );
    
        $orderId = DB::transaction(function () use ($validated) {
            $itemsJson = json_encode(
                $validated['items'],
                JSON_THROW_ON_ERROR
            );

            $result = DB::select(
                'EXEC dbo.CreateOrder ?, ?, ?, ?',
                [
                    $validated['customer_name'],
                    $validated['customer_phone'],
                    $validated['customer_address'],
                    $itemsJson,
                ]
            );

            return $result[0]->order_id;
        });

        $order = Order::with('items')->findOrFail($orderId);
        
        return response()->json([
            'message' => 'Pesanan berhasil dibuat.',
            'data' => $order,
        ], 201);
    }
}