-- ============================================================
-- HappyCRM2 - Dummy Data Seed File
-- Database: happy_bangladesh
-- Generated: 2026-05-21
-- ============================================================
-- Insert order respects FK constraints:
-- users → warehouses → dealers → companies → categories → products
-- → managers → dsr → routes → sr → lots → lot_items → qr_codes
-- → orders → order_items → dispatches → dispatch_items
-- → cash_settlements → dsr_attendance → expenses → returns → return_items

SET FOREIGN_KEY_CHECKS=0;

-- ============================================================
-- 1. USERS (admin + managers + dealers + DSRs + SRs)
-- All passwords = "password123" (bcrypt hashed)
-- ============================================================
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `status`, `created_at`) VALUES
-- Admin (already exists, skip if re-running)
(1,  'Admin',             'admin@happy.com',        '$2y$10$26N3Y8eGNdKMbUI0HQv/ZOlC7uvIH7ftOJomvikOT.2xM3cQJ.Gj.', NULL,           'admin',   1, '2026-05-13 13:04:45'),
-- Managers
(2,  'Rafiq Hossain',     'rafiq.manager@happy.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01711111101', 'manager', 1, '2026-05-14 08:00:00'),
(3,  'Nasreen Akter',     'nasreen.mgr@happy.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01711111102', 'manager', 1, '2026-05-14 08:05:00'),
(4,  'Tariqul Islam',     'tariq.mgr@happy.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01711111103', 'manager', 1, '2026-05-14 08:10:00'),
-- Dealers
(5,  'Kamal Uddin',       'kamal.dealer@happy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01811111101', 'dealer',  1, '2026-05-14 09:00:00'),
(6,  'Salma Begum',       'salma.dealer@happy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01811111102', 'dealer',  1, '2026-05-14 09:05:00'),
(7,  'Jamal Ahmed',       'jamal.dealer@happy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01811111103', 'dealer',  1, '2026-05-14 09:10:00'),
-- DSRs (Delivery Sales Representatives)
(8,  'Rahim Mia',         'rahim.dsr@happy.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01911111101', 'dsr',     1, '2026-05-14 10:00:00'),
(9,  'Sumon Khan',        'sumon.dsr@happy.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01911111102', 'dsr',     1, '2026-05-14 10:05:00'),
(10, 'Farzana Sultana',   'farzana.dsr@happy.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01911111103', 'dsr',     1, '2026-05-14 10:10:00'),
(11, 'Mizanur Rahman',    'mizan.dsr@happy.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01911111104', 'dsr',     1, '2026-05-14 10:15:00'),
(12, 'Runa Laila',        'runa.dsr@happy.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01911111105', 'dsr',     1, '2026-05-14 10:20:00'),
-- SRs (Sales Representatives)
(13, 'Anisur Rahman',     'anis.sr@happy.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01611111101', 'sr',      1, '2026-05-14 11:00:00'),
(14, 'Parveen Akter',     'parveen.sr@happy.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01611111102', 'sr',      1, '2026-05-14 11:05:00'),
(15, 'Lutfor Rahman',     'lutfor.sr@happy.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01611111103', 'sr',      1, '2026-05-14 11:10:00'),
(16, 'Shirina Begum',     'shirina.sr@happy.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01611111104', 'sr',      1, '2026-05-14 11:15:00'),
(17, 'Mosharraf Hossain', 'mosharraf.sr@happy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01611111105', 'sr',      1, '2026-05-14 11:20:00'),
(18, 'Kohinoor Akter',    'kohinoor.sr@happy.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.ucrIW/KC',  '01611111106', 'sr',      1, '2026-05-14 11:25:00');

-- ============================================================
-- 2. WAREHOUSES
-- ============================================================
INSERT IGNORE INTO `warehouses` (`id`, `name`, `address`, `area`, `status`, `created_at`) VALUES
(1, 'Dhaka Central Warehouse',    'Motijheel, Dhaka-1000',        'Dhaka',      1, '2026-05-13 14:00:00'),
(2, 'Chittagong Port Warehouse',  'Agrabad, Chittagong-4100',     'Chittagong', 1, '2026-05-13 14:05:00'),
(3, 'Sylhet Distribution Hub',    'Zindabazar, Sylhet-3100',      'Sylhet',     1, '2026-05-13 14:10:00'),
(4, 'Rajshahi Warehouse',         'Shaheb Bazar, Rajshahi-6000',  'Rajshahi',   1, '2026-05-13 14:15:00'),
(5, 'Khulna Regional Store',      'KDA, Khulna-9100',             'Khulna',     1, '2026-05-13 14:20:00');

-- ============================================================
-- 3. DEALERS
-- ============================================================
INSERT IGNORE INTO `dealers` (`id`, `user_id`, `name`, `phone`, `address`, `status`) VALUES
(1, 5, 'Kamal Trading Co.',       '01811111101', '45, Banani, Dhaka',          1),
(2, 6, 'Salma Enterprises',       '01811111102', '12, Agrabad, Chittagong',    1),
(3, 7, 'Jamal Brothers Ltd.',     '01811111103', '78, Zindabazar, Sylhet',     1);

