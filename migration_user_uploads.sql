-- Run this against your existing `realestate` database.
-- Safe to re-run: every change is guarded with IF NOT EXISTS (MariaDB 10.0.2+).

-- users.role is already used by login.php/register.php/admin.php — make sure it exists.
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `role` VARCHAR(20) NOT NULL DEFAULT 'user' AFTER `password`;

-- properties needs the extra fields add.php already writes to...
ALTER TABLE `properties`
  ADD COLUMN IF NOT EXISTS `bedrooms`    INT DEFAULT NULL       AFTER `image`,
  ADD COLUMN IF NOT EXISTS `bathrooms`   DECIMAL(4,1) DEFAULT NULL AFTER `bedrooms`,
  ADD COLUMN IF NOT EXISTS `land_area`   DECIMAL(10,2) DEFAULT NULL AFTER `bathrooms`,
  ADD COLUMN IF NOT EXISTS `built_area`  DECIMAL(10,2) DEFAULT NULL AFTER `land_area`,
  ADD COLUMN IF NOT EXISTS `amenities`   TEXT DEFAULT NULL      AFTER `built_area`,
  ADD COLUMN IF NOT EXISTS `description` TEXT DEFAULT NULL      AFTER `amenities`;

-- ...and user_id so a property is tied to the user who uploaded it.
ALTER TABLE `properties`
  ADD COLUMN IF NOT EXISTS `user_id` INT DEFAULT NULL AFTER `id`;

-- Backfill any existing rows to the admin account (id 1) so nothing is left owner-less.
UPDATE `properties` SET `user_id` = 1 WHERE `user_id` IS NULL;

-- Optional but recommended: enforce the relationship and speed up "my listings" lookups.
-- NOTE: unlike the ADD COLUMN lines above, this one is NOT safe to re-run — MariaDB 10.4
-- doesn't support "ADD CONSTRAINT IF NOT EXISTS". Only run it once; if you re-run the
-- whole script, comment this block out after the first successful run.
ALTER TABLE `properties`
  ADD CONSTRAINT `fk_properties_user`
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;
