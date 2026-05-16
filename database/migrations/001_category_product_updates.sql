-- Migration: 001_category_product_updates.sql

-- 1. Make categories.company_id nullable
ALTER TABLE categories MODIFY company_id INT NULL;
