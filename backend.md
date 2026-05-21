# 🐔 Poultry Farm & Trading Management System — Backend API

## 📋 Overview

PoultryCore is a **Laravel 12** API backend for a poultry farming and trading management system. It manages barns, batches, purchases, sales, expenses, deaths, customers, suppliers, payments, and financial reporting — with full multi-user data isolation.

---

## 🏗️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 |
| Language | PHP 8.2+ |
| API Auth | Laravel Sanctum (token-based) |
| Database | MySQL (configurable: SQLite, PostgreSQL) |
| Testing | Pest PHP 3.x |
| Dev Tools | Vite, Tailwind CSS 4, Laravel Pint |

---

## 📁 Project Structure

```
api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Controller.php          # Base controller
│   │       └── AuthController.php      # Authentication (stub)
│   ├── Models/
│   │   └── User.php                    # User model with Sanctum
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
├── config/
│   ├── sanctum.php                     # Sanctum configuration
│   ├── auth.php                        # Auth guards & providers
│   ├── database.php                    # DB connections
│   └── ...
├── database/
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   └── 2026_05_19_165722_create_personal_access_tokens_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── routes/
│   ├── api.php                         # API routes
│   ├── web.php
│   └── console.php
├── tests/
│   ├── Feature/
│   ├── Unit/
│   ├── Pest.php
│   └── TestCase.php
├── composer.json
└── package.json
```

---

## 🔐 Authentication (Sanctum)

- **Guard:** `web` (session-based for SPA) + Bearer tokens for API
- **Token Storage:** `personal_access_tokens` table (morphs to any model)
- **Expiration:** None by default (configurable via `expiration` in `config/sanctum.php`)
- **Middleware:** `auth:sanctum` for protected routes

### Current Auth State

- ✅ Sanctum installed & configured
- ✅ `HasApiTokens` trait on User model
- ✅ `personal_access_tokens` migration created
- ✅ Auth-protected route group in `routes/api.php`
- ❌ No register/login endpoints implemented
- ❌ `AuthController@logout` referenced but not implemented

---

## 🗄️ Database Schema (Current — 4 migrations)

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned | PK, auto-increment |
| name | varchar(255) | |
| email | varchar(255) | UNIQUE |
| email_verified_at | timestamp | nullable |
| password | varchar(255) | hashed |
| remember_token | varchar(100) | nullable |
| created_at / updated_at | timestamp | |

### `personal_access_tokens`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned | PK |
| tokenable_type / tokenable_id | varchar(255) / bigint | Polymorphic morph |
| name | varchar(255) | |
| token | varchar(64) | UNIQUE |
| abilities | text | nullable |
| last_used_at | timestamp | nullable |
| expires_at | timestamp | nullable, indexed |
| created_at / updated_at | timestamp | |

