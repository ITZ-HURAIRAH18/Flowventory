# Flowventory — Smart Inventory Management System

A full-stack inventory management system built with **Laravel** (backend) and **Vue.js** (frontend), featuring multi-branch stock management, order processing, and real-time stock tracking.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel (PHP), MySQL |
| Frontend | Vue 3 (Composition API), Vite |
| Auth | Laravel Sanctum (Token-based) |
| API | RESTful JSON API |

## Key Features

- **Multi-Branch Inventory** — Track stock levels independently per branch
- **Product Management** — Full CRUD with SKU, cost/sale price, tax percentage
- **Stock Operations** — Add, adjust, and transfer stock between branches
- **Order Management** — Create orders with automatic stock deduction
- **Stock Movement History** — Complete audit trail of all stock changes
- **Role-Based Access** — Branch managers and users with Sanctum authentication

---

## Concurrency Handling — Preventing Overselling

### The Problem

If two users attempt to purchase the last available stock **simultaneously**, a naive system would oversell:

```
Without protection:

Time    User A                          User B
─────────────────────────────────────────────────────────
T1      Reads stock: qty = 1
T2                                      Reads stock: qty = 1
T3      1 >= 1? ✅ Passes               1 >= 1? ✅ Passes
T4      Deducts: 1-1 = 0, SAVE          Deducts: 1-1 = 0, SAVE
─────────────────────────────────────────────────────────
Result: BOTH orders succeed — 2 items sold but only 1 existed! (OVERSOLD)
```

### Our Solution: Pessimistic Locking with Database Transactions

We use Laravel's `lockForUpdate()` inside a `DB::transaction()` to prevent this. This is called **pessimistic locking** — it tells the database: "Lock this row. No one else can read or modify it until I'm done."

**Implementation** (`OrderController.php`):

```php
return DB::transaction(function () use ($request) {

    foreach ($request->items as $item) {

        // LOCK the inventory row — other transactions WAIT here
        $inventory = Inventory::where('branch_id', $request->branch_id)
            ->where('product_id', $item['product_id'])
            ->lockForUpdate()   // ← Pessimistic lock (SELECT ... FOR UPDATE)
            ->first();

        // Check stock AFTER acquiring the lock
        if (!$inventory || $inventory->quantity < $item['quantity']) {
            throw new Exception('Insufficient stock for product ID: ' . $item['product_id']);
        }

        // Deduct stock (safe — we hold the lock)
        $inventory->quantity -= $item['quantity'];
        $inventory->save();
    }

    // Create order and order items...
});
```

### How It Works — Step by Step

```
With lockForUpdate() protection:

Time    User A                              User B
───────────────────────────────────────────────────────────────
T1      BEGIN TRANSACTION
T2      lockForUpdate() → LOCKS row 🔒
T3      Reads stock: qty = 1               BEGIN TRANSACTION
T4      1 >= 1? ✅ Yes!                     lockForUpdate() → BLOCKED ⏳
T5      Deducts: 1 - 1 = 0                 Waiting for lock... ⏳
T6      COMMIT → UNLOCKS row 🔓            Waiting for lock... ⏳
T7                                          Lock acquired 🔒, reads: qty = 0
T8                                          0 >= 1? ❌ NO!
T9                                          throw "Insufficient stock"
T10                                         ROLLBACK → UNLOCKS row 🔓
───────────────────────────────────────────────────────────────
Result: User A gets the item ✅
        User B gets error: "Insufficient stock" ✅
        NO overselling! The system is safe. 🎉
```

### Why This Works

1. **`DB::transaction()`** — Groups all operations (check stock, deduct stock, create order) into a single atomic unit. If any step fails, ALL changes roll back.

2. **`lockForUpdate()`** — Translates to SQL `SELECT ... FOR UPDATE`. The database engine (MySQL/InnoDB) places an exclusive lock on the matched row. Any other transaction trying to read the same row with `FOR UPDATE` will **wait** until the first transaction commits or rolls back.

3. **Check-then-act safety** — The stock check (`quantity < requested`) happens AFTER the lock is acquired, so the value read is guaranteed to be current and unchangeable by others.

### What Happens in Each Scenario

| Scenario | Result |
|----------|--------|
| 2 users order the last 1 item | First user succeeds, second gets "Insufficient stock" error |
| User orders 5 items but only 3 in stock | Order rejected with error, stock unchanged |
| Order has 3 products, stock check fails on 2nd product | Entire transaction rolls back — 1st product's stock is restored |
| Server crashes mid-transaction | Database auto-rolls back uncommitted transactions |

---

## Database Schema

### Core Tables

```
products          branches          users
├── id            ├── id            ├── id
├── name          ├── name          ├── name
├── sku           ├── address       ├── email
├── cost_price    ├── manager_id    └── password
├── sale_price    └── timestamps
├── tax_percentage
├── status
└── timestamps

inventories (pivot: branch ↔ product)
├── id
├── branch_id → branches.id
├── product_id → products.id
├── quantity
└── timestamps
UNIQUE(branch_id, product_id)

orders                    order_items
├── id                    ├── id
├── branch_id             ├── order_id → orders.id
├── user_id               ├── product_id → products.id
├── subtotal              ├── quantity
├── tax                   ├── price
├── total                 ├── tax
└── timestamps            └── timestamps

stock_movements (audit trail)
├── id
├── branch_id
├── product_id
├── type (add/adjust/transfer_in/transfer_out)
├── quantity
├── note
└── timestamps
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | User login |
| POST | `/api/logout` | User logout |
| GET/POST | `/api/branches` | List / Create branches |
| GET/POST | `/api/products` | List / Create products |
| GET | `/api/inventory` | List all inventory |
| GET | `/api/inventory/branch/{id}/products` | Products with stock at branch |
| POST | `/api/inventory/add` | Add stock |
| POST | `/api/inventory/adjust` | Adjust stock |
| POST | `/api/inventory/transfer` | Transfer between branches |
| GET | `/api/inventory/history` | Stock movement history |
| POST | `/api/orders` | Create order (with stock deduction) |

## Setup

### Backend (Laravel)

```bash
cd smart-inventory
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend (Vue)

```bash
cd frontend
npm install
npm run dev
```