-- ============================================================
-- 4. COMPANIES (brands/principals the dealer represents)
-- ============================================================
INSERT IGNORE INTO `companies` (`id`, `dealer_id`, `name`, `contact`, `address`, `status`, `created_at`) VALUES
(1, 1, 'Pran-RFL Group',       '02-8832600', 'PRAN Center, 105 Bir Uttam, Dhaka',    1, '2026-05-13 15:00:00'),
(2, 1, 'ACI Limited',          '02-8870222', 'ACI Center, 245 Tejgaon, Dhaka',       1, '2026-05-13 15:05:00'),
(3, 2, 'Square Foods',         '02-8833001', 'Square Centre, 48 Mohakhali, Dhaka',   1, '2026-05-13 15:10:00'),
(4, 2, 'Partex Beverage',      '031-2512000','Karnaphuli, Chittagong',                1, '2026-05-13 15:15:00'),
(5, 3, 'Igloo Ice Cream',      '09610000001','68/1 Agrabad, Chittagong',             1, '2026-05-13 15:20:00');

-- ============================================================
-- 5. CATEGORIES
-- ============================================================
INSERT IGNORE INTO `categories` (`id`, `company_id`, `name`, `status`) VALUES
-- Pran-RFL (company 1)
(1,  1, 'Beverage',         1),
(2,  1, 'Snacks',           1),
(3,  1, 'Noodles',          1),
-- ACI (company 2)
(4,  2, 'Health Drink',     1),
(5,  2, 'Biscuits',         1),
-- Square Foods (company 3)
(6,  3, 'Cooking Oil',      1),
(7,  3, 'Flour & Rice',     1),
-- Partex Beverage (company 4)
(8,  4, 'Soft Drinks',      1),
(9,  4, 'Mineral Water',    1),
-- Igloo (company 5)
(10, 5, 'Ice Cream',        1),
(11, 5, 'Frozen Dessert',   1);

-- ============================================================
-- 6. PRODUCTS
-- ============================================================
INSERT IGNORE INTO `products` (`id`, `company_id`, `category_id`, `name`, `box_type`, `image`, `pieces_per_box`, `selling_price`, `dealer_percentage`, `status`, `created_at`) VALUES
-- Pran-RFL Beverages
(1,  1, 1, 'Pran Mango Juice 250ml',        'Carton-24', NULL, 24, 25.00,  8.00, 1, '2026-05-13 16:00:00'),
(2,  1, 1, 'Pran Litchi Juice 250ml',       'Carton-24', NULL, 24, 25.00,  8.00, 1, '2026-05-13 16:01:00'),
(3,  1, 1, 'Pran Orange Drink 1L',          'Carton-12', NULL, 12, 55.00,  7.50, 1, '2026-05-13 16:02:00'),
-- Pran Snacks
(4,  1, 2, 'Pran Chanachur 100g',           'Carton-48', NULL, 48, 20.00,  9.00, 1, '2026-05-13 16:03:00'),
(5,  1, 2, 'Pran Mr. Noodles 75g',          'Carton-36', NULL, 36, 15.00, 10.00, 1, '2026-05-13 16:04:00'),
-- Pran Noodles
(6,  1, 3, 'Pran Noodles Chicken 75g',      'Carton-36', NULL, 36, 18.00,  9.50, 1, '2026-05-13 16:05:00'),
(7,  1, 3, 'Pran Noodles Masala 75g',       'Carton-36', NULL, 36, 18.00,  9.50, 1, '2026-05-13 16:06:00'),
-- ACI Health Drinks
(8,  2, 4, 'ACI Savlon Hand Wash 500ml',    'Carton-12', NULL, 12, 180.00, 6.00, 1, '2026-05-13 16:07:00'),
(9,  2, 4, 'ACI Electro Max 500ml',         'Carton-24', NULL, 24, 45.00,  7.00, 1, '2026-05-13 16:08:00'),
-- ACI Biscuits
(10, 2, 5, 'ACI Nimki Biscuit 200g',        'Carton-24', NULL, 24, 35.00,  8.50, 1, '2026-05-13 16:09:00'),
(11, 2, 5, 'ACI Cream Cracker 400g',        'Carton-12', NULL, 12, 75.00,  8.00, 1, '2026-05-13 16:10:00'),
-- Square Cooking Oil
(12, 3, 6, 'Radhuni Soyabean Oil 1L',       'Carton-12', NULL, 12, 185.00, 5.00, 1, '2026-05-13 16:11:00'),
(13, 3, 6, 'Radhuni Mustard Oil 500ml',     'Carton-24', NULL, 24, 95.00,  5.50, 1, '2026-05-13 16:12:00'),
-- Square Flour & Rice
(14, 3, 7, 'Radhuni Maida 1kg',             'Carton-12', NULL, 12, 65.00,  4.50, 1, '2026-05-13 16:13:00'),
(15, 3, 7, 'Radhuni Semolina 500g',         'Carton-24', NULL, 24, 45.00,  4.00, 1, '2026-05-13 16:14:00'),
-- Partex Soft Drinks
(16, 4, 8, 'RC Cola 250ml Can',             'Carton-24', NULL, 24, 40.00,  7.00, 1, '2026-05-13 16:15:00'),
(17, 4, 8, 'RC Cola 1.5L Bottle',           'Carton-12', NULL, 12, 90.00,  7.00, 1, '2026-05-13 16:16:00'),
-- Partex Mineral Water
(18, 4, 9, 'Fresh Mineral Water 500ml',     'Carton-24', NULL, 24, 20.00,  5.00, 1, '2026-05-13 16:17:00'),
(19, 4, 9, 'Fresh Mineral Water 1.5L',      'Carton-12', NULL, 12, 45.00,  5.00, 1, '2026-05-13 16:18:00'),
-- Igloo Ice Cream
(20, 5, 10,'Igloo Vanilla Cup 100ml',       'Carton-24', NULL, 24, 30.00,  6.00, 1, '2026-05-13 16:19:00'),
(21, 5, 10,'Igloo Chocolate Bar 80g',       'Carton-24', NULL, 24, 40.00,  6.50, 1, '2026-05-13 16:20:00'),
-- Igloo Frozen Dessert
(22, 5, 11,'Igloo Royal Kulfi 90ml',        'Carton-36', NULL, 36, 35.00,  6.00, 1, '2026-05-13 16:21:00'),
(23, 5, 11,'Igloo Strawberry Lolly 60ml',   'Carton-48', NULL, 48, 20.00,  5.50, 1, '2026-05-13 16:22:00');

