# Kedai Takjil

A responsive mini e-commerce web application for browsing takjil products and placing orders.

## 1. Overview

![alt text](thumbnail_project02.webp)
Kedai Takjil is a full-stack portfolio project built to demonstrate frontend development, backend API integration, and database management.

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
React Frontend
(Vercel)
  |
  | REST API (JSON)
  v
Laravel Backend
(Docker on Render)
  |
  v
TiDB Cloud
(Database)
```

**How it works:**

1. The frontend requests product data from the Laravel API.
2. Users select products and manage their cart in the browser.
3. The frontend sends order details to the backend during checkout.
4. The backend validates the request, calculates order totals using database prices, and stores the order and its items.

The shopping cart uses browser `localStorage` to preserve its contents across page reloads.

## 5. Getting Started

### Requirements

- Node.js and npm
- PHP and Composer
- Docker (optional, for running the backend as a container)
- Access to a compatible database

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

Configure the backend environment using `.env.example`. Set the application key, database connection, and other required settings.

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

Ensure that the frontend API URL matches the backend address and that the database is configured correctly.

### Docker

The backend includes a Docker configuration for containerized deployment.

Docker can be used to build the backend image:

```bash
docker build -t kedai-takjil-api .
```

Production deployment uses Render for the backend and Vercel for the frontend.

## 6. Demo & Links

- **Live Demo:** [Website Kedai takjil](https://framzy-kedai-takjil.vercel.app/)
- **Repository:** [Github Repository Kedai takjil](https://github.com/Framzy/Website-Kedai-Takjil)
