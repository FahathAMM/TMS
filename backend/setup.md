# Backend Setup

## Requirements
- PHP 8.2+
- Composer
- MySQL 8.0+

## Steps

```bash
cd backend

# 1. Install dependencies (includes spatie/laravel-permission)
composer install

# 2. Copy env file
cp .env.example .env

# 3. Generate app key
php artisan key:generate

# 4. Configure database in .env
# DB_DATABASE=pos_db
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 5. Create database
mysql -u root -p -e "CREATE DATABASE pos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Publish Spatie Permission migrations & config
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# 7. Run all migrations (app tables + spatie permission tables)
php artisan migrate

# 8. Seed roles, permissions, users, and sample data
php artisan db:seed

# 9. Create storage symlink
php artisan storage:link

# 10. Start server
php artisan serve
```

## Default Credentials
- Admin: admin@pos.com / password  (role: admin — all permissions)
- Staff: staff@pos.com / password  (role: staff — limited permissions)

## Roles & Permissions (Spatie)

| Permission         | Admin | Staff |
|--------------------|-------|-------|
| view products      | ✓     | ✓     |
| create products    | ✓     | ✓     |
| edit products      | ✓     | ✓     |
| delete products    | ✓     |       |
| view categories    | ✓     | ✓     |
| create/edit/delete | ✓     |       |
| view brands        | ✓     | ✓     |
| create/edit/delete | ✓     |       |
| view customers     | ✓     | ✓     |
| create customers   | ✓     | ✓     |
| edit customers     | ✓     | ✓     |
| delete customers   | ✓     |       |
| view sales         | ✓     | ✓     |
| create sales       | ✓     | ✓     |
| cancel sales       | ✓     |       |
| view inventory     | ✓     | ✓     |
| adjust inventory   | ✓     | ✓     |
| view dashboard     | ✓     | ✓     |
| users (all)        | ✓     |       |

## API Base URL
http://localhost:8000/api
