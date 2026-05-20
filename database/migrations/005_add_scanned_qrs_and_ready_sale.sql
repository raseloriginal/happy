-- Migration 005: Add scanned_qrs column and modify status enum in orders table

ALTER TABLE `orders` ADD COLUMN `scanned_qrs` TEXT NULL DEFAULT NULL AFTER `status`;

ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending','ready_sale','out_for_delivery','delivered','cancelled') DEFAULT 'pending';
