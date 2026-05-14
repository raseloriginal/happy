-- Migration: 002_add_dealer_percentage.sql
ALTER TABLE products ADD COLUMN dealer_percentage DECIMAL(5,2) DEFAULT 0.00 AFTER selling_price;