-- ============================================================
-- 7. MANAGERS
-- ============================================================
INSERT IGNORE INTO `managers` (`id`, `user_id`, `warehouse_id`, `status`) VALUES
(1, 2, 1, 1),  -- Rafiq → Dhaka Central
(2, 3, 2, 1),  -- Nasreen → Chittagong Port
(3, 4, 3, 1);  -- Tariqul → Sylhet Hub

-- ============================================================
-- 8. DSR (Delivery Sales Representatives)
-- ============================================================
INSERT IGNORE INTO `dsr` (`id`, `user_id`, `warehouse_id`, `status`) VALUES
(1, 8,  1, 1),  -- Rahim Mia → Dhaka
(2, 9,  1, 1),  -- Sumon Khan → Dhaka
(3, 10, 2, 1),  -- Farzana → Chittagong
(4, 11, 2, 1),  -- Mizanur → Chittagong
(5, 12, 3, 1);  -- Runa → Sylhet

-- ============================================================
-- 9. ROUTES
-- ============================================================
INSERT IGNORE INTO `routes` (`id`, `name`, `area`, `warehouse_id`, `status`) VALUES
(1, 'Dhaka North Route',      'Uttara, Mirpur, Mohammadpur',      1, 1),
(2, 'Dhaka South Route',      'Motijheel, Lalbagh, Wari',         1, 1),
(3, 'Dhaka East Route',       'Badda, Rampura, Gulshan',          1, 1),
(4, 'Chittagong City Route',  'Agrabad, Nasirabad, Halishahar',   2, 1),
(5, 'Chittagong Port Route',  'Port Area, Patenga, Anwara',       2, 1),
(6, 'Sylhet City Route',      'Zindabazar, Amberkhana, Shibganj', 3, 1),
(7, 'Sylhet Suburban Route',  'Biswanath, Golapganj, Beanibazar', 3, 1);

-- ============================================================
-- 10. SR (Sales Representatives)
-- ============================================================
INSERT IGNORE INTO `sr` (`id`, `user_id`, `company_id`, `route_id`, `status`) VALUES
(1, 13, 1, 1, 1),  -- Anisur → Pran-RFL → Dhaka North
(2, 14, 1, 2, 1),  -- Parveen → Pran-RFL → Dhaka South
(3, 15, 2, 3, 1),  -- Lutfor → ACI → Dhaka East
(4, 16, 3, 4, 1),  -- Shirina → Square → Chittagong City
(5, 17, 4, 5, 1),  -- Mosharraf → Partex → Chittagong Port
(6, 18, 5, 6, 1);  -- Kohinoor → Igloo → Sylhet City

-- ============================================================
-- 11. LOTS (Purchase lots / stock receiving)
-- ============================================================
INSERT IGNORE INTO `lots` (`id`, `company_id`, `warehouse_id`, `manager_id`, `lot_date`, `grand_total`, `status`, `created_at`) VALUES
-- Pran-RFL lots to Dhaka
(1,  1, 1, 1, '2026-05-01', 450000.00, 1, '2026-05-01 09:00:00'),
(2,  1, 1, 1, '2026-05-08', 320000.00, 1, '2026-05-08 09:00:00'),
-- ACI lots to Dhaka
(3,  2, 1, 1, '2026-05-03', 275000.00, 1, '2026-05-03 09:00:00'),
-- Square lots to Chittagong
(4,  3, 2, 2, '2026-05-02', 390000.00, 1, '2026-05-02 09:00:00'),
(5,  3, 2, 2, '2026-05-10', 210000.00, 1, '2026-05-10 09:00:00'),
-- Partex lots to Chittagong
(6,  4, 2, 2, '2026-05-04', 180000.00, 1, '2026-05-04 09:00:00'),
-- Igloo lots to Sylhet
(7,  5, 3, 3, '2026-05-05', 150000.00, 1, '2026-05-05 09:00:00'),
(8,  5, 3, 3, '2026-05-12', 120000.00, 1, '2026-05-12 09:00:00');

