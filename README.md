# 📦 Flowventory - Smart Inventory & Order Management System

<div align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/Vue.js-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Sanctum-Security-blue?style=for-the-badge" />
</div>

---

## 🌟 Introduction

**Flowventory** is a state-of-the-art, multi-branch inventory and order management solution designed for modern enterprises. It provides a seamless experience for managing products, tracking real-time stock levels across multiple locations, and processing complex orders with guaranteed data integrity.

Built with **Laravel** on the backend and **Vue.js 3** on the frontend, Flowventory offers a high-performance, responsive, and secure platform for business operations.

---

## 🚀 Core Features

-   **Multi-Branch Support**: Manage inventory across different locations with localized tracking.
-   **Role-Based Access Control (RBAC)**: Fine-grained permissions for Super Admins, Branch Managers, and Sales Specialists.
-   **Smart Inventory**: Real-time stock levels with support for Add, Adjust, and Transfer operations.
-   **Atomic Order Processing**: Database-level locking to prevent overselling in high-concurrency environments.
-   **Professional Reporting**: Integrated dashboards for branch-specific and global performance metrics.
-   **Premium UI**: Modern interface with a focused brown/dark theme for a professional look.

---

## 🛠 Tech Stack

| Component | Technology |
| :--- | :--- |
| **Frontend** | Vue.js 3 (Composition API), Vite, Vue Router, Axios |
| **Backend** | Laravel 10+, PHP 8.1+ |
| **Database** | MySQL |
| **Security** | Laravel Sanctum (Token Auth) |
| **Design** | Vanilla CSS (Custom UI Component Library) |

---

## 🔐 Role-Based Access Control (RBAC)

The system enforces strict access levels using middleware:

| Feature | Super Admin | Branch Manager | Sales User |
| :--- | :---: | :---: | :---: |
| User Management (CRUD) | ✅ | ❌ | ❌ |
| Branch Management (CRUD) | ✅ | ❌ | ❌ |
| Product Management (CRUD) | ✅ | ❌ | ❌ |
| View All Inventory | ✅ | ❌ | ❌ |
| Manage Branch Stock | ✅ | ✅ (Own Only) | ❌ |
| Create Orders | ❌ | ✅ | ✅ |
| Global Reports | ✅ | ❌ | ❌ |
| Branch Reports | ✅ | ✅ (Own Only) | ❌ |

---

## 📥 Installation & Setup

### Prerequisites
- PHP >= 8.1
- Composer
- Node.js & npm
- MySQL

### Backend Setup (Laravel)
1. Navigate to `smart-inventory/`.
2. Run `composer install`.
3. Copy `.env.example` to `.env` and configure your database.
4. Run migrations and seed data: `php artisan migrate --seed`.
5. Generate app key: `php artisan key:generate`.
6. Start server: `php artisan serve`.

### Frontend Setup (Vue.js)
1. Navigate to `frontend/`.
2. Run `npm install`.
3. Start development server: `npm run dev`.

---

## 🔑 Sample Login Credentials

| Role | Email | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin@example.com` | `password123` |
| **Branch Manager** | `manager@example.com` | `password123` |
| **Sales User** | `sales@example.com` | `password123` |

---

## 📖 API Documentation (Postman Style)

All requests (except Login) require the following headers:
`Authorization: Bearer {YOUR_TOKEN}`
`Accept: application/json`

### 1. Authentication
**POST** `/api/login`
```json
{
    "email": "admin@example.com",
    "password": "password"
}
```

---

### 2. Branch Management (Admin Only)
**GET** `/api/branches` - List all branches  
**POST** `/api/branches` - Create a branch
```json
{
    "name": "Downtown Branch",
    "address": "123 Main St, New York",
    "manager_id": 2
}
```

**PUT** `/api/branches/{id}` - Update branch info  
**DELETE** `/api/branches/{id}` - Delete a branch  

---

### 3. Product Management (Admin Only)
**GET** `/api/products` - List all products  
**POST** `/api/products` - Add new product
```json
{
    "name": "Wireless Mouse",
    "sku": "WM-001",
    "cost_price": 1500,
    "sale_price": 2500,
    "tax_percentage": 5,
    "status": "active"
}
```

**PUT** `/api/products/{id}` - Edit product details  
**DELETE** `/api/products/{id}` - Remove product  

---

### 4. User Management (Admin Only)
**GET** `/api/users` - List all users  
**POST** `/api/users` - Create new user account
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role_id": 2
}
```

**PUT** `/api/users/{id}` - Update user status/role  
**DELETE** `/api/users/{id}` - Remove user access  

---

### 5. Inventory Operations (Admin & Manager)
**GET** `/api/inventory` - Get real-time stock list  
**GET** `/api/inventory/stats` - Inventory summary metrics  
**GET** `/api/inventory/history` - Stock movement logs  

**POST** `/api/inventory/add` - Add stock quantity
```json
{
    "branch_id": 1,
    "product_id": 5,
    "quantity": 50
}
```

**POST** `/api/inventory/adjust` - Adjust stock (positive/negative)
```json
{
    "branch_id": 1,
    "product_id": 5,
    "quantity": -2 
}
```

**POST** `/api/inventory/transfer` - Move stock between branches
```json
{
    "from_branch_id": 1,
    "to_branch_id": 2,
    "product_id": 5,
    "quantity": 10
}
```

---

### 6. Order Management (Manager & Sales)
**POST** `/api/orders` - Process a customer order
```json
{
    "branch_id": 1,
    "items": [
        { "product_id": 1, "quantity": 2 },
        { "product_id": 3, "quantity": 1 }
    ]
}
```
*Note: Atomic transactions ensure stock is deducted safely.*

---

### 7. Reports & Analytics
**GET** `/api/report/summary` - Aggregated business metrics  
**GET** `/api/branches/{id}/report` - Deep-dive branch metrics  

---

## 🛡️ Security & Integrity
- **Sanctum Authentication**: Secure token-based access.
- **Form Requests**: Server-side validation for all inputs.
- **Pessimistic Locking**: `lockForUpdate()` prevents stock race conditions.

## 📝 License
This project is licensed under the MIT License.
