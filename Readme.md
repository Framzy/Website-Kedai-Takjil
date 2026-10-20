# Kedai Takjil

A responsive mini e-commerce web application for browsing takjil products and placing orders.

## 1. Overview

![Kedai Takjil Project Preview](https://github.com/user-attachments/assets/e61ef46a-4ad5-4f78-9cbb-8cd36f68ad79)

Kedai Takjil is a full-stack portfolio project demonstrating frontend development, REST API integration, and database management.

Users can browse products, manage a shopping cart, and place orders through a checkout flow.

## 2. Features

- Browse available takjil products and view product details.
- Add products to the shopping cart.
- Update product quantities and remove items from the cart.
- Calculate the total order amount.
- Submit orders through the checkout flow.
- Handle loading states and API errors with retry functionality.

## 3. Tech Stack

- **Frontend:** React, Vite, Tailwind CSS
- **Routing:** React Router
- **Backend:** PHP, Laravel 13
- **Database:** TiDB Cloud (MySQL-compatible)
- **Containerization:** Docker
- **Deployment:** Vercel (frontend), Render (backend)

## 4. Architecture

The application separates the frontend and backend to keep user interface responsibilities separate from business logic and data management.

```text
User
  |
  v
React Frontend (Vercel)
  |
  | REST API (JSON)
  v
Laravel Backend (Docker on Render)
  |
  v
TiDB Cloud Database
```

```text
Website-Kedai-Takjil/
├── development/
│   ├── backend/
│   │   ├── app/             # Application logic and models
│   │   ├── database/        # Migrations and seeders
│   │   ├── routes/          # API and web routes
│   │   ├── tests/           # Backend tests
│   │   └── Dockerfile
│   └── frontend/
│       └── src/
│           ├── app/         # Application setup and routing
│           ├── components/  # Reusable UI components
│           ├── context/     # Shared cart state
│           ├── hooks/       # Custom React hooks
│           ├── pages/       # Application pages
│           ├── sections/    # Page sections
│           └── utils/       # Utility functions
└── README.md
```

The project separates the frontend and backend into dedicated directories. The frontend manages the user interface and cart state, while the backend handles API requests, business logic, database operations, and order processing.

### How It Works

1. The frontend requests product data from the Laravel API.
2. Users select products and manage their shopping cart in the browser.
3. The frontend sends order details to the backend during checkout.
4. The backend validates the request, calculates order totals using database prices, and stores the order and its items.

The shopping cart uses browser `localStorage` to preserve its contents across page reloads.

### API Reference

The Laravel backend provides the following REST API endpoints:

| Method | Endpoint             | Description                             |
| ------ | -------------------- | --------------------------------------- |
| `GET`  | `/api/products`      | Retrieve the product list.              |
| `GET`  | `/api/products/{id}` | Retrieve details of a specific product. |
| `POST` | `/api/orders`        | Create a new order.                     |

The API uses JSON for request and response data.

## 5. Getting Started

### Requirements

- Node.js and npm
- PHP and Composer
- Docker (optional, for running the backend as a container)
- Access to a compatible MySQL database

### Frontend Setup

Navigate to the frontend directory:

```bash
cd development/frontend
npm install
```

Create a `.env` file in the frontend directory:

```env
VITE_API_URL=http://localhost:8000/api
```

Start the development server:

```bash
npm run dev
```

### Backend Setup

Navigate to the backend directory:

```bash
cd development/backend
composer install
```

#### Environment Configuration

Create a `.env` file based on `.env.example` in the backend directory.

Configure the required environment variables for your local environment:

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

Replace the database values with your own configuration. If you use TiDB Cloud, configure the appropriate connection settings and SSL requirements.

**Never commit your actual `.env` file or expose database credentials and application secrets.**

Generate the application key:

```bash
php artisan key:generate
```

Run database migrations and seed the initial product data:

```bash
php artisan migrate
php artisan db:seed
```

Start the Laravel development server:

```bash
php artisan serve
```

Ensure that the frontend API URL matches the backend address and that the database connection is configured correctly.

### Docker

The backend includes a Docker configuration for containerized deployment.

Build the backend image from the backend directory:

```bash
docker build -t kedai-takjil-api .
```

For production deployment, the frontend is hosted on Vercel and the backend runs on Render. Production environment variables are configured in the deployment environment.

## 6. Demo & Links

- **Live Demo:** [Kedai Takjil](https://framzy-kedai-takjil.vercel.app/)
- **Repository:** [GitHub Repository](https://github.com/Framzy/Website-Kedai-Takjil)