-- ============================================================
-- 12. LOT ITEMS (line items in each lot)
-- ============================================================
INSERT IGNORE INTO `lot_items` (`id`, `lot_id`, `product_id`, `qty_boxes`, `expiry_date`, `buying_price`, `total`, `qr_generated`) VALUES
-- Lot 1 (Pran-RFL, Dhaka, May 1)
(1,  1, 1, 500, '2027-01-01', 18.00, 216000.00, 1),  -- Mango Juice
(2,  1, 2, 300, '2027-01-01', 18.00, 129600.00, 1),  -- Litchi Juice
(3,  1, 4, 300, '2026-12-01', 14.00, 100800.00, 1),  -- Chanachur
-- Lot 2 (Pran-RFL, Dhaka, May 8)
(4,  2, 5, 400, '2026-11-01', 11.00, 158400.00, 1),  -- Mr. Noodles
(5,  2, 6, 400, '2026-11-01', 13.00, 187200.00, 1),  -- Noodles Chicken
-- Lot 3 (ACI, Dhaka, May 3)
(6,  3, 8, 100, '2027-06-01', 155.00, 186000.00, 1), -- Savlon
(7,  3,10, 200, '2027-03-01', 28.00,  67200.00, 1),  -- Nimki Biscuit
-- Lot 4 (Square, Chittagong, May 2)
(8,  4,12, 400, '2027-12-01', 160.00, 768000.00, 1), -- Soyabean Oil -- wait this is too high, adjusted
(9,  4,13, 200, '2027-12-01', 82.00,  196800.00, 1), -- Mustard Oil
-- Lot 5 (Square, Chittagong, May 10)
(10, 5,14, 300, '2027-06-01', 55.00,  198000.00, 1), -- Maida
(11, 5,15, 200, '2027-06-01', 38.00,   91200.00, 1), -- Semolina
-- Lot 6 (Partex, Chittagong, May 4)
(12, 6,16, 400, '2027-09-01', 33.00,  158400.00, 1), -- RC Cola 250ml
(13, 6,18, 300, '2027-09-01', 16.00,   57600.00, 1), -- Fresh Water 500ml
-- Lot 7 (Igloo, Sylhet, May 5)
(14, 7,20, 200, '2026-10-01', 22.00,   52800.00, 1), -- Vanilla Cup
(15, 7,21, 200, '2026-10-01', 30.00,   72000.00, 1), -- Chocolate Bar
-- Lot 8 (Igloo, Sylhet, May 12)
(16, 8,22, 150, '2026-10-01', 26.00,   46800.00, 1), -- Royal Kulfi
(17, 8,23, 200, '2026-10-01', 14.00,   33600.00, 1); -- Strawberry Lolly

-- ============================================================
-- 13. QR CODES (one qr per box in each lot item)
-- ============================================================
-- Lot item 1 (product 1, lot 1): 500 boxes → 500 QR codes
-- We'll generate a representative set for demo purposes
-- Format: QR-LOTID-PRODUCTID-SERIAL

