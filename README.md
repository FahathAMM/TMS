# Mobile Shop POS System

A complete Point of Sale system for mobile shops.

## Architecture

```
pos/
├── backend/     # Laravel 13 REST API
└── frontend/    # Next.js 15 App
```

## Quick Start

### 1. Start Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Edit .env with your DB credentials
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

### 2. Start Frontend
```bash
cd frontend
npm install
npm run dev
```

### 3. Login
- URL: http://localhost:3000/login
- Admin: admin@pos.com / password
- Staff: staff@pos.com / password

## Features
- Dashboard with sales charts & stats
- Product management (CRUD, images, SKU)
- Category & Brand management
- Customer management
- POS sales screen with cart, discounts, tax
- Sales history & invoice printing
- Inventory management & stock adjustments
- User management (Admin only)
- Role-based access (Admin / Staff)

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/login | Login |
| POST | /api/auth/logout | Logout |
| GET | /api/auth/me | Current user |
| GET | /api/dashboard | Dashboard stats |
| GET/POST | /api/products | Products list/create |
| GET/PUT/DELETE | /api/products/{id} | Product detail |
| GET/POST | /api/categories | Categories |
| GET/POST | /api/brands | Brands |
| GET/POST | /api/customers | Customers |
| GET/POST | /api/sales | Sales list/create |
| GET | /api/sales/{id} | Sale detail |
| POST | /api/sales/{id}/cancel | Cancel sale |
| GET | /api/inventory | Inventory |
| POST | /api/inventory/adjust | Stock adjustment |
| GET | /api/inventory/history | Adjustment history |
| GET | /api/inventory/low-stock | Low stock alert |
| GET/POST | /api/users | Users (Admin only) |
