# Warranty Management System API

A production-ready RESTful API for SNP Distribution's Warranty Management System built with Laravel 12.

## Features

- **User Authentication**: Laravel Sanctum token-based authentication
- **Role-Based Access Control**: Granular permissions system
- **Brand Management**: Multi-brand warranty tracking
- **Product Categories**: Organized product categorization
- **Warranty Management**: Full warranty lifecycle management
- **Claims Processing**: Convert claims to work orders
- **Work Order Management**: Complete service workflow
- **Service Centers**: Multi-location service support
- **Dashboard Analytics**: Real-time KPI tracking
- **Activity Logging**: Complete audit trail

## Requirements

- PHP 8.2+
- Laravel 12
- MySQL 5.7+
- Composer

## Installation

### 1. Clone and Install Dependencies

```bash
cd warranty-management-system
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Update `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=warranty_system
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. Run Seeders

```bash
php artisan db:seed
```

### 6. Create Storage Link

```bash
php artisan storage:link
```

### 7. Clear Configuration Cache

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Default Login Credentials

```
Email: admin@snpdist.com
Password: Admin@1234
```

## API Base URL

```
https://localhost/warranty-management-system/api
```

## Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | Login with email + password |
| POST | `/auth/logout` | Revoke current token |
| POST | `/auth/forgot-password` | Send password reset OTP |
| POST | `/auth/reset-password` | Reset password via OTP |
| POST | `/auth/change-password` | Change own password |
| GET | `/auth/me` | Get authenticated user profile |
| PUT | `/auth/profile` | Update own profile |

## API Modules

### Users Module
- `GET /api/users` - List all users
- `POST /api/users` - Create user
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Soft delete user

### Roles Module
- `GET /api/roles` - List roles
- `POST /api/roles` - Create role
- `GET /api/roles/{id}` - Get role
- `PUT /api/roles/{id}` - Update role
- `DELETE /api/roles/{id}` - Delete role

### Brands Module
- `GET /api/brands` - List brands
- `POST /api/brands` - Create brand
- `GET /api/brands/{id}` - Get brand
- `PUT /api/brands/{id}` - Update brand
- `DELETE /api/brands/{id}` - Delete brand

### Categories Module
- `GET /api/categories` - List categories
- `POST /api/categories` - Create category

### Warranties Module
- `GET /api/warranties` - List warranties
- `POST /api/warranties` - Create warranty
- `GET /api/warranties/{id}` - Get warranty
- `GET /api/warranties/check/{serial}` - Public warranty check

### Claims Module
- `GET /api/claims` - List claims
- `POST /api/claims` - Create claim
- `POST /api/claims/{id}/convert-to-work-order` - Convert to work order

### Work Orders Module
- `GET /api/work-orders` - List work orders
- `GET /api/work-orders/pending` - List pending work orders
- `GET /api/work-orders/overdue` - List overdue work orders

### Service Centers Module
- `GET /api/service-centers` - List service centers
- `POST /api/service-centers` - Create service center

### Settings Module
- `GET /api/settings` - Get all settings
- `POST /api/settings` - Create/update setting

### Dashboard Module
- `GET /api/dashboard/stats` - Overall statistics
- `GET /api/dashboard/warranty-stats` - Warranty breakdown
- `GET /api/dashboard/claim-stats` - Claims by status

## Postman Collection

Import the Postman collection and environment from:
- `wms.postman_collection.json`
- `warranty-management-system.postman_environment.json`

## XAMPP Configuration

For local development with XAMPP:

1. Create a virtual host in `xampp/apache/conf/extra/httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/warranty-management-system/public"
    ServerName warranty-management-system.local
    <Directory "C:/xampp/htdocs/warranty-management-system/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

2. Add to hosts file:
```
127.0.0.1 warranty-management-system.local
```

3. Restart Apache

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/    # API Controllers
│   ├── Requests/           # Form Requests
│   ├── Resources/         # API Resources
│   └── Middleware/        # Custom Middleware
├── Models/                 # Eloquent Models
├── Traits/                 # Shared Traits
database/
├── migrations/            # Database Migrations
└── seeders/              # Database Seeders
routes/
└── api.php               # API Routes
```

## License

MIT License
# warranty_management_system
# warranty_management_system
