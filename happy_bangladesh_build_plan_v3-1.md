# Happy Bangladesh — Full Build Plan (v3)
**Stack:** PHP 8+, MySQL, Tailwind CSS (CDN), Vanilla JS, AJAX, XAMPP (dev) → cPanel (prod)

> **Changes in v2:**
> - ❌ Retailer completely removed (table, pages, API, sidebar)
> - ❌ SR order placement page removed
> - ❌ SR panel removed entirely
> - ✅ QR generation is now a separate page (`manager/qr_generate.php`)
> - ✅ QR print is a separate page (`manager/qr_print.php`)
> - ✅ Lot add no longer auto-generates QR codes
> - ✅ Orders page redesigned with product-level table + stat cards + QR scan order entry

> **Changes in v3:**
> - ✅ SR role brought back (admin manages SR) — needed to assign orders to an SR
> - ✅ Add Order page: manual bulk product entry (select SR → select products → enter qty) — no QR scanning on order entry
> - ❌ Separate dispatch scan page removed (`order_scan.php` removed)
> - ✅ Delivery page (`delivery.php`) now does everything: select SR order → scan QR boxes → fills progress bar → complete sends to van

---

## 1. PROJECT FOLDER STRUCTURE

```
/happy/
├── index.php                  ← Login page
├── logout.php
├── config/
│   ├── db.php                 ← PDO MySQL connection
│   └── session.php            ← Auth check + role guard
├── assets/
│   ├── css/
│   │   └── app.css            ← Custom styles on top of Tailwind
│   ├── js/
│   │   ├── app.js             ← Global JS helpers
│   │   └── qr.js              ← QR scanner (html5-qrcode lib)
│   └── img/
├── includes/
│   ├── header.php             ← HTML head + Tailwind CDN
│   ├── sidebar.php            ← Dynamic sidebar by role
│   ├── navbar.php
│   └── footer.php
│
├── admin/
│   ├── index.php              ← Dashboard + charts
│   ├── companies.php
│   ├── dealers.php
│   ├── warehouses.php
│   ├── managers.php
│   ├── dsr.php
│   ├── routes.php
│   └── reports.php
│
├── manager/
│   ├── index.php              ← Dashboard
│   ├── products.php           ← Product list + add/edit
│   ├── categories.php
│   ├── lots.php               ← Lot list table
│   ├── lot_add.php            ← Add lot form (NO auto QR generation)
│   ├── lot_view.php           ← Invoice view
│   ├── qr_generate.php        ← Generate QR codes (separate page)
│   ├── qr_print.php           ← Print QR stickers (separate page)
│   ├── orders.php             ← Orders list + stat cards
│   ├── order_add.php          ← Manual bulk order entry (select SR + products + qty)
│   ├── delivery.php           ← Out for Delivery: scan QR boxes → van loading + dispatch
│   ├── returns.php            ← Back product panel
│   ├── return_custom.php      ← Custom QR return modal logic
│   ├── inventory.php
│   ├── cashflow.php
│   └── expenses.php
│
├── dsr/
│   ├── index.php              ← Dashboard
│   └── expenses.php           ← Add expenses
│
├── dealer/
│   └── index.php              ← Profit + reports view
│
└── api/                       ← AJAX endpoints (return JSON)
    ├── auth.php               ← Login handler
    ├── companies.php          ← CRUD
    ├── dealers.php
    ├── warehouses.php
    ├── managers.php
    ├── dsr.php
    ├── routes.php
    ├── products.php
    ├── categories.php
    ├── lots.php
    ├── qr.php                 ← Generate, fetch, scan, print QR
    ├── orders.php             ← Place order (manual), list, filter
    ├── delivery.php           ← QR scan → dispatch + van loading combined
    ├── returns.php            ← Back products logic
    └── inventory.php          ← Stock queries
```

**Removed files (vs v1):**
- `manager/retailers.php` ❌
- `api/retailers.php` ❌
- `manager/order_scan.php` ❌ (replaced by delivery.php)
- `api/dispatch.php` ❌ (merged into api/delivery.php)

---

## 2. DATABASE SCHEMA (MySQL)

