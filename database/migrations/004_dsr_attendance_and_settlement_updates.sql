-- Migration 004: DSR Attendance and Cash Settlement Updates

-- Create DSR Attendance Table
CREATE TABLE IF NOT EXISTS dsr_attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dsr_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  checkin_date DATE NOT NULL,
  checkin_time TIME NOT NULL,
  status VARCHAR(20) DEFAULT 'present',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_dsr_attendance (dsr_id, checkin_date),
  FOREIGN KEY (dsr_id) REFERENCES dsr(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add new columns to cash_settlements to support mobile settlement detail reporting
ALTER TABLE cash_settlements ADD COLUMN IF NOT EXISTS return_amount DECIMAL(12,2) DEFAULT 0.00;
ALTER TABLE cash_settlements ADD COLUMN IF NOT EXISTS damage_amount DECIMAL(12,2) DEFAULT 0.00;
ALTER TABLE cash_settlements ADD COLUMN IF NOT EXISTS expense_amount DECIMAL(12,2) DEFAULT 0.00;
ALTER TABLE cash_settlements ADD COLUMN IF NOT EXISTS notes_details TEXT DEFAULT NULL;
ALTER TABLE cash_settlements ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending';
