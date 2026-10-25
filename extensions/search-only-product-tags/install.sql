-- Search-Only Product Tags: run once before refreshing modifications.
-- Replace `oc_` with the DB_PREFIX from your config.php.
ALTER TABLE `oc_product_description` ADD COLUMN `hidden_tag` TEXT NULL AFTER `tag`;