```sql
CREATE DATABASE IF NOT EXISTS happy_bangladesh;
USE happy_bangladesh;

-- USERS (all roles share this table)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  role ENUM('admin','manager','sr','dsr','dealer') NOT NULL,
  status TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- WAREHOUSES
CREATE TABLE warehouses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  address TEXT,
  area VARCHAR(100),
  status TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- DEALERS (linked to user)
CREATE TABLE dealers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100),
  phone VARCHAR(20),
  address TEXT,
  status TINYINT DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- COMPANIES (linked to dealer)
CREATE TABLE companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dealer_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  contact VARCHAR(50),
  address TEXT,
  status TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (dealer_id) REFERENCES dealers(id)
);

-- MANAGERS (linked to user + warehouse)
CREATE TABLE managers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  status TINYINT DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- ROUTES
CREATE TABLE routes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  area VARCHAR(100),
  warehouse_id INT,
  status TINYINT DEFAULT 1,
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- SR (Sales Representatives — assigned to a company)
CREATE TABLE sr (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  company_id INT NOT NULL,
  route_id INT,
  status TINYINT DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (route_id) REFERENCES routes(id)
);

-- DSR (Delivery Sales Representatives)
CREATE TABLE dsr (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  status TINYINT DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- CATEGORIES
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  name VARCHAR(100),
  status TINYINT DEFAULT 1,
  FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- PRODUCTS
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  category_id INT,
  name VARCHAR(150) NOT NULL,
  image VARCHAR(255),
  pieces_per_box INT DEFAULT 1,
  selling_price DECIMAL(10,2),
  status TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- LOTS (product batches received from company)
CREATE TABLE lots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  manager_id INT NOT NULL,
  lot_date DATE NOT NULL,
  grand_total DECIMAL(12,2),
  status TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- LOT ITEMS (products inside a lot)
CREATE TABLE lot_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lot_id INT NOT NULL,
  product_id INT NOT NULL,
  qty_boxes INT NOT NULL,
  buying_price DECIMAL(10,2),
  total DECIMAL(12,2),
  qr_generated TINYINT DEFAULT 0,   ← tracks if QR has been generated yet
  FOREIGN KEY (lot_id) REFERENCES lots(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- QR CODES (each box gets one QR, generated manually on qr_generate.php)
CREATE TABLE qr_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lot_item_id INT NOT NULL,
  product_id INT NOT NULL,
  lot_id INT NOT NULL,
  qr_uid VARCHAR(50) UNIQUE NOT NULL,   ← e.g. "HB-2024-GT-00001"
  serial_number INT NOT NULL,           ← strict sequential per product
  pieces_total INT NOT NULL,
  pieces_remaining INT NOT NULL,
  status ENUM('active','dispatched','returned','depleted') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lot_item_id) REFERENCES lot_items(id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (lot_id) REFERENCES lots(id)
);

-- ORDERS (placed manually by manager, assigned to an SR)
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sr_id INT NOT NULL,                  ← which SR this order is for
  company_id INT NOT NULL,
  order_date DATE NOT NULL,
  status ENUM('pending','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sr_id) REFERENCES sr(id),
  FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- ORDER ITEMS
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  qty_pieces INT NOT NULL,
  qty_boxes_display INT NOT NULL,      ← pre-calculated for display
  qty_pieces_remainder INT NOT NULL,   ← leftover pieces beyond full boxes
  unit_price DECIMAL(10,2),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- DISPATCHES (created when delivery scan is complete — no separate dispatch step)
CREATE TABLE dispatches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dsr_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  manager_id INT NOT NULL,
  dispatch_date DATE NOT NULL,
  status ENUM('loading','loaded','delivered','settled') DEFAULT 'loading',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (dsr_id) REFERENCES dsr(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- DISPATCH ITEMS
CREATE TABLE dispatch_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dispatch_id INT NOT NULL,
  order_id INT,
  qr_code_id INT NOT NULL,
  product_id INT NOT NULL,
  qty_out INT NOT NULL,
  FOREIGN KEY (dispatch_id) REFERENCES dispatches(id),
  FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- RETURNS
CREATE TABLE returns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dispatch_id INT NOT NULL,
  manager_id INT NOT NULL,
  return_date DATE NOT NULL,
  status ENUM('pending','completed') DEFAULT 'pending',
  FOREIGN KEY (dispatch_id) REFERENCES dispatches(id)
);

-- RETURN ITEMS
CREATE TABLE return_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  return_id INT NOT NULL,
  qr_code_id INT NOT NULL,
  product_id INT NOT NULL,
  qty_in INT NOT NULL,
  type ENUM('scan','custom') DEFAULT 'scan',
  FOREIGN KEY (return_id) REFERENCES returns(id),
  FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id)
);

-- INVENTORY
CREATE TABLE inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  qty_boxes INT DEFAULT 0,
  qty_pieces INT DEFAULT 0,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_stock (product_id, warehouse_id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- EXPENSES
CREATE TABLE expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dsr_id INT NOT NULL,
  dispatch_id INT,
  amount DECIMAL(10,2),
  description TEXT,
  expense_date DATE,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  FOREIGN KEY (dsr_id) REFERENCES dsr(id)
);

-- CASH SETTLEMENTS
CREATE TABLE cash_settlements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dsr_id INT NOT NULL,
  dispatch_id INT NOT NULL,
  amount_expected DECIMAL(12,2),
  amount_submitted DECIMAL(12,2),
  difference DECIMAL(12,2),
  settlement_date DATE,
  manager_id INT,
  notes TEXT,
  FOREIGN KEY (dsr_id) REFERENCES dsr(id),
  FOREIGN KEY (dispatch_id) REFERENCES dispatches(id)
);

-- DEFAULT ADMIN
INSERT INTO users (name, email, password, role)
VALUES ('Admin', 'admin@happy.com', '$2y$10$...bcrypt_hash...', 'admin');
```

