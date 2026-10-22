# Kedai Takjil API Documentation

## 1. Overview

Dokumentasi ini menjelaskan REST API untuk aplikasi Kedai Takjil yang dibangun menggunakan Laravel.

API digunakan untuk mengambil data produk dan memproses pesanan pelanggan.

### Base URL

**Local development**

```text
http://127.0.0.1:8000/api
```

Sesuaikan base URL jika backend dijalankan menggunakan host atau port berbeda.

### Response Format

API menggunakan JSON untuk request dan response. Contoh response pada dokumen ini menunjukkan struktur yang diharapkan berdasarkan implementasi dan automated test.

---

## 2. Endpoint Summary

| Method | Endpoint         | Description                            |
| ------ | ---------------- | -------------------------------------- |
| GET    | `/products`      | Mengambil daftar produk yang tersedia  |
| GET    | `/products/{id}` | Mengambil detail produk berdasarkan ID |
| POST   | `/orders`        | Membuat pesanan pelanggan              |

---

## 3. Products API

### 3.1 Get Available Products

Mengambil daftar produk yang tersedia untuk dipesan.

**Request**

```http
GET /api/products
Accept: application/json
```

**Success Response — `200 OK`**

Contoh struktur response:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Es Buah",
      "description": "Minuman segar",
      "price": "10000.00",
      "image_key": "product_img_1",
      "is_available": true,
      "sold": 0
    }
  ]
}
```

Contoh nilai di atas bersifat ilustratif. Jumlah produk, ID, harga, dan nilai `sold` bergantung pada data database.

**Business Rules**

- Hanya produk dengan `is_available = true` yang ditampilkan.
- Produk yang tidak tersedia tidak disertakan dalam daftar.
- Field `sold` menunjukkan jumlah unit produk yang tercatat dalam item pesanan, sesuai implementasi backend.
- Daftar produk diurutkan berdasarkan ID produk.

**Verified by Test**

- Endpoint mengembalikan `200 OK`.
- Produk yang tersedia dikembalikan dalam response.
- Produk yang tidak tersedia dikecualikan.

### 3.2 Get Product by ID

Mengambil detail produk berdasarkan ID numerik.

**Request**

```http
GET /api/products/{id}
Accept: application/json
```

Contoh:

```http
GET /api/products/1
```

Respons untuk produk yang tidak ditemukan perlu mengikuti exception handling aktual Laravel. Status HTTP dan struktur error untuk kasus ini belum dibuktikan oleh test yang tersedia.

---

## 4. Orders API

### 4.1 Create Order

Membuat pesanan baru beserta item-item pesanan.

**Request**

```http
POST /api/orders
Content-Type: application/json
Accept: application/json
```

**Request Body**

```json
{
  "customer_name": "Pelanggan Test",
  "customer_phone": "081234567890",
  "customer_address": "Alamat untuk testing",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

### Request Fields

| Field                | Type    | Validation                                                                   |
| -------------------- | ------- | ---------------------------------------------------------------------------- |
| `customer_name`      | String  | Required, maximum 255 characters                                             |
| `customer_phone`     | String  | Required, length 10–20 characters, hanya angka, spasi, tanda `-`, dan `+`    |
| `customer_address`   | String  | Required                                                                     |
| `items`              | Array   | Required, minimal satu item                                                  |
| `items.*.product_id` | Integer | Required, harus berbeda untuk setiap item, dan harus ada di tabel `products` |
| `items.*.quantity`   | Integer | Required, minimal 1                                                          |

### Business Rules

1. Harga produk diambil dari database, bukan dipercaya dari frontend.
2. Subtotal setiap item dihitung menggunakan kuantitas dikalikan harga produk di database.
3. Total pesanan dihitung dari penjumlahan seluruh subtotal item.
4. Produk harus tersedia untuk dipesan.
5. Pesanan baru menggunakan status `pending`.
6. Pembuatan pesanan dan item-itemnya dilakukan menggunakan database transaction agar perubahan data dikelola sebagai satu operasi.
7. Jika validasi gagal, pesanan tidak dibuat.

### Success Response — `201 Created`

Contoh response berdasarkan field yang diverifikasi oleh automated test:

```json
{
  "message": "Pesanan berhasil dibuat.",
  "data": {
    "total_amount": "20000.00",
    "status": "pending"
  }
}
```

Contoh di atas hanya menampilkan field yang dibuktikan oleh test. Field lain yang mungkin disertakan oleh model saat serialisasi response tidak dicantumkan sebagai kontrak final.

### Error Responses

**422 Unprocessable Entity — `items` kosong**

```json
{
  "message": "The items field is required.",
  "errors": {
    "items": ["The items field is required."]
  }
}
```

Struktur ini merupakan ilustrasi format validasi Laravel. Pesan aktual dapat berbeda sesuai bahasa dan konfigurasi aplikasi.

**422 Unprocessable Entity — produk tidak ditemukan**

Test memverifikasi adanya validation error pada field `items.0.product_id` dan bahwa pesanan tidak tersimpan.

Bentuk umum error Laravel:

```json
{
  "message": "The selected items.0.product_id is invalid.",
  "errors": {
    "items.0.product_id": ["The selected items.0.product_id is invalid."]
  }
}
```

Pesan aktual dapat berbeda sesuai konfigurasi aplikasi.

**Produk tidak tersedia**

Jika produk tidak tersedia saat checkout, backend menolak pesanan dengan validation error pada `items`. Pesan yang digunakan implementasi adalah:

```text
Satu atau lebih produk tidak tersedia.
```

Status dan bentuk response aktual untuk skenario ini sebaiknya dikonfirmasi melalui test atau request langsung.

---

## 5. Data Model

### Products

| Column         | Type                   | Description                 |
| -------------- | ---------------------- | --------------------------- |
| `id`           | Auto-increment integer | Primary key                 |
| `name`         | String                 | Nama produk                 |
| `description`  | Nullable text          | Deskripsi produk            |
| `price`        | Decimal(10,2)          | Harga produk                |
| `image_key`    | String                 | Key referensi gambar produk |
| `is_available` | Boolean                | Status ketersediaan produk  |
| `created_at`   | Timestamp              | Waktu pembuatan             |
| `updated_at`   | Timestamp              | Waktu pembaruan             |

### Orders

| Column             | Type                   | Description                       |
| ------------------ | ---------------------- | --------------------------------- |
| `id`               | Auto-increment integer | Primary key                       |
| `customer_name`    | String                 | Nama pelanggan                    |
| `customer_phone`   | String(20)             | Nomor telepon pelanggan           |
| `customer_address` | Text                   | Alamat pelanggan                  |
| `total_amount`     | Decimal(12,2)          | Total pembayaran                  |
| `status`           | String(30)             | Status pesanan, default `pending` |
| `created_at`       | Timestamp              | Waktu pembuatan                   |
| `updated_at`       | Timestamp              | Waktu pembaruan                   |

### Order Items

| Column       | Type                   | Description                |
| ------------ | ---------------------- | -------------------------- |
| `id`         | Auto-increment integer | Primary key                |
| `order_id`   | Foreign key            | Referensi ke `orders.id`   |
| `product_id` | Foreign key            | Referensi ke `products.id` |
| `quantity`   | Unsigned integer       | Jumlah produk              |
| `unit_price` | Decimal(10,2)          | Harga satuan saat checkout |
| `subtotal`   | Decimal(12,2)          | Total harga item           |
| `created_at` | Timestamp              | Waktu pembuatan            |
| `updated_at` | Timestamp              | Waktu pembaruan            |

### Relationships

- Satu `Order` memiliki banyak `OrderItem`.
- Satu `Product` dapat direferensikan oleh banyak `OrderItem`.
- Penghapusan order menghapus item terkait melalui cascade delete.
- Penghapusan produk yang masih direferensikan oleh item pesanan dibatasi oleh foreign key.

---

## 6. Automated Testing

Automated tests tersedia untuk endpoint produk dan checkout.

### Product API Tests

- Menguji endpoint daftar produk.
- Memastikan produk tersedia dikembalikan.
- Memastikan produk tidak tersedia dikecualikan.

### Order API Tests

- Menguji checkout valid dan pembuatan order beserta order items.
- Menguji penolakan checkout dengan `items` kosong.
- Menguji penolakan ID produk yang tidak ditemukan.
- Menguji perhitungan total berdasarkan harga database, meskipun request mengirimkan nilai `price` dan `subtotal` yang tidak valid sebagai sumber harga.

Test menggunakan Laravel feature testing dan `RefreshDatabase`.

Jalankan seluruh test menggunakan:

```bash
php artisan test
```

Jalankan test secara terpisah:

```bash
php artisan test --filter=ProductApiTest
php artisan test --filter=OrderApiTest
```

Hasil test yang tersedia membuktikan assertion yang tercantum di atas. Skenario lain perlu diuji secara terpisah sebelum dianggap terverifikasi.

---

## 7. Database Migration and Seeding

Struktur database dikelola menggunakan Laravel migrations.

Untuk membangun ulang database lokal dan menjalankan seeder produk:

```bash
php artisan migrate:fresh
php artisan db:seed --class=ProductSeeder
```

**Warning:** `migrate:fresh` menghapus seluruh tabel sebelum menjalankan ulang migration. Gunakan hanya pada database development atau testing yang datanya boleh dihapus.

Seeder produk membaca data dari file JSON dan memasukkan produk ke database. ID produk dihasilkan oleh database.

---

## 8. Database Portability

API ini menjadi kontrak dasar untuk eksperimen implementasi backend dengan beberapa database.

- **TiDB Cloud:** implementasi backend referensi saat ini.
- **Azure SQL:** target implementasi menggunakan database relasional SQL Server.
- **MongoDB Atlas:** target implementasi menggunakan model dokumen.

Perbedaan tipe data, relasi, query, transaksi, dan implementasi persistence perlu ditangani pada masing-masing backend. Endpoint dan format response yang ingin dipertahankan harus dibandingkan melalui pengujian.

Dokumentasi ini tidak menyatakan bahwa ketiga implementasi sudah selesai atau seluruhnya kompatibel.

---
