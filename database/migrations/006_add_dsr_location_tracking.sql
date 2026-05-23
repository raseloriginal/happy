-- database/migrations/006_add_dsr_location_tracking.sql
CREATE TABLE IF NOT EXISTS `dsr_locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `dsr_id` INT NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `accuracy` DECIMAL(8, 2) DEFAULT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `dsr_id` (`dsr_id`),
  CONSTRAINT `dsr_locations_ibfk_1` FOREIGN KEY (`dsr_id`) REFERENCES `dsr` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