---

## 3. BUILD ORDER (Phase by Phase)

---

### ✅ PHASE 1 — Foundation

- `config/db.php` — PDO connection
- `config/session.php` — session start, role check
- `index.php` — Login form
- `api/auth.php` — POST handler
- `logout.php`
- `includes/header.php`, `sidebar.php`, `footer.php`

**Session on login:**
```php
$_SESSION['user_id']
$_SESSION['role']        // admin / manager / dsr / dealer
$_SESSION['name']
$_SESSION['warehouse_id']
$_SESSION['company_id']
```

---

### ✅ PHASE 2 — Admin Panel (Master Data)

Build each page: HTML table list + Add/Edit modal (AJAX) + Delete confirm.

**Order:**
1. `admin/warehouses.php`
2. `admin/dealers.php`
3. `admin/companies.php`
4. `admin/managers.php`
5. `admin/sr.php` — Create user with role=sr, assign company + route
6. `admin/dsr.php`
7. `admin/routes.php`
8. `admin/index.php` — Dashboard

**API endpoints:**
- `api/warehouses.php`, `api/dealers.php`, `api/companies.php`
- `api/managers.php`, `api/sr.php`, `api/dsr.php`, `api/routes.php`

---

### ✅ PHASE 3 — Manager Panel: Products & Categories

1. `manager/categories.php` + `api/categories.php`
2. `manager/products.php` + `api/products.php`
   - List with image, name, company, category, pieces/box, price
   - Add/Edit/Delete

**No retailers page.**

---

### ✅ PHASE 4 — Manager Panel: Lots

#### `manager/lot_add.php`
- Select company (dropdown)
- Select date
- Dynamic item rows: [Select Product] [Qty Boxes] [Buying Price] = [Row Total]
- Add Row button → append new row
- Grand Total auto-calculates
- Submit → POST to `api/lots.php`

**`api/lots.php` on create:**
```
1. Insert into lots
2. For each item: Insert into lot_items (qr_generated = 0)
3. Update inventory (add qty_boxes and qty_pieces)
⚠️ DO NOT insert into qr_codes here — QR generation is separate
```

#### `manager/lots.php`
- Table: Date | Company | Items | Grand Total | [Generate QR] [Print QR] [View] [Edit] [Delete]
- **[Generate QR] button** → redirect to `manager/qr_generate.php?lot_id=X`
- **[Print QR] button** → redirect to `manager/qr_print.php?lot_id=X`

#### `manager/lot_view.php`
- Invoice layout: Company, Date, Items table, Grand Total
- Print button

---

### ✅ PHASE 5 — QR Generation Page (Separate)

#### `manager/qr_generate.php`

**Layout:**
```
┌──────────────────────────────────────────────────┐
│  Generate QR Codes                               │
│                                                  │
│  Select Lot:     [Dropdown ▼]                    │
│  Select Product: [Dropdown ▼]  ← filtered by lot │
│                                                  │
│  Qty in Lot:     [ 24 boxes ]  ← auto-filled     │
│  (read-only, from lot_items.qty_boxes)           │
│                                                  │
│  [Generate QR Codes]                             │
└──────────────────────────────────────────────────┘
│  QR Grid (appears after generate)               │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐        │
│  │  [QR img]│ │  [QR img]│ │  [QR img]│        │
│  │ HB-24-001│ │ HB-24-002│ │ HB-24-003│        │
│  └──────────┘ └──────────┘ └──────────┘        │
└──────────────────────────────────────────────────┘
```

