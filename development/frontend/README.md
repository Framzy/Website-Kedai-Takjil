# Kedai Takjil — Frontend

React frontend for the Kedai Takjil e-commerce application.

## 1. Overview

The frontend provides the user interface for browsing takjil products, managing the shopping cart, and completing the checkout flow.

It communicates with the Laravel backend through REST API requests and uses browser `localStorage` to preserve shopping cart contents across page reloads.

## 2. Tech Stack

- **UI Library:** React
- **Build Tool:** Vite
- **Styling:** Tailwind CSS
- **Routing:** React Router
- **API Communication:** REST API with JSON

## 3. Features

- Browse products and view product details.
- Add, remove, and update shopping cart items.
- Calculate order totals.
- Submit orders through checkout.
- Display loading states and handle API errors with retry functionality.
- Preserve cart contents using `localStorage`.

## 4. Getting Started

### Requirements

- Node.js
- npm
- A running Kedai Takjil backend API

### Installation

Navigate to the frontend directory:

```bash
cd development/frontend
```

Install dependencies:

```bash
npm install
```

### Environment Configuration

Create a `.env` file in the frontend directory:

```env
VITE_API_URL=http://localhost:8000/api
```

`VITE_API_URL` specifies the base URL of the backend API. Update it to match your backend address.

Do not put database credentials, private API keys, or other secrets in frontend environment variables. Variables prefixed with `VITE_` are exposed to client-side code.

### Run the Development Server

```bash
npm run dev
```

Vite displays the local development URL in the terminal.

### Build for Production

```bash
npm run build
```

The production build is generated in the `dist` directory by default.

## 5. Deployment

The frontend is deployed on Vercel.

Configure `VITE_API_URL` in the Vercel project environment variables to point to the deployed Laravel API. Redeploy the frontend after changing production environment variables.

## 6. Related Documentation

See the root README for the complete application architecture, backend setup, API reference, live demo, and repository links.
