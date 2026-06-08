-- Migration: 007_pending_approvals.sql
-- Adds the pending_approvals table for manager edit/cancel requests requiring admin approval

CREATE TABLE IF NOT EXISTS `pending_approvals` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `action_type`    ENUM('edit_order','cancel_order','edit_dispatch') NOT NULL,
  `target_id`      INT(11) NOT NULL,
  `payload`        LONGTEXT NOT NULL,
  `summary`        VARCHAR(500) DEFAULT NULL,
  `requested_by`   INT(11) NOT NULL,
  `requested_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`         ENUM('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by`    INT(11) DEFAULT NULL,
  `reviewed_at`    TIMESTAMP NULL DEFAULT NULL,
  `admin_notes`    TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requested_by` (`requested_by`),
  KEY `target_id` (`target_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
