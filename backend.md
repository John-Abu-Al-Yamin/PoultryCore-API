# Poultry Farm & Trading Management System — Backend API

## Overview

PoultryCore is a **Laravel 12** API backend for a poultry farming and trading management system. It manages barns, batches, purchases, sales, expenses, deaths, customers, suppliers, payments, and financial reporting — with full multi-user data isolation.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 |
| Language | PHP 8.2+ |
| API Auth | Laravel Sanctum (token-based) |
| Database | MySQL (configurable) |
| Testing | Pest PHP 3.x |
| Dev Tools | Vite, Tailwind CSS 4, Laravel Pint |

## Project Structure

```
app/
├── Console/Commands/
│   └── RecalculateSupplierDues.php   # suppliers:recalculate-dues
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php        # register, login, logout, user
│   │   ├── BarnController.php        # CRUD
│   │   ├── BatchController.php       # CRUD + close
│   │   ├── SupplierController.php    # CRUD + syncDues
│   │   ├── PurchaseController.php    # CRUD (stock/dues/status sync)
│   │   └── PaymentController.php     # CRUD (cross-entity reconciliation)
│   ├── Requests/
│   │   ├── BaseApiRequest.php        # JSON validation errors
│   │   ├── LoginRequest.php
│   │   ├── RegisterRequest.php
│   │   ├── Barn/{Store,Update}BarnRequest.php
│   │   ├── Batch/{Store,Update}BatchRequest.php
│   │   ├── Supplier/{Store,Update}SupplierRequest.php
│   │   ├── Purchase/{Store,Update}PurchaseRequest.php
│   │   └── Payment/{Store,Update}PaymentRequest.php
│   └── Responses/
│       └── ApiResponse.php           # success(), emptyData(), error()
├── Models/
│   ├── User.php
│   ├── Barn.php
│   ├── Batch.php
│   ├── Supplier.php
│   ├── Purchase.php
│   └── Payment.php
routes/
└── api.php                           # 27 routes (2 public + 25 auth)
database/
├── migrations/                       # 10 migration files
└── database-er-diagram.drawio        # ER diagram (draw.io)
```

## Authentication (Sanctum)

- **Guard:** `web` (session-based for SPA) + Bearer tokens for API
- **Token Storage:** `personal_access_tokens` table
- **Expiration:** None by default
- **Middleware:** `auth:sanctum` for protected routes

### Auth State
- Sanctum installed & configured
- `HasApiTokens` on User model
- Full register/login/logout/user endpoints implemented
- Auth middleware on all business routes

## Database Schema

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned | PK |
| name | varchar(255) | |
| phone | varchar(255) | UNIQUE |
| password | varchar(255) | hashed |
| has_completed_setup | boolean | default: false |
| role | enum('user','admin') | default: 'user' |
| remember_token | varchar(100) | nullable |
| created_at / updated_at | timestamp | |

### `barns`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned | PK |
| user_id | bigint unsigned | FK → users.id ON DELETE CASCADE |
| name | varchar(255) | UNIQUE per (user_id, name) |
| location | varchar(255) | nullable |
| capacity | integer | nullable |
| notes | text | nullable |
| created_at / updated_at | timestamp | |

### `batches`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned | PK |
| user_id | bigint unsigned | FK → users.id ON DELETE CASCADE |
| barn_id | bigint unsigned | FK → barns.id ON DELETE CASCADE |
| poultry_type | varchar(255) | |
| current_quantity | integer | default: 0 |
| start_date | date | |
| end_date | date | nullable |
| status | enum('active','closed') | default: 'active' |
| notes | text | nullable |
| created_at / updated_at | timestamp | |

### `suppliers`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned | PK |
| user_id | bigint unsigned | FK → users.id ON DELETE CASCADE |
| name | varchar(255) | UNIQUE per (user_id, name) |
| phone | varchar(255) | nullable |
| address | varchar(255) | nullable |
| total_dues | decimal(10,2) | default: 0 |
| created_at / updated_at | timestamp | |

### `purchases`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned | PK |
| user_id | bigint unsigned | FK → users.id ON DELETE CASCADE |
| supplier_id | bigint unsigned | FK → suppliers.id ON DELETE CASCADE |
| batch_id | bigint unsigned | FK → batches.id ON DELETE CASCADE |
| item_name | varchar(255) | |
| quantity | integer | |
| unit_price | decimal(10,2) | |
| total_price | decimal(10,2) | |
| paid_amount | decimal(10,2) | default: 0 |
| status | enum('unpaid','partial','paid') | default: 'unpaid' |
| purchase_date | date | |
| payment_type | enum('cash','credit') | |
| created_at / updated_at | timestamp | |

