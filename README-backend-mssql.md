# Kedai Takjil — MSSQL Backend

Laravel 13 REST API backend for the Kedai Takjil mini e-commerce project, configured to use Microsoft SQL Server.

This backend is maintained separately from the original MySQL-compatible backend so SQL Server integration and T-SQL can be explored without changing the existing application.

## 1. Tech Stack

- **Language:** PHP 8.4
- **Framework:** Laravel 13
- **Database:** Microsoft SQL Server
- **PHP database driver:** `pdo_sqlsrv` (and `sqlsrv`)
- **Containerization:** Docker
- **API format:** REST API with JSON

## 2. Features

- Retrieve available products.
- Retrieve an available product by ID.
- Validate and create customer orders.
- Store order items and calculate order totals from database product prices.
- Run automated API tests against a dedicated test database.
- Compare Laravel Eloquent and SQL Server Stored Procedure implementations.

## 3. API Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/products` | Retrieve available products, including sold quantities. |
| `GET` | `/api/products/{id}` | Retrieve one available product by ID. |
| `POST` | `/api/orders` | Validate and create an order with its items. |

The API accepts and returns JSON. The frontend should continue using the same API contract as the main Kedai Takjil application.

### Create an order

Example request body:

```json
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

The backend must calculate the order total using prices from the database. Do not trust a total amount supplied by the client.

## 4. Database Design

The relational schema uses these main tables:

- `products` — product details, price, image key, and availability.
- `orders` — customer details, order total, and order status.
- `order_items` — products and quantities associated with each order, including the unit price and subtotal recorded at order time.

The schema uses foreign keys to relate order items to orders and products.

## 5. Database Access Approaches

This project is also a learning exercise for comparing application-level database operations with T-SQL stored procedures.

### Laravel / Eloquent

The Eloquent approach performs validation, product lookup, total calculation, and order creation in Laravel, using a database transaction for the order and its items.

### SQL Server Stored Procedures

The stored-procedure implementation calls SQL Server procedures from Laravel. The current procedures used in this learning implementation are:

- `dbo.GetAvailableProducts`
- `dbo.GetAvailableProductById`
- `dbo.CreateOrder`

The product procedures return available products and sold quantities. `dbo.CreateOrder` receives order details and the requested items as JSON.

**Important:** Stored procedures must exist in the target SQL Server database before the stored-procedure implementation can run. If they are currently created manually, make sure they are installed in each relevant database. Consider versioning their SQL definitions alongside the project so a new environment can be reproduced reliably.

### Keeping both order implementations

The Eloquent controller is the default route implementation. The stored-procedure controller may be retained separately for learning and comparison, but do not point the same route to both controllers at once. Use a separate temporary test route if both implementations need to be exercised through HTTP.

## 6. Getting Started

### Requirements

- PHP 8.4 when running directly on the host, or Docker.
- Composer when running directly on the host.
- Microsoft SQL Server reachable from the application.
- Microsoft ODBC Driver and the PHP `pdo_sqlsrv` extension when running directly on the host.
- A separate SQL Server database for automated tests.

### Install dependencies

From the MSSQL backend directory:

```bash
composer install
```

If the project has not yet been configured:

```bash
copy .env.example .env
php artisan key:generate
```

Use the equivalent file-copy command for your shell if you are not using Windows Command Prompt.

### Configure the environment

Create or update `.env` in this backend directory. Example settings:

```env
APP_NAME="Kedai Takjil MSSQL"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=KedaiTakjilMSSQL
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Set `DB_HOST` to an address reachable from the application process. When Laravel runs inside Docker, `127.0.0.1` refers to the Laravel container itself; it does not refer to the Windows host or a separate SQL Server container.

Do not commit `.env` or place database credentials in the Docker image.

### Run migrations and seeders

Check the configured database carefully before running migrations or seeders:

```bash
php artisan migrate
php artisan db:seed
```

Use these commands only against the intended database. Seeders may insert or update project data, while test configuration must never point at the development database.

### Run the application

```bash
php artisan serve
```

The API is available at `http://localhost:8000/api` unless the host or port is configured differently.

## 7. Automated Tests

Use a dedicated test database, for example `KedaiTakjilMSSQL_Test`. Do not run tests that refresh the database against your development database, because database refresh operations can drop and recreate tables.

Configure the test environment in the project's test configuration (`phpunit.xml` and/or the testing environment file) so tests use the test database.

Run the test suite:

```bash
php artisan test
```

If tests exercise stored procedures, make sure those procedures are also installed in the test database. Verify the test database configuration before running the suite.

## 8. Docker

The Docker image is intended to run the Laravel application. It does **not** run SQL Server in the same container.

The image needs a compatible Microsoft ODBC Driver and PHP SQL Server extensions (`pdo_sqlsrv` and, where required, `sqlsrv`). Build the image from this backend directory:

```bash
docker build -t kedai-takjil-mssql .
```

For a development image that includes Composer development dependencies, if the Dockerfile supports the build argument:

```bash
docker build --build-arg INSTALL_DEV_DEPENDENCIES=true -t kedai-takjil-mssql:dev .
```

Supply database connection settings through the runtime environment when starting the container or configuring the deployment platform. Do not bake secrets into the image.

Before deployment, build and test the image in the target environment. Confirm that the PHP extensions load, the Laravel application can reach SQL Server, migrations and stored procedures are present, and the health of the API has been verified.

## 9. Project Structure

Typical Laravel directories used by this backend:

```text
backend-mssql/
├── app/
│   ├── Http/Controllers/
│   └── Models/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
├── Dockerfile
├── composer.json
└── README.md
```

The exact files may vary as the implementation evolves.

## 10. Security Notes

- Keep credentials and application secrets out of source control.
- Use a dedicated test database.
- Validate client input and calculate prices on the server.
- Use parameter binding when passing values to SQL statements.
- Do not expose detailed SQL errors to clients in production.
- Treat the current implementation as a learning and portfolio project; review authorization, concurrency behavior, error handling, and deployment configuration before using it for real customer orders.