**Step-by-step UI behaviour:**
1. User selects a Lot from dropdown
2. AJAX call → `api/qr.php?action=lot_products&lot_id=X`
   - Returns products in that lot (from lot_items JOIN products)
   - Product dropdown populates
3. User selects a product
4. AJAX auto-fills "Qty in Lot" (read-only) from `lot_items.qty_boxes`
5. User clicks **[Generate QR Codes]**
6. AJAX → POST `api/qr.php?action=generate`

**`api/qr.php` generate logic:**
```
1. Check if qr_codes already exist for this lot_item_id
   - If yes: return existing ones (don't duplicate)
2. If no: generate N rows (N = qty_boxes)
   - Determine global serial counter for this product:
     SELECT MAX(serial_number) FROM qr_codes WHERE product_id = X
   - Increment from there ensuring perfect serial sequence
   - qr_uid format: HB-{YEAR}-{PRODUCT_SHORT}-{5DIGIT_SERIAL}
     Example: HB-2025-GT-00001, HB-2025-GT-00002 ...
   - Insert into qr_codes with serial_number stored
3. Set lot_items.qr_generated = 1
4. Return all QR codes for display
```

**QR Grid display (JS, using qrcode.js):**
```javascript
qrCodes.forEach(qr => {
  const canvas = document.createElement('canvas');
  QRCode.toCanvas(canvas, qr.qr_uid, { width: 120 });
  // wrap in card with qr_uid text below
});
```

**Serial guarantee:** All serials for a product are always sequential globally (across all lots), never duplicated.

---

### ✅ PHASE 6 — QR Print Page (Separate, Sticker Printer Optimized)

#### `manager/qr_print.php`

**Layout (screen view):**
```
┌──────────────────────────────────────────────────┐
│  Print QR Stickers                               │
│                                                  │
│  Select Lot:     [Dropdown ▼]                    │
│  Select Product: [Dropdown ▼]                    │
│                  [Apply]                         │
└──────────────────────────────────────────────────┘
│  QR Sticker Preview (after Apply)               │
│  ┌────────────┐  ┌────────────┐                 │
│  │ [QR CODE]  │  │ [QR CODE]  │                 │
│  │ HB-25-001  │  │ HB-25-002  │                 │
│  │ Product    │  │ Product    │                 │
│  │ Name Here  │  │ Name Here  │                 │
│  │ 24 pcs/box │  │ 24 pcs/box │                 │
│  └────────────┘  └────────────┘                 │
│                                                  │
│  [🖨 Print Stickers]                             │
└──────────────────────────────────────────────────┘
```

**User flow:**
1. Select Lot → products populate (only lots with generated QRs)
2. Select Product → click **[Apply]**
3. AJAX → `api/qr.php?action=fetch&lot_item_id=X` → returns only that product's QR codes
4. Display QR sticker cards on screen
5. Click **[Print Stickers]** → triggers print dialog

**Each sticker card contains:**
- QR code image (generated client-side via qrcode.js)
- QR unique number (e.g. `HB-2025-GT-00001`)
- Product name
- Qty per box (pieces_per_box value)

**Print CSS — optimized for thermal sticker printer (75mm × 100mm label):**
```css
@media print {
  body * { visibility: hidden; }
  #print-area, #print-area * { visibility: visible; }
  #print-area {
    position: absolute;
    left: 0; top: 0;
  }
  .sticker-card {
    width: 75mm;
    height: 100mm;
    page-break-after: always;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4mm;
    font-family: Arial, sans-serif;
    border: none;
  }
  .sticker-card canvas {
    width: 55mm !important;
    height: 55mm !important;
  }
  .sticker-qr-uid {
    font-size: 9pt;
    font-weight: bold;
    margin-top: 3mm;
    letter-spacing: 0.5px;
  }
  .sticker-product-name {
    font-size: 10pt;
    font-weight: bold;
    text-align: center;
    margin-top: 2mm;
  }
  .sticker-qty {
    font-size: 9pt;
    color: #333;
    margin-top: 1mm;
  }
  @page {
    size: 75mm 100mm;
    margin: 0;
  }
}
```

