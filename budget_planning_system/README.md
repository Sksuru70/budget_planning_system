# 💰 Budget Planning System

### Full-Stack PHP + MySQL Web Application

### BCA Final Year Project | NSU Jamshedpur

---

## 🚀 Quick Setup (XAMPP)

### Step 1 – Copy project files

Place the `budget_planning_system/` folder inside:

```
C:\xampp\htdocs\budget_planning_system\
```

### Step 2 – Import the database

1. Open **http://localhost/phpmyadmin**
2. Click **"New"** → Database name: `budget_planning_system` → Create
3. Click **Import** tab → Choose file: `db/budget_system.sql` → **Go**

### Step 3 – Configure database (if needed)

Edit `includes/db.php` and update:

```php
define('DB_USER', 'root');      // your MySQL username
define('DB_PASS', '');          // your MySQL password (blank by default in XAMPP)
```

### Step 4 – Run the app

Open browser → **http://localhost/budget_planning_system/**

---

## 🔑 Login Credentials

| Role  | Email            | Password  |
| ----- | ---------------- | --------- |
| User  | demo@budget.com  | demo@123  |
| Admin | admin@budget.com | admin@123 |

---

## 📁 Project Structure

```
budget_planning_system/
├── db/
│   └── budget_system.sql       ← Database schema + sample data
├── includes/
│   ├── db.php                  ← PDO database connection
│   ├── functions.php           ← Auth helpers, flash messages
│   └── sidebar.php             ← Reusable navigation sidebar
├── assets/
│   ├── css/style.css           ← Custom stylesheet
│   └── js/main.js              ← Chart.js helpers, sidebar toggle
├── index.php                   ← Login page
├── register.php                ← User registration
├── dashboard.php               ← Main dashboard with charts
├── transactions.php            ← Add/view/filter transactions
├── budget.php                  ← Budget categories management
├── savings.php                 ← Savings goals tracker
├── reports.php                 ← Financial charts & reports
├── alerts.php                  ← Budget alerts & notifications
├── profile.php                 ← User profile & password change
├── logout.php                  ← Session logout
└── admin/
    └── index.php               ← Admin panel (user management)
```

---

## ✨ Features

### User Module

- ✅ Secure registration & login (bcrypt password hashing)
- ✅ Dashboard with real-time financial overview
- ✅ Add/view/filter income & expense transactions
- ✅ Budget categories with monthly limits & progress bars
- ✅ Savings goals with contribution tracking & achievement detection
- ✅ Interactive charts: Doughnut, Bar, Line (Chart.js)
- ✅ Financial reports — 6-month summary, category breakdown
- ✅ Auto-generated alerts when budget limits are approached/exceeded
- ✅ Profile management & password change

### Admin Module

- ✅ System-wide statistics dashboard
- ✅ User management (activate/deactivate accounts)
- ✅ View all registered users with transaction counts

---

## 🛠 Technology Stack

| Layer     | Technology              |
| --------- | ----------------------- |
| Frontend  | HTML5, CSS3, JavaScript |
| Framework | Bootstrap Icons CDN     |
| Charts    | Chart.js 4.4            |
| Backend   | PHP 8+ (PDO)            |
| Database  | MySQL 5.7+ / MariaDB    |
| Server    | Apache (XAMPP)          |

---

## 📋 Database Tables (6 Tables)

1. **users** – User accounts & authentication
2. **budget_categories** – Monthly budget limits per category
3. **transactions** – Income & expense records
4. **savings_goals** – Financial savings targets
5. **alerts** – Budget limit notifications
6. (admin is part of users table with role='admin')

---

**Guide:** Ritesh Jha | **Dept:** Computer Applications | **NSU Jamshedpur**
