-- Migration: 001_category_product_updates.sql

-- 1. Make categories.company_id nullable
ALTER TABLE categories MODIFY company_id INT NULL;

-- 2. Add box_type to products table
ALTER TABLE products ADD COLUMN box_type VARCHAR(50) NULL AFTER name;