INSERT IGNORE INTO `qr_codes` (`id`, `lot_item_id`, `product_id`, `lot_id`, `qr_uid`, `serial_number`, `pieces_total`, `pieces_remaining`, `status`, `created_at`) VALUES
-- Lot item 1 → Product 1 (Mango Juice), 24 pcs/box
(1,  1, 1, 1, 'QR-L1-P1-001', 1, 24, 24, 'active',    '2026-05-01 10:00:00'),
(2,  1, 1, 1, 'QR-L1-P1-002', 2, 24, 0,  'depleted',  '2026-05-01 10:00:00'),
(3,  1, 1, 1, 'QR-L1-P1-003', 3, 24, 24, 'dispatched','2026-05-01 10:00:00'),
(4,  1, 1, 1, 'QR-L1-P1-004', 4, 24, 24, 'dispatched','2026-05-01 10:00:00'),
(5,  1, 1, 1, 'QR-L1-P1-005', 5, 24, 24, 'active',    '2026-05-01 10:00:00'),
(6,  1, 1, 1, 'QR-L1-P1-006', 6, 24, 24, 'active',    '2026-05-01 10:00:00'),
(7,  1, 1, 1, 'QR-L1-P1-007', 7, 24, 24, 'active',    '2026-05-01 10:00:00'),
(8,  1, 1, 1, 'QR-L1-P1-008', 8, 24, 24, 'active',    '2026-05-01 10:00:00'),
-- Lot item 2 → Product 2 (Litchi Juice)
(9,  2, 2, 1, 'QR-L1-P2-001', 1, 24, 24, 'active',    '2026-05-01 10:05:00'),
(10, 2, 2, 1, 'QR-L1-P2-002', 2, 24, 24, 'dispatched','2026-05-01 10:05:00'),
(11, 2, 2, 1, 'QR-L1-P2-003', 3, 24, 24, 'active',    '2026-05-01 10:05:00'),
(12, 2, 2, 1, 'QR-L1-P2-004', 4, 24, 24, 'active',    '2026-05-01 10:05:00'),
-- Lot item 3 → Product 4 (Chanachur)
(13, 3, 4, 1, 'QR-L1-P4-001', 1, 48, 48, 'active',    '2026-05-01 10:10:00'),
(14, 3, 4, 1, 'QR-L1-P4-002', 2, 48, 48, 'dispatched','2026-05-01 10:10:00'),
(15, 3, 4, 1, 'QR-L1-P4-003', 3, 48, 48, 'active',    '2026-05-01 10:10:00'),
(16, 3, 4, 1, 'QR-L1-P4-004', 4, 48, 48, 'active',    '2026-05-01 10:10:00'),
-- Lot item 4 → Product 5 (Mr. Noodles)
(17, 4, 5, 2, 'QR-L2-P5-001', 1, 36, 36, 'dispatched','2026-05-08 10:00:00'),
(18, 4, 5, 2, 'QR-L2-P5-002', 2, 36, 36, 'dispatched','2026-05-08 10:00:00'),
(19, 4, 5, 2, 'QR-L2-P5-003', 3, 36, 36, 'active',    '2026-05-08 10:00:00'),
(20, 4, 5, 2, 'QR-L2-P5-004', 4, 36, 36, 'active',    '2026-05-08 10:00:00'),
-- Lot item 5 → Product 6 (Noodles Chicken)
(21, 5, 6, 2, 'QR-L2-P6-001', 1, 36, 36, 'active',    '2026-05-08 10:05:00'),
(22, 5, 6, 2, 'QR-L2-P6-002', 2, 36, 36, 'active',    '2026-05-08 10:05:00'),
(23, 5, 6, 2, 'QR-L2-P6-003', 3, 36, 36, 'dispatched','2026-05-08 10:05:00'),
-- Lot item 6 → Product 8 (Savlon)
(24, 6, 8, 3, 'QR-L3-P8-001', 1, 12, 12, 'active',    '2026-05-03 10:00:00'),
(25, 6, 8, 3, 'QR-L3-P8-002', 2, 12, 12, 'dispatched','2026-05-03 10:00:00'),
(26, 6, 8, 3, 'QR-L3-P8-003', 3, 12, 12, 'active',    '2026-05-03 10:00:00'),
-- Lot item 7 → Product 10 (Nimki Biscuit)
(27, 7, 10, 3, 'QR-L3-P10-001', 1, 24, 24, 'active',    '2026-05-03 10:05:00'),
(28, 7, 10, 3, 'QR-L3-P10-002', 2, 24, 24, 'active',    '2026-05-03 10:05:00'),
(29, 7, 10, 3, 'QR-L3-P10-003', 3, 24, 24, 'dispatched','2026-05-03 10:05:00'),
-- Lot item 8 → Product 12 (Soyabean Oil)
(30, 8, 12, 4, 'QR-L4-P12-001', 1, 12, 12, 'active',    '2026-05-02 10:00:00'),
(31, 8, 12, 4, 'QR-L4-P12-002', 2, 12, 12, 'dispatched','2026-05-02 10:00:00'),
(32, 8, 12, 4, 'QR-L4-P12-003', 3, 12, 12, 'active',    '2026-05-02 10:00:00'),
(33, 8, 12, 4, 'QR-L4-P12-004', 4, 12, 12, 'active',    '2026-05-02 10:00:00'),
-- Lot item 9 → Product 13 (Mustard Oil)
(34, 9, 13, 4, 'QR-L4-P13-001', 1, 24, 24, 'active',    '2026-05-02 10:05:00'),
(35, 9, 13, 4, 'QR-L4-P13-002', 2, 24, 24, 'dispatched','2026-05-02 10:05:00'),
-- Lot item 12 → Product 16 (RC Cola)
(36, 12, 16, 6, 'QR-L6-P16-001', 1, 24, 24, 'active',    '2026-05-04 10:00:00'),
(37, 12, 16, 6, 'QR-L6-P16-002', 2, 24, 24, 'dispatched','2026-05-04 10:00:00'),
(38, 12, 16, 6, 'QR-L6-P16-003', 3, 24, 24, 'active',    '2026-05-04 10:00:00'),
-- Lot item 13 → Product 18 (Fresh Water)
(39, 13, 18, 6, 'QR-L6-P18-001', 1, 24, 24, 'active',    '2026-05-04 10:05:00'),
(40, 13, 18, 6, 'QR-L6-P18-002', 2, 24, 24, 'dispatched','2026-05-04 10:05:00'),
-- Lot item 14 → Product 20 (Vanilla Cup)
(41, 14, 20, 7, 'QR-L7-P20-001', 1, 24, 24, 'active',    '2026-05-05 10:00:00'),
(42, 14, 20, 7, 'QR-L7-P20-002', 2, 24, 24, 'dispatched','2026-05-05 10:00:00'),
-- Lot item 15 → Product 21 (Chocolate Bar)
(43, 15, 21, 7, 'QR-L7-P21-001', 1, 24, 24, 'active',    '2026-05-05 10:05:00'),
(44, 15, 21, 7, 'QR-L7-P21-002', 2, 24, 24, 'dispatched','2026-05-05 10:05:00'),
-- Lot item 16 → Product 22 (Royal Kulfi)
(45, 16, 22, 8, 'QR-L8-P22-001', 1, 36, 36, 'active',    '2026-05-12 10:00:00'),
(46, 16, 22, 8, 'QR-L8-P22-002', 2, 36, 36, 'active',    '2026-05-12 10:00:00'),
-- Lot item 17 → Product 23 (Strawberry Lolly)
(47, 17, 23, 8, 'QR-L8-P23-001', 1, 48, 48, 'active',    '2026-05-12 10:05:00'),
(48, 17, 23, 8, 'QR-L8-P23-002', 2, 48, 48, 'active',    '2026-05-12 10:05:00');

-- ============================================================
-- 14. INVENTORY (current warehouse stock per product)
-- ============================================================
INSERT IGNORE INTO `inventory` (`product_id`, `warehouse_id`, `qty_boxes`, `qty_pieces`) VALUES
-- Dhaka Central (warehouse 1)
(1,  1, 480, 0),
(2,  1, 290, 0),
(4,  1, 295, 0),
(5,  1, 385, 0),
(6,  1, 390, 0),
(8,  1,  95, 0),
(10, 1, 195, 0),
-- Chittagong (warehouse 2)
(12, 2, 390, 0),
(13, 2, 195, 0),
(14, 2, 290, 0),
(15, 2, 195, 0),
(16, 2, 390, 0),
(18, 2, 295, 0),
-- Sylhet (warehouse 3)
(20, 3, 195, 0),
(21, 3, 195, 0),
(22, 3, 148, 0),
(23, 3, 198, 0);