### `payments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned | PK |
| user_id | bigint unsigned | FK → users.id ON DELETE CASCADE |
| type | enum('from_customer','to_supplier') | |
| supplier_id | bigint unsigned | FK → suppliers.id, nullable |
| purchase_id | bigint unsigned | FK → purchases.id, nullable |
| customer_id | bigint unsigned | (unused — future) |
| sale_id | bigint unsigned | (unused — future) |
| amount | decimal(10,2) | |
| payment_date | date | |
| payment_method | varchar(255) | |
| notes | text | nullable |
| created_at / updated_at | timestamp | |

## API Routes

### Public
| Method | URI | Controller | Action |
|--------|-----|------------|--------|
| POST | `/register` | AuthController | register |
| POST | `/login` | AuthController | login |

### Authenticated (`auth:sanctum`)
| Method | URI | Controller | Action |
|--------|-----|------------|--------|
| POST | `/logout` | AuthController | logout |
| GET | `/user` | AuthController | user |
| GET | `/barns` | BarnController | index |
| POST | `/barns` | BarnController | store |
| GET | `/barns/{id}` | BarnController | show |
| PUT | `/barns/{id}` | BarnController | update |
| DELETE | `/barns/{id}` | BarnController | destroy |
| GET | `/batches` | BatchController | index |
| POST | `/batches` | BatchController | store |
| GET | `/batches/{id}` | BatchController | show |
| PUT | `/batches/{id}` | BatchController | update |
| DELETE | `/batches/{id}` | BatchController | destroy |
| POST | `/batches/{id}/close` | BatchController | close |
| GET | `/suppliers` | SupplierController | index |
| POST | `/suppliers` | SupplierController | store |
| GET | `/suppliers/{id}` | SupplierController | show |
| PUT | `/suppliers/{id}` | SupplierController | update |
| DELETE | `/suppliers/{id}` | SupplierController | destroy |
| PUT | `/suppliers/{id}/sync-dues` | SupplierController | syncDues |
| GET | `/purchases` | PurchaseController | index |
| POST | `/purchases` | PurchaseController | store |
| GET | `/purchases/{id}` | PurchaseController | show |
| PUT | `/purchases/{id}` | PurchaseController | update |
| DELETE | `/purchases/{id}` | PurchaseController | destroy |
| GET | `/payments` | PaymentController | index |
| POST | `/payments` | PaymentController | store |
| GET | `/payments/{id}` | PaymentController | show |
| PUT | `/payments/{id}` | PaymentController | update |
| DELETE | `/payments/{id}` | PaymentController | destroy |

## Models

### User
- **Fillable:** `name`, `phone`, `password`, `has_completed_setup`, `role`
- **Hidden:** `password`, `remember_token`
- **Casts:** `password → hashed`, `has_completed_setup → boolean`
- **Relations:** `barns()`, `batches()`, `suppliers()`, `purchases()`, `payments()` (all hasMany)

### Barn
- **Fillable:** `user_id`, `name`, `location`, `capacity`, `notes`
- **Casts:** `capacity → integer`
- **Relations:** `user()` (belongsTo), `batches()` (hasMany)

### Batch
- **Fillable:** `user_id`, `barn_id`, `poultry_type`, `current_quantity`, `start_date`, `end_date`, `status`, `notes`
- **Casts:** `current_quantity → integer`, `start_date → date`, `end_date → date`
- **Relations:** `barn()`, `user()`, `purchases()` (hasMany)

### Supplier
- **Fillable:** `user_id`, `name`, `phone`, `address`, `total_dues`
- **Casts:** `total_dues → decimal:2`
- **Relations:** `user()`, `purchases()`, `payments()`
- **Methods:** `recalculateTotalDues()` — syncs `total_dues` from purchases
- **Accessors:** `duesBalance()` — returns `$this->total_dues`

### Purchase
- **Fillable:** `user_id`, `supplier_id`, `batch_id`, `item_name`, `quantity`, `unit_price`, `total_price`, `paid_amount`, `status`, `purchase_date`, `payment_type`
- **Casts:** `quantity → integer`, `unit_price → decimal:2`, `total_price → decimal:2`, `paid_amount → decimal:2`, `purchase_date → date`
- **Relations:** `user()`, `supplier()`, `batch()`, `payments()` (hasMany)
- **Accessors:** `remainingAmount()` — `max(0, total_price - paid_amount)`
- **Methods:** `recalculateStatus()` — sets status based on paid_amount vs total_price

### Payment
- **Fillable:** `user_id`, `type`, `supplier_id`, `purchase_id`, `amount`, `payment_date`, `payment_method`, `notes`
- **Casts:** `amount → decimal:2`, `payment_date → date`
- **Relations:** `user()`, `supplier()`, `purchase()`

## Business Logic

