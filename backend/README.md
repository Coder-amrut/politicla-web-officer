# Field Management Platform

This repository contains the complete project codebase:
- **`field-management-api/`** — Laravel 13 Backend API (Sanctum, Roles, Projects, Geographies)
- **`political-web/`** — React 18 + Vite Frontend
- **`field_management_final_schema.sql`** — Complete MySQL Database Schema & Reference Data
- **`Complete_Features_Marathi (1).pdf`** — Client Requirements Document

---

## 🚀 Quick Setup Guide

### 1. Database Setup (MySQL)
Open MySQL / phpMyAdmin and create the database:
```sql
CREATE DATABASE field_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import the initial schema:
```bash
mysql -u root -p --default-character-set=utf8mb4 field_management < ../field_management_final_schema.sql
```

---

### 2. Backend Setup (`field-management-api`)

Inside the `field-management-api` folder:

1. **Install PHP dependencies:**
   ```bash
   composer install
   ```

2. **Setup environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Edit `.env` and verify your MySQL credentials (`DB_DATABASE=field_management`, `DB_USERNAME`, `DB_PASSWORD`).*

3. **Verify backend tests:**
   ```bash
   php artisan test
   ```
   *(All 77 tests will pass!)*

4. **Start the backend server:**
   ```bash
   php artisan serve
   ```
   *API will run at `http://127.0.0.1:8000`*

---

### 3. Frontend Setup (`political-web`)

Inside the `political-web` folder:

1. **Install Node dependencies:**
   ```bash
   npm install
   ```

2. **Setup environment:**
   ```bash
   cp .env.example .env
   ```

3. **Start the frontend development server:**
   ```bash
   npm run dev
   ```
   *App will run at `http://localhost:5173` and communicate with `http://127.0.0.1:8000/api`.*
