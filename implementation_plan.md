# HappyCRM — Happy Bangladesh ERP Full Build Plan

## Overview

Build a complete PHP 8+ / MySQL CRM/ERP system for **Happy Bangladesh** from scratch in `c:\xampp\htdocs\happycrm2\`. The system manages companies, dealers, warehouses, managers, SRs, DSRs, product lots, QR code generation, orders, delivery, returns, inventory, expenses, and cash settlements.

**Stack:** PHP 8+, MySQL, Tailwind CSS (CDN), Vanilla JS, AJAX, XAMPP

---

## Open Questions

> [!IMPORTANT]
> **None** — the build plan v3-1 is fully specified. Proceeding to full implementation.

---

## Proposed Changes

The project starts from a clean directory (only the plan `.md` file exists). All files below will be **[NEW]**.

---

### Phase 1 — Foundation

#### [NEW] `config/db.php`
PDO MySQL connection with error handling, charset UTF-8.

#### [NEW] `config/session.php`
Session start, role guard function (`requireRole()`), redirect helpers.

#### [NEW] `index.php`
Login page — Tailwind styled, email + password form, AJAX POST to `api/auth.php`.

#### [NEW] `logout.php`
Destroy session and redirect to login.

#### [NEW] `includes/header.php`
HTML head + Tailwind CDN + Chart.js + html5-qrcode + qrcode.js CDN links.

#### [NEW] `includes/sidebar.php`
Dynamic sidebar rendering by role (admin/manager/dsr/dealer).

#### [NEW] `includes/navbar.php`
Top navbar with user name, role badge, logout button.

#### [NEW] `includes/footer.php`
Closing tags.

#### [NEW] `assets/css/app.css`
Custom styles on top of Tailwind (progress bars, sticker print CSS, etc.).

#### [NEW] `assets/js/app.js`
Global JS: `showToast()`, `api()` fetch helper, `formatQty()`.

#### [NEW] `assets/js/qr.js`
Camera QR scanner (html5-qrcode) + USB keyboard-wedge listener.

#### [NEW] `database/happy_bangladesh.sql`
Complete SQL schema + default admin user (bcrypt password).

---

### Phase 2 — Admin Panel

#### [NEW] `api/auth.php`
Login handler — verify credentials, set session, return JSON.

#### [NEW] `api/warehouses.php`, `api/dealers.php`, `api/companies.php`
#### [NEW] `api/managers.php`, `api/sr.php`, `api/dsr.php`, `api/routes.php`
CRUD AJAX endpoints (GET list / POST create / PUT update / DELETE).

#### [NEW] `admin/index.php`
Dashboard with stat cards + Chart.js charts + recent orders/dispatches tables.

#### [NEW] `admin/warehouses.php`, `admin/dealers.php`, `admin/companies.php`
#### [NEW] `admin/managers.php`, `admin/sr.php`, `admin/dsr.php`, `admin/routes.php`
HTML table list + Add/Edit modal (AJAX) + Delete confirm.

---

### Phase 3 — Manager Panel: Products & Categories

#### [NEW] `api/categories.php`, `api/products.php`
CRUD endpoints with file upload support for product images.

#### [NEW] `manager/categories.php`
List + Add/Edit/Delete modal.

#### [NEW] `manager/products.php`
Product list with image, name, company, category, pieces/box, price + CRUD modals.

---

### Phase 4 — Manager Panel: Lots

#### [NEW] `api/lots.php`
Create lot → insert lot + lot_items + update inventory. NO QR generation here.

#### [NEW] `manager/lot_add.php`
Dynamic form: company select → product rows → grand total auto-calc → submit.

#### [NEW] `manager/lots.php`
Table with Generate QR / Print QR / View / Edit / Delete actions.

#### [NEW] `manager/lot_view.php`
Invoice layout with print button.

---

### Phase 5 — QR Generation

#### [NEW] `api/qr.php`
Actions: `lot_products`, `generate` (serial QR codes), `fetch` (for print), `scan` (delivery).

#### [NEW] `manager/qr_generate.php`
Lot → Product select → auto-fill qty → Generate button → QR grid display.

---

### Phase 6 — QR Print Page

#### [NEW] `manager/qr_print.php`
Lot/Product select → sticker preview → print (75×100mm thermal label CSS).

---

### Phase 7 — Orders

#### [NEW] `api/orders.php`
Actions: list (with filters), `sr_products`, create order.

#### [NEW] `manager/orders.php`
Stat cards + filters + product-level table with delivery ratio badges.

---

### Phase 8 — Add Order

#### [NEW] `manager/order_add.php`
SR select → product rows → qty input → Save Order.

---

### Phase 9 — Delivery

#### [NEW] `api/delivery.php`
Actions: `scan_box` (validate QR), `complete` (create dispatch record).

#### [NEW] `manager/delivery.php`
Order + DSR select → QR scan (camera+USB) → animated progress bars → Complete button.

---

### Phase 10 — Returns

#### [NEW] `api/returns.php`
Scan return / custom return / complete return logic.

#### [NEW] `manager/returns.php`
Select dispatch → scan or custom return modal → update inventory.

#### [NEW] `manager/return_custom.php`
Custom return modal logic.

---

### Phase 11 — DSR Panel

#### [NEW] `dsr/index.php`
DSR dashboard.

#### [NEW] `dsr/expenses.php`
Add expenses form.

#### [NEW] `manager/expenses.php`
Approve/Reject DSR expenses.

#### [NEW] `api/expenses.php` (in api folder, or handled in expenses.php)

---

### Phase 12 — Cash Settlement

#### [NEW] `manager/cashflow.php`
Select dispatch → expected/submitted amounts → auto difference → save.

---

### Phase 13 — Inventory

#### [NEW] `api/inventory.php`
Stock queries by warehouse/company/category.

#### [NEW] `manager/inventory.php`
Table: Product | Company | Category | Boxes | Pieces | Last Updated. Low stock highlighting.

---

### Phase 14 — Dealer Panel

#### [NEW] `dealer/index.php`
Profit + reports view.

---

### Phase 15 — Reports

#### [NEW] `admin/reports.php`
Admin reporting page.

---

## Verification Plan

### Automated Tests
- Run XAMPP MySQL → import `database/happy_bangladesh.sql`
- Browse to `http://localhost/happycrm2/` → verify login page loads
- Login with admin credentials → verify dashboard loads
- Use browser subagent to test login flow and page navigation

### Manual Verification
- Test adding warehouse, dealer, company, manager, SR, DSR
- Test lot creation + QR generation + print preview
- Test order creation + delivery scan flow
- Test return flow + inventory updates

---

## Build Order Summary

| Phase | Files | Status |
|-------|-------|--------|
| 1 | Foundation (config, includes, login) | ⬜ |
| 2 | Admin panel (8 pages + 7 APIs) | ⬜ |
| 3 | Products & Categories | ⬜ |
| 4 | Lots | ⬜ |
| 5 | QR Generate | ⬜ |
| 6 | QR Print | ⬜ |
| 7 | Orders list | ⬜ |
| 8 | Add Order | ⬜ |
| 9 | Delivery | ⬜ |
| 10 | Returns | ⬜ |
| 11 | DSR Expenses | ⬜ |
| 12 | Cash Settlement | ⬜ |
| 13 | Inventory | ⬜ |
| 14 | Dealer Panel | ⬜ |
| 15 | Admin Dashboard Charts | ⬜ |

**Total estimated files: ~60+ PHP files**