Also includes standard Laravel tables: `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

---

## 🧭 API Routes

### Public
| Method | URI | Purpose |
|--------|-----|---------|
| GET | `/api/test-1` | Health check |

### Authenticated (`auth:sanctum`)
| Method | URI | Controller | Status |
|--------|-----|-----------|--------|
| POST | `/api/logout` | `AuthController@logout` | ❌ Not implemented |
| GET | `/api/test` | Closure | ✅ Working |

---

## 📦 Models

### `App\Models\User`
- **Traits:** `HasFactory`, `Notifiable`, `HasApiTokens`
- **Fillable:** `name`, `email`, `password`
- **Hidden:** `password`, `remember_token`
- **Casts:** `email_verified_at → datetime`, `password → hashed`
- **Relationships:** None defined yet
- **Future:** Will have `hasMany` relationships to Barns, Batches, Customers, Suppliers, etc.

---

## 🎯 Domain Entities (To Build)

Based on the PRD, the following domain models need to be implemented:

### 1. Barns (`barns` table)
- `id`, `user_id`, `name`, `location`, `capacity`, `notes`
- `user_id` FK → `users.id`
- **Relations:** BelongsTo User, HasMany Batches

### 2. Batches (`batches` table)
- `id`, `user_id`, `barn_id`, `poultry_type`, `current_quantity`, `initial_quantity`, `start_date`, `end_date`, `status` (active/closed), `notes`
- `user_id` FK → `users.id`, `barn_id` FK → `barns.id`
- **Relations:** BelongsTo Barn, HasMany Expenses, HasMany Sales, HasMany Purchases, HasMany Deaths

### 3. Expenses (`expenses` table)
- `id`, `user_id`, `batch_id`, `type` (feed/treatment/medicine/tools/electricity/transport), `amount`, `date`, `notes`
- `user_id` FK → `users.id`, `batch_id` FK → `batches.id`

### 4. Deaths (`deaths` table)
- `id`, `user_id`, `batch_id`, `quantity`, `reason`, `date`
- **Effect:** Reduces `batches.current_quantity`

### 5. Customers (`customers` table)
- `id`, `user_id`, `name`, `phone`, `address`, `total_debts`
- `user_id` FK → `users.id`
- **Relations:** HasMany Sales, HasMany Payments (as receiver)

### 6. Suppliers (`suppliers` table)
- `id`, `user_id`, `name`, `phone`, `address`, `total_dues`
- `user_id` FK → `users.id`
- **Relations:** HasMany Purchases, HasMany Payments (as receiver)

### 7. Sales (`sales` table)
- `id`, `user_id`, `batch_id`, `customer_id`, `quantity`, `unit_price`, `total_price`, `sale_date`, `payment_type` (cash/credit), `remaining_amount`, `payment_status` (paid/partial/unpaid)
- `user_id` FK → `users.id`, `batch_id` FK → `batches.id`, `customer_id` FK → `customers.id`
- **Effect:** Reduces `batches.current_quantity`, creates customer debt if credit

### 8. Purchases (`purchases` table)
- `id`, `user_id`, `batch_id`, `supplier_id`, `poultry_type`, `quantity`, `unit_price`, `total_price`, `purchase_date`, `payment_type` (cash/credit), `remaining_amount`, `payment_status` (paid/partial/unpaid)
- `user_id` FK → `users.id`, `batch_id` FK → `batches.id`, `supplier_id` FK → `suppliers.id`
- **Effect:** Increases `batches.current_quantity`

### 9. Payments (`payments` table)
- `id`, `user_id`, `type` (from_customer/to_supplier), `customer_id` (nullable), `supplier_id` (nullable), `amount`, `payment_date`, `payment_method`, `notes`
- **Relations:** MorphTo or dual nullable FKs to customers/suppliers

---

## 📐 Business Logic Formulas

| Calculation | Formula |
|------------|---------|
| Total Cost | `Purchases + Expenses + Death Losses` |
| Payables | `Purchase Debts + Expenses` |
| Receivables | `Credit Sales - Customer Payments` |
| Profit | `Revenue - (Purchases + Expenses)` |
| Current Stock | `Initial Quantity + Purchases - Deaths - Sales` |

---

## 🧪 Tests

| File | Purpose |
|------|---------|
| `tests/Pest.php` | Pest config (RefreshDatabase commented out) |
| `tests/TestCase.php` | Base TestCase (empty) |
| `tests/Unit/ExampleTest.php` | `assertTrue(true)` |
| `tests/Feature/ExampleTest.php` | `GET /` → 200 |

**Test config** (`phpunit.xml`): SQLite in-memory, array cache/session, sync queue.

---

## 🔧 Configuration Summary

| Config File | Key Settings |
|-------------|-------------|
| `config/app.php` | Timezone: UTC, Locale: en |
| `config/auth.php` | Default guard: web, provider: users (Eloquent) |
| `config/sanctum.php` | Stateful domains: localhost:3000, 127.0.0.1; no expiration |
| `config/database.php` | Default: MySQL (from .env) |
| `.env` | DB: mysql, host 127.0.0.1, database: api, user: root, no password |

---

## 📦 Dependencies

### Production
- `php: ^8.2`
- `laravel/framework: ^12.0`
- `laravel/sanctum: ^4.0`
- `laravel/tinker: ^2.10.1`

### Dev
- `pestphp/pest: ^3.8`, `pestphp/pest-plugin-laravel: ^3.2`
- `fakerphp/faker: ^1.23`
- `laravel/pint: ^1.24`
- `laravel/sail: ^1.41`
- `mockery/mockery: ^1.6`

---

## 🔜 Development Roadmap

### Phase 1: Foundation
- [ ] Implement Auth endpoints (register, login, logout, me)
- [ ] Create Form Request validation classes
- [ ] Create API Resource classes

### Phase 2: Core Domain
- [ ] Barns CRUD
- [ ] Batches CRUD
- [ ] Purchases CRUD (with stock increase logic)
- [ ] Sales CRUD (with stock decrease logic)
- [ ] Expenses CRUD
- [ ] Deaths CRUD (with stock decrease logic)

### Phase 3: Financial
- [ ] Customers CRUD (with debt tracking)
- [ ] Suppliers CRUD (with dues tracking)
- [ ] Payments CRUD (collections & supplier payments)
- [ ] Payment reconciliation logic

### Phase 4: Reporting & Dashboard
- [ ] Dashboard stats endpoint
- [ ] Profit & Loss report
- [ ] Stock report
- [ ] Debts report (receivables/payables)
- [ ] Batch performance report
- [ ] Barn performance report

### Phase 5: Polish
- [ ] Data isolation middleware/scopes
- [ ] End batch / archive cycle
- [ ] Tests for all endpoints
- [ ] API documentation (Scramble/Scribe)
