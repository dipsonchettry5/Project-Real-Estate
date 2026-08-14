-- Migration for Furnished Status, Road Width, and Land Area text fields.
-- Run this against your `realestate` database in phpMyAdmin.

ALTER TABLE `properties`
  MODIFY COLUMN `land_area` VARCHAR(255) DEFAULT NULL;

ALTER TABLE `properties`
  ADD COLUMN IF NOT EXISTS `furnished_status` VARCHAR(50) DEFAULT NULL AFTER `built_area`,
  ADD COLUMN IF NOT EXISTS `road_width` VARCHAR(100) DEFAULT NULL AFTER `furnished_status`;
