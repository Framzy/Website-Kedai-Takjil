# Kedai Takjil --- MongoDB Backend

Laravel 13 REST API backend for the Kedai Takjil mini e-commerce
project, configured to use MongoDB Atlas.

This backend is maintained separately from other Kedai Takjil backend
variants so MongoDB integration can be developed and tested
independently.

## 1. Tech Stack

-   **Language:** PHP 8.4
-   **Framework:** Laravel 13
-   **Database:** MongoDB Atlas
-   **Laravel integration:** `mongodb/laravel-mongodb`
-   **API format:** REST API with JSON
-   **Automated tests:** PHPUnit / Laravel testing tools

## 2. Features

-   Retrieve available products.
-   Retrieve an available product by ID.
-   Validate and create customer orders.
-   Store order items and calculate order totals using product prices
    from MongoDB.
-   Use a MongoDB transaction for order creation and its order items.
-   Run automated API tests against a dedicated test database.
-   Verify rollback behavior when order-item insertion fails.

## 3. API Endpoints

  -----------------------------------------------------------------------
  Method                  Endpoint                Description
  ----------------------- ----------------------- -----------------------
  `GET`                   `/api/products`         Retrieve available
                                                  products, including
                                                  sold quantities.

  `GET`                   `/api/products/{id}`    Retrieve one available
                                                  product by ID.

  `POST`                  `/api/orders`           Validate and create an
                                                  order with its items.
  -----------------------------------------------------------------------

The API uses JSON. The frontend should continue using the same API
contract as the Kedai Takjil application.

### Create an order

Example request body:

``` json
{
  "customer_name": "Example Customer",
  "customer_phone": "081234567890",
  "customer_address": "Example street, Jakarta",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 2,
      "quantity": 1
    }
  ]
}
```

The backend calculates the order total using product prices stored in
MongoDB. Do not trust prices, subtotals, or totals supplied by the
client.

## 4. MongoDB Data Structure

This implementation uses the following collections:

-   `products` --- product details, price, image key, and availability.
-   `orders` --- customer details, order total, and order status.
-   `order_items` --- products and quantities associated with an order,
    including the unit price and subtotal recorded at order time.

The implementation uses numeric `id` values to identify products.
MongoDB documents also have their own `_id` field, typically an
`ObjectId`. These identifiers serve different purposes and should not be
treated as interchangeable.

Order and order-item relationships are represented by stored identifiers
rather than relational foreign-key constraints. Application logic is
responsible for validating referenced products and maintaining
consistency.

## 5. Database Connection

The MongoDB connection is configured in `config/database.php`:

``` php
'mongodb' => [
    'driver' => 'mongodb',
    'dsn' => env('MONGODB_URI'),
    'database' => env('MONGODB_DATABASE'),
],
```

The project uses the `mongodb/laravel-mongodb` package to integrate
MongoDB with Laravel. Some controller operations use the raw MongoDB
collection API directly for queries and writes.

## 6. Getting Started

### Requirements

-   PHP 8.4
-   Composer
-   MongoDB Atlas account and a database user
-   MongoDB PHP extension enabled
-   Network access from the development machine to the Atlas cluster

### Install dependencies

From the MongoDB backend directory:

``` bash
composer install
```

If the project has not yet been configured:

``` bash
copy .env.example .env
php artisan key:generate
```

Use the equivalent file-copy command for your shell if you are not using
Windows Command Prompt.

### Configure environment variables

Create or update `.env` in this backend directory:

``` env
APP_NAME="Kedai Takjil MongoDB"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mongodb
MONGODB_URI="mongodb+srv://<username>:<password>@<cluster-host>/?retryWrites=true&w=majority"
MONGODB_DATABASE="kedai_takjil_mongodb"
```

Replace the URI placeholders with the connection string provided by
MongoDB Atlas. URL-encode special characters in the database username or
password when required by the connection-string format.

The database name in `MONGODB_DATABASE` must match the intended
environment. Keep `.env` out of source control and never publish Atlas
credentials.

### Clear cached configuration

After changing connection settings, run:

``` bash
php artisan config:clear
```

### Run the application

``` bash
php artisan serve
```

The API is available at `http://localhost:8000/api` unless the host or
port is configured differently.

## 7. MongoDB Transactions

Order creation uses a MongoDB session and transaction to group the
insertion of an order and its order items. If an error occurs during the
transaction, the controller aborts the transaction so partial order data
is not committed.

MongoDB transactions require a deployment configuration that supports
them, such as a replica set or a sharded cluster. MongoDB Atlas clusters
support replica-set deployments; confirm the selected cluster
configuration before relying on transactions.

Do not remove transaction handling without considering the risk of an
order being stored without its corresponding order items, or vice versa.

## 8. Automated Tests

Automated tests must use a dedicated test database, separate from the
development database.

Example environment configuration:

``` env
MONGODB_DATABASE="kedai_takjil_mongodb_test"
```

The project's test setup checks that the configured database is
`kedai_takjil_mongodb_test` before running its database-backed tests.
Keep this safeguard in place and verify the active test configuration
before executing tests.

Run the full test suite:

``` bash
php artisan config:clear
php artisan test
```

### Current test coverage

The current suite includes tests for:

-   Listing available products.
-   Excluding unavailable products from the product list.
-   Creating an order and its order items.
-   Rejecting empty order items and nonexistent products.
-   Calculating totals from database prices rather than client-supplied
    values.
-   Creating an order with multiple products.
-   Rejecting unavailable products at checkout.
-   Rejecting invalid quantities and duplicate product IDs.
-   Rolling back an order when order-item insertion fails.

The latest reported run passed **13 tests with 61 assertions**. This
result describes the state of the suite at the time it was run; rerun
`php artisan test` after code or configuration changes.

## 9. Project Structure

Typical Laravel directories used by this backend:

``` text
backend-MongoDB/
├── app/
│   ├── Http/Controllers/
│   └── Models/
├── config/
│   └── database.php
├── routes/
│   └── api.php
├── tests/
│   └── Feature/
├── composer.json
├── phpunit.xml
└── README.md
```

The exact files may vary as the implementation evolves.

## 10. Security and Reliability Notes

-   Keep MongoDB Atlas credentials and application secrets out of source
    control.
-   Use a dedicated test database; never run automated tests against the
    development database.
-   Validate client input and calculate prices on the server.
-   Keep transaction handling around order creation and order-item
    insertion.
-   Avoid exposing detailed exception or database error information to
    clients in production.
-   Review authorization, rate limiting, concurrency behavior, logging,
    and deployment configuration before using the API for real customer
    orders.
