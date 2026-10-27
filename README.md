# Procurement & Warehouse Management System

A complete PHP/MySQL/Bootstrap web application for **Khawar Construction Company**, built for XAMPP on Windows.

## Features

- **Role-based access** across 9 roles, including a separate Contracting Department Reviewer
- **Requests workflow**: request submission → committee assignment → separate Procurement, Contracting, Finance, and request-specific department decisions → warehouse check (purchase) or rental contract flow → completion
- **Purchases & quotations**: multi-supplier quotes, committee approval, purchase finalization
- **Equipment rentals**: separate rental requests, contracts, handover / return, extensions and payment history
- **Warehouse management**: inventory, categories, item CRUD, low-stock alerts
- **Gate receipts**: physical receipt checklist, condition check
- **Consumptions log**: warehouse vs direct delivery tracking
- **Reports**: purchases, consumption, current inventory, cost analysis with filters
- **Admin**: user & department management

## Quick Start

1. Place the project folder under `c:\xampp\htdocs\ProcurementWarehouseMS`
2. Open `http://localhost/ProcurementWarehouseMS/install.php` to install / reset the database. The installer drops and recreates the application's tables, so do not run it on a database whose data you need to keep.
3. Login with the seeded admin account:
   - Username: `admin`
   - Password: `admin123`

Other seeded demo usernames are `procurement`, `warehouse`, `gate`, `committee`, `gm`, `employee`, `finance`, and `contracting`; their password is `password`.

### Existing installations

For an existing database, apply the required upgrade scripts in order, ending with `database/upgrade_v8.sql` after `upgrade_v7.sql`. Back up the database before applying upgrades. The installer creates all workflow tables automatically for a fresh installation.
On XAMPP, run the applicable upgrade from Command Prompt, for example: `C:\xampp\mysql\bin\mysql.exe -u root procurement_warehouse_ms < C:\xampp\htdocs\ProcurementWarehouseMS\database\upgrade_v8.sql`.
After upgrading an existing database, use **Admin → Users** to create or assign a user the new **Contracting Department Reviewer** role; fresh installs seed a demo `contracting` account with password `password`.

`database/procurement_warehouse.sql` is the schema and seed script used by the installer. `database/procurement_warehouse_ms.sql` is a separate SQL dump and is not loaded by the installer; use it only when intentionally restoring that dump.

## Project Structure

```
ProcurementWarehouseMS/
├── database/
│   ├── procurement_warehouse.sql
│   ├── procurement_warehouse_ms.sql
│   └── upgrade_v8.sql
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
│   ├── review.php
│   ├── committee_queue.php
│   └── committee_vote.php
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
├── rentals/
│   ├── list.php
│   ├── create.php
│   └── view.php
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

1. **Requester** submits a procurement or rental request
2. **Committee coordinator** selects the request-specific fourth department and assigns its reviewer
3. **Procurement, Contracting, Finance, and the assigned department reviewer** each approve or reject independently; only four approvals allow continuation, while any rejection closes the request
4. An approved purchase goes to **Warehouse Manager** for stock check; an approved rental follows the existing rental-contract route
5. **Procurement Manager** collects quotations from at least 3 different suppliers
6. **Committee** handles the existing purchase-stage decision
7. **Procurement Manager** selects the final supplier and price; **General Manager** approves, then **Finance** records the funds handover
8. **Procurement Manager** registers the order; **Gate Security** records the receipt checklist
9. **Warehouse / Departments** store or receive the goods and confirm the handover

For **temporary rentals**, the requester selects rental on the request form. After all four committee approvals, procurement registers the separate contract; warehouse/procurement records handover and return, while finance/procurement tracks payments and procurement records extensions. Rental equipment is never added to warehouse inventory.

## Notes

- Passwords are hashed with `password_hash()`. Seeded admin credentials are `admin` / `admin123`; the other demo accounts use `password`. Change demo passwords before using the system with real data.
- `BASE_URL` is auto-detected from the URL path so the app works from any subfolder.
- All tables are created and seeded by `install.php`.
