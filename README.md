# Procurement & Warehouse Management System

A complete PHP/MySQL/Bootstrap web application for **Khawar Construction Company**, built for XAMPP on Windows.

## Features

- **Role-based access** across 7 roles: Employee, Procurement Manager, Warehouse Manager, Gate Security, Committee Member, General Manager, Administrator
- **Requests workflow**: draft → review → warehouse check → purchase required → quotation → committee → approval → purchase → gate receipt → completion
- **Purchases & quotations**: multi-supplier quotes, committee approval, purchase finalization
- **Warehouse management**: inventory, categories, item CRUD, low-stock alerts
- **Gate receipts**: physical receipt checklist, condition check
- **Consumptions log**: warehouse vs direct delivery tracking
- **Reports**: purchases, consumption, current inventory, cost analysis with filters
- **Admin**: user & department management

## Quick Start

1. Place the project folder under `c:\xampp\htdocs\ProcurementWarehouseMS`
2. Open `http://localhost/ProcurementWarehouseMS/install.php` to install / reset the database
3. Login with the seeded admin account:
   - Email: `admin@khawar.com`
   - Password: `password`

## Project Structure

```
ProcurementWarehouseMS/
├── database/
│   └── procurement_warehouse.sql
├── includes/
│   ├── auth.php
│   ├── config.php
│   ├── functions.php
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── dash_*.php
├── assets/
│   ├── css/style.css
│   └── js/main.js
├── requests/
│   ├── create.php
│   ├── list.php
│   ├── view.php
│   └── review.php
├── warehouse/
│   ├── check.php
│   ├── categories.php
│   ├── disposition.php
│   ├── inventory.php
│   └── item_edit.php
├── purchases/
│   ├── create.php
│   ├── list.php
│   ├── view.php
│   └── committee.php
├── gate/
│   └── receipts.php
├── suppliers/
│   └── list.php
├── consumptions/
│   └── list.php
├── reports/
│   ├── index.php
│   ├── purchases.php
│   ├── consumption.php
│   ├── inventory.php
│   └── cost_analysis.php
├── admin/
│   ├── users.php
│   └── departments.php
├── index.php
├── login.php
├── logout.php
└── install.php
```

## Workflow Summary

1. **Employee** submits a procurement request
2. **Procurement Manager** reviews and approves (or routes to warehouse check / purchase required)
3. **Warehouse Manager** checks stock; if insufficient, marks "purchase required"
4. **Procurement Manager** collects ≥3 quotations
5. **Committee** approves or rejects
6. **Procurement Manager** finalizes order (supplier, price, date)
7. **Gate Security** records receipt checklist
8. **Warehouse / Departments** consume stock (warehouse issuance or direct delivery)

## Notes

- Passwords are hashed with `password_hash()`. Default admin password is `password`.
- `BASE_URL` is auto-detected from the URL path so the app works from any subfolder.
- All tables are created and seeded by `install.php`.