### Stock (batch.current_quantity)
- **Purchase creates:** `+ quantity`
- **Purchase update (quantity change):** `± diff`
- **Purchase delete:** `− quantity` (capped at 0)
- (Future: Sales, Deaths will also adjust)

### Dues (supplier.total_dues)
- **Credit purchase creates:** `+ total_price`
- **Credit purchase update (total_price change):** `± diff`
- **Credit purchase delete:** `− remaining` (capped at 0)
- **Payment creates (linked to purchase):** `− amount` (capped at 0)
- **Payment update:** unwind old effect + apply new effect
- **Payment delete:** `+ amount` (restores, but purchase paid_amount decreases so net effect is neutral)

### Payment – Purchase Lifecycle
- `paid_amount` = sum of all Payments for that purchase (initialized for cash purchases)
- `status` = recalculated by `recalculateStatus()`:
  - `paid_amount = 0` → `unpaid`
  - `paid_amount < total_price` → `partial`
  - `paid_amount >= total_price` → `paid`
- `paid_amount` never exceeds `total_price` (rejected at update & payment creation)
- `paid_amount` never goes below 0 (all decrements use `max(0, ...)`)
- Paid purchases are frozen (cannot be updated or have payments edited/deleted)

### Payment Creation Flow
1. Validate: purchase (if linked) is not already paid, amount ≤ remaining
2. Increment purchase `paid_amount`
3. Recalculate purchase `status`
4. Decrement supplier `total_dues` (capped at 0) via `max(0, ...)`

### Cash Purchase Flow
If `payment_type = 'cash'` at creation:
- `paid_amount = total_price`, `status = 'paid'`
- Auto-creates a Payment record with `payment_method = 'cash'`
- No `total_dues` increment on supplier

### Key Validation Rules
- supplier_id/batch_id frozen after purchase creation
- total_price cannot be reduced below paid_amount
- Supplier deletion blocked if purchases exist
- Purchase deletion blocked if payments exist or batch is closed
- All exists rules scoped to authenticated user (`where('user_id', auth()->id())`)

## Artisan Commands

| Signature | Description |
|-----------|-------------|
| `suppliers:recalculate-dues` | Recalculate `total_dues` for all suppliers from their purchases |

## Helper: ApiResponse

All API responses follow a consistent JSON envelope via `App\Http\Responses\ApiResponse`:

| Method | Use |
|--------|-----|
| `success($data, $message, $code, $extra)` | `{ success: true, status, message, data }` |
| `emptyData($message, $code)` | `{ success: true, status, message }` |
| `error($message, $code, $errors)` | `{ success: false, status, message, errors }` |

All messages are in Arabic.

## Tests

| File | Purpose |
|------|---------|
| `tests/Pest.php` | Pest config |
| `tests/TestCase.php` | Base TestCase |
| `tests/Unit/ExampleTest.php` | Assert True |
| `tests/Feature/ExampleTest.php` | Stub |

## Configuration

| Config | Key Settings |
|--------|-------------|
| `config/app.php` | Timezone: UTC, Locale: en |
| `config/sanctum.php` | Stateful: localhost:3000, 127.0.0.1; no expiration |
| `config/database.php` | Default: MySQL (from .env) |

## Dependencies

### Production
- `php: ^8.2`, `laravel/framework: ^12.0`, `laravel/sanctum: ^4.0`, `laravel/tinker: ^2.10`

### Dev
- `pestphp/pest: ^3.8`, `pestphp/pest-plugin-laravel: ^3.2`
- `fakerphp/faker: ^1.23`, `laravel/pint: ^1.24`, `laravel/sail: ^1.41`, `mockery/mockery: ^1.6`

## Development Roadmap

### Phase 1: Foundation ✅
- Auth endpoints (register, login, logout, user)
- Form Request validation classes with Arabic messages
- ApiResponse helper class

### Phase 2: Core Domain ✅
- Barns CRUD
- Batches CRUD (with close endpoint)
- Purchases CRUD (with stock increase, dues sync, payment status)
- Suppliers CRUD (with dues tracking)

### Phase 3: Financial ✅
- Payments CRUD (supplier payments linked to purchases)
- Payment reconciliation logic (paid_amount / status sync)

### Phase 4: Remaining Domain Entities
- [ ] Sales CRUD (with stock decrease, customer debt tracking)
- [ ] Customers CRUD
- [ ] Expenses CRUD
- [ ] Deaths CRUD (with stock decrease)

### Phase 5: Reporting & Dashboard
- [ ] Dashboard stats endpoint
- [ ] Profit & Loss report
- [ ] Stock report
- [ ] Debts report (receivables/payables)
- [ ] Batch performance report
- [ ] Barn performance report

### Phase 6: Polish
- [ ] End batch / archive cycle
- [ ] Tests for all endpoints
- [ ] API documentation (Scramble/Scribe)
