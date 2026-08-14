-- Run this against your existing `realestate` database, after migration_user_uploads.sql.
-- Adds an approval workflow: new listings start as 'pending' and only show
-- publicly once an admin approves them.
-- Safe to re-run: guarded with IF NOT EXISTS (MariaDB 10.0.2+).

ALTER TABLE `properties`
  ADD COLUMN IF NOT EXISTS `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `rejection_reason` VARCHAR(255) DEFAULT NULL AFTER `status`;

-- Backfill everything that already exists so nothing already live disappears.
UPDATE `properties` SET `status` = 'approved' WHERE `status` = 'pending';
