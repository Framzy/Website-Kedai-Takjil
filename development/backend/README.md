# Kedai Takjil — Backend

Laravel REST API for the Kedai Takjil e-commerce application.

## 1. Overview

The backend provides product data and handles order creation. It validates incoming requests, calculates order totals using database prices, and stores orders and their associated items.

## 2. Tech Stack

- **Language:** PHP
- **Framework:** Laravel 13
- **Database:** TiDB Cloud (MySQL-compatible)
- **API Format:** REST API with JSON
- **Containerization:** Docker
- **Testing:** PHPUnit

## 3. API Reference

| Method | Endpoint             | Description                             |
| ------ | -------------------- | --------------------------------------- |
| `GET`  | `/api/products`      | Retrieve the product list.              |
| `GET`  | `/api/products/{id}` | Retrieve details of a specific product. |
| `POST` | `/api/orders`        | Create a new order.                     |

## 4. Getting Started

### Requirements

- PHP
- Composer
- Access to a compatible MySQL database
- Docker (optional)

### Installation

Navigate to the backend directory:

```bash
cd development/backend
```

Install dependencies:

```bash
composer install
```

### Environment Configuration

Create a `.env` file based on `.env.example`.

Configure the application and database settings for your environment. The following is an example for a local MySQL database:

```env
APP_NAME="Kedai Takjil"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stderr
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kedai_takjil
DB_USERNAME=your_username
DB_PASSWORD=your_password

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Replace the database values with your own configuration. For TiDB Cloud, configure the required connection settings and SSL options.

Never commit your actual `.env` file or expose database credentials and application secrets.

Generate the application key:

```bash
php artisan key:generate
```

### Database Setup

Run the database migrations:

```bash
php artisan migrate
```

Seed the initial product data:

```bash
php artisan db:seed
```

Ensure the database connection is configured correctly before running these commands.

### Run the Development Server

```bash
php artisan serve
```

The API is available at `http://localhost:8000` by default.

## 5. Testing

Run the backend test suite:

```bash
php artisan test
```

Use the test results to verify the current application behavior after making changes.

## 6. Docker

The backend includes a Dockerfile for containerized builds.

Build the image from the backend directory:

```bash
docker build -t kedai-takjil-api .
```

Production deployment runs on Render. Configure production environment variables and database connectivity in the deployment environment.

## 7. Related Documentation

See the root README for the overall project architecture, frontend setup, live demo, and repository links.
