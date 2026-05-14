-- Happy Bangladesh ERP — Database Schema v3
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS happy_bangladesh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE happy_bangladesh;

-- USERS (all roles share this table)
CREATE TABLE IF NOT EXISTS users (
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
CREATE TABLE IF NOT EXISTS warehouses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL,
  address TEXT,
  area VARCHAR(100),
  status TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- DEALERS (linked to user)
CREATE TABLE IF NOT EXISTS dealers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100),
  phone VARCHAR(20),
  address TEXT,
  status TINYINT DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- COMPANIES (linked to dealer)
CREATE TABLE IF NOT EXISTS companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dealer_id INT NOT NULL,
  name VARCHAR(100) UNIQUE NOT NULL,
  contact VARCHAR(50),
  address TEXT,
  status TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (dealer_id) REFERENCES dealers(id)
);

-- MANAGERS (linked to user + warehouse)
CREATE TABLE IF NOT EXISTS managers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  status TINYINT DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- ROUTES
CREATE TABLE IF NOT EXISTS routes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE,
  area VARCHAR(100),
  warehouse_id INT,
  status TINYINT DEFAULT 1,
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- SR (Sales Representatives)
CREATE TABLE IF NOT EXISTS sr (
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
CREATE TABLE IF NOT EXISTS dsr (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  status TINYINT DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- CATEGORIES
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT,
  name VARCHAR(100),
  status TINYINT DEFAULT 1,
  UNIQUE KEY unique_category (company_id, name),
  FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- PRODUCTS
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  category_id INT,
  name VARCHAR(150) NOT NULL,
  box_type VARCHAR(50) NULL,
  image VARCHAR(255),
  pieces_per_box INT DEFAULT 1,
  selling_price DECIMAL(10,2),
  dealer_percentage DECIMAL(5,2) DEFAULT 0.00,
  status TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_product (company_id, name),
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- LOTS (product batches received from company)
CREATE TABLE IF NOT EXISTS lots (
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

-- LOT ITEMS
CREATE TABLE IF NOT EXISTS lot_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lot_id INT NOT NULL,
  product_id INT NOT NULL,
  qty_boxes INT NOT NULL,
  expiry_date DATE NULL,
  buying_price DECIMAL(10,2),
  total DECIMAL(12,2),
  qr_generated TINYINT DEFAULT 0,
  FOREIGN KEY (lot_id) REFERENCES lots(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- QR CODES
CREATE TABLE IF NOT EXISTS qr_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lot_item_id INT NOT NULL,
  product_id INT NOT NULL,
  lot_id INT NOT NULL,
  qr_uid VARCHAR(50) UNIQUE NOT NULL,
  serial_number INT NOT NULL,
  pieces_total INT NOT NULL,
  pieces_remaining INT NOT NULL,
  status ENUM('active','dispatched','returned','depleted') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lot_item_id) REFERENCES lot_items(id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (lot_id) REFERENCES lots(id)
);

-- ORDERS
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sr_id INT NOT NULL,
  company_id INT NOT NULL,
  order_date DATE NOT NULL,
  status ENUM('pending','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sr_id) REFERENCES sr(id),
  FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- ORDER ITEMS
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  qty_pieces INT NOT NULL,
  qty_boxes_display INT NOT NULL,
  qty_pieces_remainder INT NOT NULL,
  unit_price DECIMAL(10,2),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- DISPATCHES
CREATE TABLE IF NOT EXISTS dispatches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dsr_id INT NOT NULL,
  order_id INT,
  warehouse_id INT NOT NULL,
  manager_id INT NOT NULL,
  dispatch_date DATE NOT NULL,
  status ENUM('loading','loaded','delivered','settled') DEFAULT 'loading',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (dsr_id) REFERENCES dsr(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

-- DISPATCH ITEMS
CREATE TABLE IF NOT EXISTS dispatch_items (
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
CREATE TABLE IF NOT EXISTS returns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dispatch_id INT NOT NULL,
  manager_id INT NOT NULL,
  return_date DATE NOT NULL,
  status ENUM('pending','completed') DEFAULT 'pending',
  FOREIGN KEY (dispatch_id) REFERENCES dispatches(id)
);

-- RETURN ITEMS
CREATE TABLE IF NOT EXISTS return_items (
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
CREATE TABLE IF NOT EXISTS inventory (
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
CREATE TABLE IF NOT EXISTS expenses (
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
CREATE TABLE IF NOT EXISTS cash_settlements (
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

-- DEFAULT ADMIN USER (password: admin123)
INSERT IGNORE INTO users (name, email, password, role, status)
VALUES ('Admin', 'admin@happy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);
-- Note: The above bcrypt hash corresponds to "password" — change after first login!
-- To generate a correct hash for "admin123", run: password_hash('admin123', PASSWORD_DEFAULT)

-- Sample data (optional — remove in production)
INSERT IGNORE INTO warehouses (name, address, area, status) VALUES 
('Main Warehouse', 'Dhaka, Bangladesh', 'Dhaka', 1),
('Chittagong Hub', 'Chittagong, Bangladesh', 'Chittagong', 1);
