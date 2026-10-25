-- Search-Only Product Tags: run after removing the modification and refreshing modifications.
-- Replace `oc_` with the DB_PREFIX from your config.php. This permanently deletes the stored tags.
ALTER TABLE `oc_product_description` DROP COLUMN `hidden_tag`;