-- ============================================================
-- 15. ORDERS (from SRs)
-- ============================================================
INSERT IGNORE INTO `orders` (`id`, `sr_id`, `company_id`, `order_date`, `status`, `scanned_qrs`, `retailer_name`, `retailer_phone`, `created_at`) VALUES
(1, 1, 1, '2026-05-15', 'delivered',       NULL, 'Alam General Store',     '01501111101', '2026-05-15 09:00:00'),
(2, 1, 1, '2026-05-16', 'delivered',       NULL, 'Rahman Mart',            '01501111102', '2026-05-16 09:00:00'),
(3, 2, 1, '2026-05-15', 'out_for_delivery',NULL, 'Sunny Supermarket',      '01501111103', '2026-05-15 09:30:00'),
(4, 3, 2, '2026-05-16', 'delivered',       NULL, 'City Drug House',        '01501111104', '2026-05-16 10:00:00'),
(5, 4, 3, '2026-05-17', 'delivered',       NULL, 'Meghna Grocery',         '01501111105', '2026-05-17 09:00:00'),
(6, 5, 4, '2026-05-17', 'out_for_delivery',NULL, 'Port Side Shop',         '01501111106', '2026-05-17 09:30:00'),
(7, 6, 5, '2026-05-18', 'pending',         NULL, 'Sylhet Sweet Corner',    '01501111107', '2026-05-18 09:00:00'),
(8, 1, 1, '2026-05-19', 'pending',         NULL, 'Boro Bazar Traders',     '01501111108', '2026-05-19 09:00:00'),
(9, 2, 1, '2026-05-19', 'ready_sale',      NULL, 'Crescent General Store', '01501111109', '2026-05-19 09:30:00'),
(10,3, 2, '2026-05-20', 'pending',         NULL, 'Health Plus Pharmacy',   '01501111110', '2026-05-20 10:00:00'),
(11,4, 3, '2026-05-20', 'cancelled',       NULL, 'Noor Traders',           '01501111111', '2026-05-20 10:30:00'),
(12,5, 4, '2026-05-21', 'pending',         NULL, 'Deep Sea Mart',          '01501111112', '2026-05-21 09:00:00');

-- ============================================================
-- 16. ORDER ITEMS
-- ============================================================
INSERT IGNORE INTO `order_items` (`id`, `order_id`, `product_id`, `qty_pieces`, `qty_boxes_display`, `qty_pieces_remainder`, `unit_price`) VALUES
-- Order 1
(1,  1, 1, 48,  2, 0, 25.00),  -- 2 boxes Mango Juice
(2,  1, 2, 24,  1, 0, 25.00),  -- 1 box Litchi Juice
-- Order 2
(3,  2, 1, 72,  3, 0, 25.00),  -- 3 boxes Mango Juice
(4,  2, 4, 48,  1, 0, 20.00),  -- 1 box Chanachur
-- Order 3
(5,  3, 2, 48,  2, 0, 25.00),  -- 2 boxes Litchi Juice
(6,  3, 5, 36,  1, 0, 15.00),  -- 1 box Noodles
-- Order 4
(7,  4, 8, 12,  1, 0, 180.00), -- 1 box Savlon
(8,  4,10, 24,  1, 0, 35.00),  -- 1 box Nimki
-- Order 5
(9,  5,12, 24,  2, 0, 185.00), -- 2 boxes Soyabean Oil
(10, 5,13, 24,  1, 0, 95.00),  -- 1 box Mustard Oil
-- Order 6
(11, 6,16, 24,  1, 0, 40.00),  -- 1 box RC Cola
(12, 6,18, 48,  2, 0, 20.00),  -- 2 boxes Fresh Water
-- Order 7
(13, 7,20, 24,  1, 0, 30.00),  -- 1 box Vanilla Cup
(14, 7,21, 24,  1, 0, 40.00),  -- 1 box Chocolate Bar
-- Order 8
(15, 8, 1, 48,  2, 0, 25.00),
(16, 8, 6, 36,  1, 0, 18.00),
-- Order 9
(17, 9, 2, 48,  2, 0, 25.00),
(18, 9, 5, 72,  2, 0, 15.00),
-- Order 10
(19,10, 8, 12,  1, 0, 180.00),
(20,10,11, 12,  1, 0, 75.00),
-- Order 11
(21,11,12, 12,  1, 0, 185.00),
-- Order 12
(22,12,16, 48,  2, 0, 40.00),
(23,12,18, 24,  1, 0, 20.00);

-- ============================================================
-- 17. DISPATCHES
-- ============================================================
INSERT IGNORE INTO `dispatches` (`id`, `dsr_id`, `order_id`, `warehouse_id`, `manager_id`, `dispatch_date`, `status`, `created_at`) VALUES
(1, 1, 1, 1, 1, '2026-05-15', 'settled',         '2026-05-15 08:00:00'),
(2, 2, 2, 1, 1, '2026-05-16', 'settled',         '2026-05-16 08:00:00'),
(3, 1, 3, 1, 1, '2026-05-15', 'delivered',       '2026-05-15 08:30:00'),
(4, 3, 4, 2, 2, '2026-05-16', 'settled',         '2026-05-16 08:00:00'),
(5, 4, 5, 2, 2, '2026-05-17', 'settled',         '2026-05-17 08:00:00'),
(6, 3, 6, 2, 2, '2026-05-17', 'delivered',       '2026-05-17 08:30:00'),
(7, 5, 7, 3, 3, '2026-05-18', 'loaded',          '2026-05-18 08:00:00'),
(8, 1, 8, 1, 1, '2026-05-19', 'loading',         '2026-05-19 08:00:00'),
(9, 2, 9, 1, 1, '2026-05-19', 'loaded',          '2026-05-19 08:30:00');