**One sticker = one box. After printing, stick one label on each physical box.**

---

### ✅ PHASE 7 — Orders Page

#### `manager/orders.php`

**Top section — Stat Cards:**
```
┌─────────────────┐ ┌──────────────────┐ ┌───────────────────┐ ┌──────────────────┐
│ Grand Total      │ │ Total Out (pcs)  │ │ Total Back (pcs)  │ │ Avg Delivery %   │
│ Sales Value      │ │ All orders       │ │ All returns       │ │ Across all orders│
│ ৳ 1,24,500      │ │ 4,800 pcs        │ │ 320 pcs           │ │ 93.4%            │
└─────────────────┘ └──────────────────┘ └───────────────────┘ └──────────────────┘
```

**Below cards:**
```
[+ Add New Order]   ← prominent button → redirects to manager/order_add.php
```

**Orders table columns:**
| # | Product Name | SR Name | Date | Total Qty (pcs) | Back Qty (pcs) | Sell Qty (pcs) | Sale Value | Out Value | Back Value | Delivery Ratio | Edit | Delete |

- **Sell Qty:** Total Qty − Back Qty
- **Sale Value:** Sell Qty × selling_price
- **Out Value:** Total Qty × selling_price
- **Back Value:** Back Qty × selling_price
- **Delivery Ratio:** `(Sell Qty / Total Qty) × 100`
  - ≥90%: green badge
  - 70–89%: yellow badge
  - <70%: red badge

Table rows are **per product per order** (product-level breakdown).

**Filters above table:** Date range | Company | SR | Product | Status

---

### ✅ PHASE 8 — Add Order Page (Manual Bulk Entry)

#### `manager/order_add.php`

**Layout:**
```
┌──────────────────────────────────────────────────┐
│  Add New Order                                   │
│                                                  │
│  Select SR:   [Dropdown ▼]                       │
│  Date:        [Date picker]                      │
│                                                  │
│  ── Products ───────────────────────────────────│
│  [+ Add Product Row]                             │
│                                                  │
│  ┌──────────────────────────┬──────────────────┐ │
│  │ Select Product [▼]       │ Ordered Qty      │ │
│  ├──────────────────────────┼──────────────────┤ │
│  │ GT Soap                  │ [  72  ] pcs    [✕]│
│  │ HL Cream                 │ [  50  ] pcs    [✕]│
│  │ [Select Product ▼]       │ [      ] pcs    [✕]│
│  └──────────────────────────┴──────────────────┘ │
│                                                  │
│  [Save Order]                                    │
└──────────────────────────────────────────────────┘
```

**Behaviour:**
1. Manager selects SR from dropdown
2. AJAX → `api/orders.php?action=sr_products&sr_id=X`
   - Fetches all products belonging to that SR's company
3. Product dropdowns populate with only those products
4. Manager clicks **[+ Add Product Row]** to add more rows
5. Each row: product select + qty input (in pieces) + remove [✕] button
6. Same product cannot be selected twice (disable already-chosen options)
7. Click **[Save Order]**:

**`api/orders.php` POST (create):**
```
1. Insert into orders (sr_id, company_id, order_date, status='pending')
2. For each product row:
   - Insert into order_items (order_id, product_id, qty_pieces, unit_price)
   - qty_boxes_display = floor(qty_pieces / pieces_per_box)
   - qty_pieces_remainder = qty_pieces % pieces_per_box
3. Redirect to orders.php with success toast
```

No QR scanning involved in order creation at all.

---

### ✅ PHASE 9 — Out for Delivery Page (Scan + Dispatch Combined)

#### `manager/delivery.php`

This page replaces the old separate dispatch scan + van loading pages. Everything happens here in one flow.

**Layout:**
```
┌──────────────────────────────────────────────────┐
│  Out for Delivery                                │
│                                                  │
│  Select Order:  [Dropdown ▼]  (pending orders)  │
│  Select DSR:    [Dropdown ▼]                     │
│                                                  │
│  ┌──────────────────────────────┐                │
│  │  📷  QR Scanner              │                │
│  │  (camera or USB scanner)     │                │
│  │  [Start Camera]              │                │
│  └──────────────────────────────┘                │
│                                                  │
│  Products to Load:                               │
│  ┌───────────────┬──────────┬───────────────────┐│
│  │ Product Name  │ Required │ Progress           ││
│  ├───────────────┼──────────┼───────────────────┤│
│  │ GT Soap       │ 3 boxes  │ ████░░░ 2/3        ││
│  │ HL Cream      │ 2 boxes  │ ██████░ 2/2 ✓      ││
│  └───────────────┴──────────┴───────────────────┘│
│                                                  │
│  [✅ Complete — Send to Van]  ← enabled when all done │
└──────────────────────────────────────────────────┘
```

