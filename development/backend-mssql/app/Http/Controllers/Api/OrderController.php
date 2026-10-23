<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
    
        $order = DB::transaction(function () use ($validated) {
            $productIds = collect($validated['items'])
                ->pluck('product_id')
                ->unique();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('is_available', true)
                ->get() 
                ->keyBy('id');
                
            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => [
                        'Satu atau lebih produk tidak tersedia.'
                    ]
                ]);
            }

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);                
                $totalAmount += 
                    (int) $item['quantity'] * (int) $product->price;
            }

            $order = Order::create([
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = (int) $product->price;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $quantity * $unitPrice,
                ]);
            }

            return $order;
        });

        return response()->json([
            'message' => 'Pesanan berhasil dibuat.',
            'data' => $order,
        ], 201);
    }
}