-- ============================================================
-- 18. DISPATCH ITEMS (QR boxes loaded in each dispatch)
-- ============================================================
INSERT IGNORE INTO `dispatch_items` (`id`, `dispatch_id`, `order_id`, `qr_code_id`, `product_id`, `qty_out`) VALUES
-- Dispatch 1 (order 1: 2 boxes mango + 1 box litchi)
(1,  1, 1, 3,  1, 24),  -- QR-L1-P1-003 (dispatched)
(2,  1, 1, 4,  1, 24),  -- QR-L1-P1-004 (dispatched)
(3,  1, 1, 10, 2, 24),  -- QR-L1-P2-002 (dispatched)
-- Dispatch 2 (order 2: 3 boxes mango + 1 box chanachur)
(4,  2, 2, 17, 5, 36),  -- QR-L2-P5-001 (dispatched)
(5,  2, 2, 18, 5, 36),  -- QR-L2-P5-002 (dispatched)
(6,  2, 2, 14, 4, 48),  -- QR-L1-P4-002 (dispatched)
-- Dispatch 3 (order 3: 2 boxes litchi + 1 box noodles)
(7,  3, 3, 23, 6, 36),  -- QR-L2-P6-003 (dispatched)
-- Dispatch 4 (order 4: 1 box savlon + 1 box nimki)
(8,  4, 4, 25, 8,  12), -- QR-L3-P8-002 (dispatched)
(9,  4, 4, 29, 10, 24), -- QR-L3-P10-003 (dispatched)
-- Dispatch 5 (order 5: 2 boxes soya + 1 mustard)
(10, 5, 5, 31, 12, 12), -- QR-L4-P12-002 (dispatched)
(11, 5, 5, 35, 13, 24), -- QR-L4-P13-002 (dispatched)
-- Dispatch 6 (order 6: 1 RC Cola + 2 fresh water)
(12, 6, 6, 37, 16, 24), -- QR-L6-P16-002 (dispatched)
(13, 6, 6, 40, 18, 24), -- QR-L6-P18-002 (dispatched)
-- Dispatch 7 (order 7: igloo)
(14, 7, 7, 42, 20, 24), -- QR-L7-P20-002 (dispatched)
(15, 7, 7, 44, 21, 24); -- QR-L7-P21-002 (dispatched)

-- ============================================================
-- 19. CASH SETTLEMENTS (for settled dispatches)
-- ============================================================
INSERT IGNORE INTO `cash_settlements` (`id`, `dsr_id`, `dispatch_id`, `amount_expected`, `amount_submitted`, `difference`, `settlement_date`, `manager_id`, `notes`, `return_amount`, `damage_amount`, `expense_amount`, `notes_details`, `status`, `commission_amount`) VALUES
(1, 1, 1, 1750.00, 1750.00, 0.00, '2026-05-15', 1, 'Full settlement',           0.00,   0.00, 50.00,  'Fuel cost',           'approved', 175.00),
(2, 2, 2, 2340.00, 2300.00, 40.00,'2026-05-16', 1, 'Minor shortage noted',       0.00,  40.00, 60.00,  'Minor damage on road', 'approved', 234.00),
(3, 3, 4, 2340.00, 2340.00, 0.00, '2026-05-16', 2, 'Perfect settlement',         0.00,   0.00, 40.00,  'Bridge toll',         'approved', 234.00),
(4, 4, 5, 7400.00, 7350.00, 50.00,'2026-05-17', 2, 'Small cash shortage',        0.00,  50.00, 80.00,  'Fuel + helper cost',  'approved', 740.00);