**Step-by-step behaviour:**
1. Manager selects a **pending order** from dropdown
2. Manager selects **DSR** (driver)
3. Table populates with products from that order:
   - Required qty = `ceil(qty_pieces / pieces_per_box)` → number of boxes needed
   - Progress bar starts at 0
4. Manager starts scanning QR boxes (camera or USB):
   ```
   On each scan → POST api/delivery.php?action=scan_box
   - Validate: qr_uid exists in qr_codes
   - Validate: status = 'active'
   - Validate: product belongs to this order
   - Validate: not already scanned in this session
   - Return: product_id, product_name, pieces_total
   ```
5. Frontend on successful scan:
   - Find product row in table
   - Increment scanned count
   - Animate progress bar fill: `width: (scanned/required * 100)%` with 0.4s CSS transition
   - If scanned = required → row turns green + checkmark ✓
   - If wrong product scanned → red toast "This product is not in this order"
   - If already scanned → orange toast "Already scanned"
6. When ALL products reach required qty → **[Complete]** button enables

**[Complete] button → POST `api/delivery.php?action=complete`:**
```
1. Create dispatch record: INSERT INTO dispatches (dsr_id, order_id, warehouse_id, dispatch_date, status='loaded')
2. For each scanned QR box: INSERT INTO dispatch_items (dispatch_id, qr_code_id, product_id, qty_out)
3. UPDATE qr_codes.status = 'dispatched' for all scanned boxes
4. UPDATE orders.status = 'out_for_delivery'
5. Return success → toast "Order sent to van!" → redirect to orders.php
```

**Progress bar CSS:**
```css
.progress-bar {
  height: 12px;
  background: #E5E7EB;
  border-radius: 999px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  background: #4F46E5;
  border-radius: 999px;
  transition: width 0.4s ease;
}
.progress-fill.complete {
  background: #10B981;
}
```

---

### ✅ PHASE 10 — Return Panel

#### `manager/returns.php`
- Select dispatch
- Table: [Product | Out Qty | In Qty | Custom button]

**Two flows:**

**A) QR Scan Return:**
- Scanner auto-fills In Qty

**B) Custom Return:**
- Modal with QR cards: [QR UID | Pieces Total | Pieces Remaining | input]
- Manager enters partial returns per box

**On Complete:**
```
- Insert return_items
- Update qr_codes.pieces_remaining
- If pieces_remaining = 0 → status = 'returned'
- Add back to inventory.qty_pieces
- dispatch.status = 'settled'
```

---

### ✅ PHASE 11 — DSR Panel: Expenses

#### `dsr/expenses.php`
- List + Add expense: Amount | Description | Date | Dispatch
- Manager approves/rejects from `manager/expenses.php`

---

### ✅ PHASE 12 — Cash Settlement

#### `manager/cashflow.php`
- Select dispatch
- Expected amount | Submitted amount | Difference auto
- Save → insert into cash_settlements

---

### ✅ PHASE 13 — Inventory Page

#### `manager/inventory.php`
- Table: Product | Company | Category | Boxes in Stock | Pieces in Stock | Last Updated
- Filter by company, category
- Low stock row highlighting (qty_boxes < threshold)

---

### ✅ PHASE 14 — Admin Dashboard

#### `admin/index.php`
- Stat cards: Total Orders Today | Revenue | Active DSRs | Stock Value | Pending Returns
- Charts (Chart.js): Orders/day line, Top 5 products bar, Revenue by company pie
- Tables: Recent orders, Active dispatches, Low stock alerts, Pending expenses

---

## 4. KEY JAVASCRIPT MODULES

### `assets/js/qr.js` — QR Scanner (camera + USB)
```javascript
// Camera scanner using html5-qrcode
function startCameraScanner(elementId, onScanCallback) {
  const scanner = new Html5Qrcode(elementId);
  scanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    (qrCodeMessage) => { onScanCallback(qrCodeMessage); },
    (error) => { /* ignore */ }
  );
  return scanner;
}

// USB / keyboard-wedge scanner listener
// USB scanners emit keystrokes ending in Enter
let usbBuffer = '';
document.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    if (usbBuffer.length > 5) {
      onScanCallback(usbBuffer);
    }
    usbBuffer = '';
  } else {
    usbBuffer += e.key;
  }
});
```

