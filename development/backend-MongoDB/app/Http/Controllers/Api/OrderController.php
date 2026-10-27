<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;
use Throwable;

class OrderController extends Controller
{
    private function convertToDecimal128($value): Decimal128
    {
        $formatted = number_format((float)$value, 2, '.', '');
        return new Decimal128($formatted);
    }
    public function store(Request $request): JsonResponse
    {                                                   
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9\s\-\+]+$/'],
            'customer_address' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer','distinct'],
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

            'items.*.quantity.required' => 'Jumlah produk wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah produk harus berupa bilangan bulat.',
            'items.*.quantity.min' => 'Jumlah produk minimal 1.',
            ]
        );

        try {
            $client = DB::connection('mongodb')->getMongoClient();

            $database = $client->selectDatabase(
                config('database.connections.mongodb.database')
            );

            $productsCollection = $database->selectCollection('products');
            $ordersCollection = $database->selectCollection('orders');
            $orderItemsCollection = $database->selectCollection('order_items');

            $items = $validated['items'];

            $productIds = array_map(
                fn ($item) => (int) $item['product_id'],
                $items
            );

            $orderId = new ObjectId();
            $totalAmount = 0;

            $session = $client->startSession();

            try {
                $session->startTransaction();
                    // 1. Ambil produk yang tersedia dalam transaksi.
                    $products = $productsCollection->find(
                        [
                            'id' => ['$in' => $productIds],
                            'is_available' => true,
                        ],
                        ['session' => $session]
                    )->toArray();

                    $productsById = [];

                    foreach ($products as $product) {
                        $productsById[(string) $product->id] = $product;
                    }

                    // 2. Pastikan semua produk tersedia.
                    if (count($productsById) !== count($productIds)) {
                        throw ValidationException::withMessages([
                            'items' => [
                                'Satu atau lebih produk tidak tersedia.',
                            ],
                        ]);
                    }

                    // 3. Hitung total menggunakan harga database.
                    $totalAmount = 0;

                    foreach ($items as $item) {
                        $product = $productsById[
                            (string) $item['product_id']
                        ];

                        $price = (float) (string) $product->price;
                        $quantity = (int) $item['quantity'];

                        $totalAmount += $quantity * (int) $price;
                    }

                    // 4. Simpan pesanan utama.
                    $ordersCollection->insertOne([
                        '_id' => $orderId,
                        'customer_name' => $validated['customer_name'],
                        'customer_phone' => $validated['customer_phone'],
                        'customer_address' => $validated['customer_address'],
                        'total_amount' => $this->convertToDecimal128(
                            $totalAmount
                        ),
                        'status' => 'pending',
                        'created_at' => new UTCDateTime(),
                        'updated_at' => new UTCDateTime(),
                    ], ['session' => $session]);

                    // Simulasi kegagalan khusus testing untuk menguji rollback.
                    if (
                        app()->environment('testing')
                        && config('app.testing_fail_order_items_insert', false)
                    ) {
                        throw new \RuntimeException(
                            'Simulated order_items insertion failure.'
                        );
                    }

                    // 5. Simpan semua item pesanan.
                    foreach ($items as $item) {
                        $product = $productsById[
                            (string) $item['product_id']
                        ];

                        $quantity = (int) $item['quantity'];
                        $unitPrice = (int) (float) (string) $product->price;
                        $subTotal = $quantity * $unitPrice;

                        $orderItemsCollection->insertOne([
                            'order_id' => $orderId,
                            'product_id' => (int) $product->id,
                            'quantity' => $quantity,
                            'unit_price' => $this->convertToDecimal128(
                                $unitPrice
                            ),
                            'subtotal' => $this->convertToDecimal128(
                                $subTotal
                            ),
                        ], ['session' => $session]);
                    }                
                $session->commitTransaction();
            }catch (Throwable $e) {
                $session->abortTransaction();
                throw $e;
            } finally {
                $session->endSession();
            }

            return response()->json([
                'message' => 'Pesanan berhasil dibuat.',
                'data' => [
                    'id' => (string) $orderId,
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'],
                    'customer_address' => $validated['customer_address'],
                    'total_amount' => number_format(
                        $totalAmount,
                        2,
                        '.',
                        ''
                    ),
                    'status' => 'pending',
                ],
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Gagal membuat pesanan.', [
                'exception' => $e,
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'Terjadi kesalahan pada server.',
            ], 500);
        }

    }
}