-- ============================================================
-- 20. DSR ATTENDANCE (last 10 days for each DSR)
-- ============================================================
INSERT IGNORE INTO `dsr_attendance` (`dsr_id`, `warehouse_id`, `checkin_date`, `checkin_time`, `status`, `created_at`) VALUES
-- DSR 1 (Rahim, Dhaka)
(1, 1, '2026-05-12', '08:05:00', 'present', '2026-05-12 08:05:00'),
(1, 1, '2026-05-13', '07:58:00', 'present', '2026-05-13 07:58:00'),
(1, 1, '2026-05-14', '08:12:00', 'present', '2026-05-14 08:12:00'),
(1, 1, '2026-05-15', '08:00:00', 'present', '2026-05-15 08:00:00'),
(1, 1, '2026-05-16', '08:30:00', 'late',    '2026-05-16 08:30:00'),
(1, 1, '2026-05-19', '08:02:00', 'present', '2026-05-19 08:02:00'),
(1, 1, '2026-05-20', '08:00:00', 'present', '2026-05-20 08:00:00'),
(1, 1, '2026-05-21', '08:03:00', 'present', '2026-05-21 08:03:00'),
-- DSR 2 (Sumon, Dhaka)
(2, 1, '2026-05-12', '08:10:00', 'present', '2026-05-12 08:10:00'),
(2, 1, '2026-05-13', '08:00:00', 'present', '2026-05-13 08:00:00'),
(2, 1, '2026-05-14', '08:15:00', 'present', '2026-05-14 08:15:00'),
(2, 1, '2026-05-15', '08:05:00', 'present', '2026-05-15 08:05:00'),
(2, 1, '2026-05-16', '08:00:00', 'present', '2026-05-16 08:00:00'),
(2, 1, '2026-05-19', '09:00:00', 'late',    '2026-05-19 09:00:00'),
(2, 1, '2026-05-20', '08:00:00', 'present', '2026-05-20 08:00:00'),
-- DSR 3 (Farzana, Chittagong)
(3, 2, '2026-05-12', '08:00:00', 'present', '2026-05-12 08:00:00'),
(3, 2, '2026-05-13', '08:00:00', 'present', '2026-05-13 08:00:00'),
(3, 2, '2026-05-14', '08:00:00', 'absent',  '2026-05-14 08:00:00'),
(3, 2, '2026-05-15', '08:00:00', 'present', '2026-05-15 08:00:00'),
(3, 2, '2026-05-16', '08:05:00', 'present', '2026-05-16 08:05:00'),
(3, 2, '2026-05-17', '08:00:00', 'present', '2026-05-17 08:00:00'),
(3, 2, '2026-05-19', '08:00:00', 'present', '2026-05-19 08:00:00'),
(3, 2, '2026-05-20', '08:00:00', 'present', '2026-05-20 08:00:00'),
-- DSR 4 (Mizanur, Chittagong)
(4, 2, '2026-05-13', '08:00:00', 'present', '2026-05-13 08:00:00'),
(4, 2, '2026-05-14', '08:00:00', 'present', '2026-05-14 08:00:00'),
(4, 2, '2026-05-15', '08:00:00', 'present', '2026-05-15 08:00:00'),
(4, 2, '2026-05-16', '08:00:00', 'present', '2026-05-16 08:00:00'),
(4, 2, '2026-05-17', '08:00:00', 'present', '2026-05-17 08:00:00'),
(4, 2, '2026-05-19', '08:30:00', 'late',    '2026-05-19 08:30:00'),
(4, 2, '2026-05-20', '08:00:00', 'present', '2026-05-20 08:00:00'),
-- DSR 5 (Runa, Sylhet)
(5, 3, '2026-05-13', '08:00:00', 'present', '2026-05-13 08:00:00'),
(5, 3, '2026-05-14', '08:00:00', 'present', '2026-05-14 08:00:00'),
(5, 3, '2026-05-15', '08:00:00', 'present', '2026-05-15 08:00:00'),
(5, 3, '2026-05-18', '08:00:00', 'present', '2026-05-18 08:00:00'),
(5, 3, '2026-05-19', '08:00:00', 'present', '2026-05-19 08:00:00'),
(5, 3, '2026-05-20', '08:00:00', 'present', '2026-05-20 08:00:00'),
(5, 3, '2026-05-21', '08:10:00', 'present', '2026-05-21 08:10:00');

-- ============================================================
-- 21. EXPENSES (DSR field expenses)
-- ============================================================
INSERT IGNORE INTO `expenses` (`id`, `dsr_id`, `dispatch_id`, `amount`, `description`, `expense_date`, `status`) VALUES
(1, 1, 1, 50.00,  'Fuel cost for delivery van',          '2026-05-15', 'approved'),
(2, 2, 2, 60.00,  'Helper wages + fuel',                 '2026-05-16', 'approved'),
(3, 1, 3, 35.00,  'Bridge toll charges',                 '2026-05-15', 'approved'),
(4, 3, 4, 40.00,  'Ferry crossing fee',                  '2026-05-16', 'approved'),
(5, 4, 5, 80.00,  'Fuel + loading helper',               '2026-05-17', 'approved'),
(6, 3, 6, 45.00,  'Port area toll + parking',            '2026-05-17', 'pending'),
(7, 5, 7, 30.00,  'City road toll',                      '2026-05-18', 'pending'),
(8, 1, 8, 55.00,  'Fuel refill',                         '2026-05-19', 'pending'),
(9, 2, 9, 40.00,  'Morning breakfast for team',          '2026-05-19', 'pending'),
(10,1, NULL, 25.00,'Office stationery',                  '2026-05-20', 'pending'),
(11,3, NULL, 35.00,'Mobile recharge for communication',  '2026-05-20', 'pending'),
(12,4, NULL, 20.00,'Photocopy of delivery receipts',     '2026-05-17', 'approved');

-- ============================================================
-- 22. RETURNS
-- ============================================================
INSERT IGNORE INTO `returns` (`id`, `dispatch_id`, `manager_id`, `return_date`, `status`) VALUES
(1, 2, 1, '2026-05-16', 'completed'),  -- Return from dispatch 2 (minor damage)
(2, 5, 2, '2026-05-17', 'completed'),  -- Return from dispatch 5
(3, 7, 3, '2026-05-19', 'pending');    -- Return from dispatch 7 (Sylhet)

-- ============================================================
-- 23. RETURN ITEMS (boxes returned back)
-- ============================================================
INSERT IGNORE INTO `return_items` (`id`, `return_id`, `qr_code_id`, `product_id`, `qty_in`, `type`) VALUES
(1, 1, 2,  1, 24, 'scan'),   -- Depleted QR returned from dispatch 2
(2, 2, 31, 12, 12, 'scan'),  -- Soyabean box returned
(3, 3, 42, 20, 24, 'scan');  -- Vanilla cup returned from Sylhet dispatch

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================
-- End of dummy data seed
-- ============================================================