---

## 5. REUSABLE UI COMPONENTS

### Toast Notifications
```javascript
function showToast(msg, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `fixed top-4 right-4 px-4 py-2 rounded shadow-lg text-white z-50
    ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
  toast.textContent = msg;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}
```

### AJAX Helper
```javascript
async function api(url, method = 'GET', body = null) {
  const opts = { method, headers: { 'Content-Type': 'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(url, opts);
  return res.json();
}
```

### Boxes + Pieces Display Helper
```javascript
function formatQty(totalPieces, piecesPerBox) {
  const boxes = Math.floor(totalPieces / piecesPerBox);
  const pieces = totalPieces % piecesPerBox;
  if (pieces === 0) return `${boxes} box`;
  return `${boxes} box ${pieces} piece`;
}
// Example: formatQty(50, 24) → "2 box 2 piece"
```

---

## 6. TAILWIND UI DESIGN SYSTEM

```
Page wrapper:    class="min-h-screen bg-gray-50"
Card:            class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
Primary button:  class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition"
Danger button:   class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition"
Table:           class="w-full text-sm text-left border-collapse"
Table header:    class="bg-gray-50 text-gray-500 uppercase text-xs font-semibold tracking-wide"
Table row:       class="border-b border-gray-100 hover:bg-gray-50"
Input field:     class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
Badge success:   class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full"
Badge warning:   class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full"
Badge danger:    class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full"
Modal overlay:   class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
Modal box:       class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg mx-4"
```

**Color palette:**
- Primary: Indigo `#4F46E5`
- Success: Green `#10B981`
- Warning: Amber `#F59E0B`
- Danger: Red `#EF4444`
- Background: Gray-50 `#F9FAFB`

---

## 7. CDN LIBRARIES (add to header.php)

```html
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- html5-qrcode (camera scanning) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<!-- QR Code Generator (client-side QR image generation) -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
```

> **Note:** Leaflet map removed — no SR map needed.

---

## 8. SECURITY RULES

1. All AJAX API files: check session + role before processing
2. All inputs: `htmlspecialchars()` on output, PDO prepared statements
3. File uploads: validate extension (jpg/png/webp), rename to hash
4. Passwords: `password_hash()` + `password_verify()`
5. No direct SQL string concatenation

---

## 9. DEPLOYMENT (cPanel)

1. Export XAMPP DB → import to cPanel MySQL
2. Update `config/db.php` with cPanel credentials
3. Upload via File Manager or FTP
4. Set `uploads/` permissions → 755
5. Optional: `.htaccess` for clean URLs

---

## 10. BUILD CHECKLIST

```
Phase 1:  [ ] Login  [ ] Session  [ ] Sidebar  [ ] Logout
Phase 2:  [ ] Warehouse  [ ] Dealer  [ ] Company  [ ] Manager  [ ] SR  [ ] DSR  [ ] Route
Phase 3:  [ ] Category  [ ] Product
Phase 4:  [ ] Lot Add (no auto QR)  [ ] Lot List  [ ] Lot Invoice
Phase 5:  [ ] QR Generate page  [ ] Lot select  [ ] Product select  [ ] Auto qty fill  [ ] Serial QR generation
Phase 6:  [ ] QR Print page  [ ] Lot/Product select  [ ] Sticker layout  [ ] 75×100mm print CSS
Phase 7:  [ ] Orders list  [ ] Stat cards  [ ] Product-level table  [ ] Delivery ratio badges
Phase 8:  [ ] Add Order page  [ ] SR select  [ ] SR company products load  [ ] Multi-row product+qty entry  [ ] Save order
Phase 9:  [ ] Delivery page  [ ] Order+DSR select  [ ] QR scanner (camera+USB)  [ ] Product progress bars  [ ] Animate on scan  [ ] Complete → dispatch created
Phase 10: [ ] Return scan  [ ] Custom return modal  [ ] Return complete
Phase 11: [ ] DSR Expenses  [ ] Manager approval
Phase 12: [ ] Cash settlement
Phase 13: [ ] Inventory view  [ ] Low stock alerts
Phase 14: [ ] Admin dashboard charts
```
