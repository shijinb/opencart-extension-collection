-- Synthetic test data, loaded after OpenCart's own install/opencart.sql (which includes demo products).
-- IDs start at 9000 to stay clear of the demo data. Loaded with a non-strict sql_mode, as the
-- OpenCart installer does, so omitted NOT NULL columns take their implicit defaults.

-- Products -------------------------------------------------------------------------------------
INSERT INTO `oc_product` (`product_id`, `model`, `sku`, `upc`, `ean`, `quantity`, `price`, `tax_class_id`, `manufacturer_id`, `shipping`, `status`, `date_available`, `date_added`, `date_modified`) VALUES
(9001, 'AVG-100', 'AVG-100', '', '', 10, 12.0000, 0, 0, 1, 1, '2020-01-01', NOW(), NOW()),
(9002, 'GEL-200', 'GEL-200', '', '', 3, 8.5000, 0, 0, 1, 1, '2020-01-01', NOW(), NOW()),
(9003, 'OLD-300', 'OLD-300', '', '', 0, 5.0000, 0, 0, 0, 0, '2020-01-01', NOW(), NOW());

INSERT INTO `oc_product_description` (`product_id`, `language_id`, `name`, `description`, `tag`, `hidden_tag`, `meta_title`) VALUES
(9001, 1, 'Aloe Vera Gel', '&lt;p&gt;Soothing gel.&lt;/p&gt;', 'skin care', 'kattar vazha, aloe jel', 'Aloe Vera Gel'),
(9002, 1, 'Cooling Gel with Vera and Aloe Extract', 'Cooling gel.', 'cooling', NULL, 'Cooling Gel'),
(9003, 1, 'Discontinued Soap', 'Old soap.', '', NULL, 'Discontinued Soap');

INSERT INTO `oc_product_to_store` (`product_id`, `store_id`) VALUES (9001, 0), (9002, 0), (9003, 0);

-- Options: product 9001 has two option values (one out of stock) ---------------------------------
INSERT INTO `oc_option` (`option_id`, `type`, `sort_order`) VALUES (9401, 'select', 0);
INSERT INTO `oc_option_description` (`option_id`, `language_id`, `name`) VALUES (9401, 1, 'Size');
INSERT INTO `oc_option_value` (`option_value_id`, `option_id`, `image`, `sort_order`) VALUES (9411, 9401, '', 1), (9412, 9401, '', 2);
INSERT INTO `oc_option_value_description` (`option_value_id`, `language_id`, `option_id`, `name`) VALUES (9411, 1, 9401, '100 ml'), (9412, 1, 9401, '50 ml');
INSERT INTO `oc_product_option` (`product_option_id`, `product_id`, `option_id`, `value`, `required`) VALUES (9351, 9001, 9401, '', 1);
INSERT INTO `oc_product_option_value` (`product_option_value_id`, `product_option_id`, `product_id`, `option_id`, `option_value_id`, `quantity`, `subtract`, `price`, `price_prefix`) VALUES
(9301, 9351, 9001, 9401, 9411, 0, 1, 2.0000, '+'),
(9302, 9351, 9001, 9401, 9412, 7, 1, 1.0000, '-');

-- Categories: 9101 > 9102 > 9103 (enabled); 9105 (disabled) > 9104 -----------------------------
INSERT INTO `oc_category` (`category_id`, `parent_id`, `top`, `column`, `status`, `date_added`, `date_modified`) VALUES
(9101, 0, 1, 1, 1, NOW(), NOW()),
(9102, 9101, 0, 1, 1, NOW(), NOW()),
(9103, 9102, 0, 1, 1, NOW(), NOW()),
(9105, 0, 0, 1, 0, NOW(), NOW()),
(9104, 9105, 0, 1, 1, NOW(), NOW());

INSERT INTO `oc_category_description` (`category_id`, `language_id`, `name`, `description`, `meta_title`) VALUES
(9101, 1, 'Skin Care', '', 'Skin Care'),
(9102, 1, 'Face', '', 'Face'),
(9103, 1, 'Face Gels', '', 'Face Gels'),
(9105, 1, 'Archive', '', 'Archive'),
(9104, 1, 'Old Gels', '', 'Old Gels');

INSERT INTO `oc_category_path` (`category_id`, `path_id`, `level`) VALUES
(9101, 9101, 0),
(9102, 9101, 0), (9102, 9102, 1),
(9103, 9101, 0), (9103, 9102, 1), (9103, 9103, 2),
(9105, 9105, 0),
(9104, 9105, 0), (9104, 9104, 1);

INSERT INTO `oc_category_to_store` (`category_id`, `store_id`) VALUES (9101, 0), (9102, 0), (9103, 0), (9104, 0), (9105, 0);

-- 9001 is in the root category and the deepest one; 9002 only under the disabled parent.
INSERT INTO `oc_product_to_category` (`product_id`, `category_id`) VALUES (9001, 9101), (9001, 9103), (9002, 9104);

-- Reviews: two approved (5 and 4) and one pending (1) for 9001 -----------------------------------
INSERT INTO `oc_review` (`review_id`, `product_id`, `customer_id`, `author`, `text`, `rating`, `status`, `date_added`, `date_modified`) VALUES
(9201, 9001, 0, 'Asha', 'Works well on sunburn.', 5, 1, '2024-05-01 10:00:00', NOW()),
(9202, 9001, 0, 'Ravi', 'Good, a little sticky.', 4, 1, '2024-06-01 10:00:00', NOW()),
(9203, 9001, 0, 'Spam', 'Pending moderation.', 1, 0, '2024-07-01 10:00:00', NOW());

INSERT INTO `oc_product_special` (`product_special_id`, `product_id`, `customer_group_id`, `priority`, `price`, `date_start`, `date_end`) VALUES
(9601, 9001, 1, 1, 9.9900, '0000-00-00', '2099-12-31');

-- Orders: 9502 canceled (7) and 9503 missing (0) are skipped by prev/next ------------------------
INSERT INTO `oc_order` (`order_id`, `store_id`, `customer_id`, `customer_group_id`, `firstname`, `lastname`, `email`, `telephone`, `total`, `order_status_id`, `tracking`, `language_id`, `currency_id`, `currency_code`, `currency_value`, `date_added`, `date_modified`) VALUES
(9501, 0, 0, 1, 'Test', 'One',   'one@example.com',   '+91 98470 00001', 100.0000, 1, 'SUMMER''24', 1, 1, 'USD', 1.0, NOW(), NOW()),
(9502, 0, 0, 1, 'Test', 'Two',   'two@example.com',   '+91 98470 00002', 50.0000,  7, 'SUMMER''24', 1, 1, 'USD', 1.0, NOW(), NOW()),
(9503, 0, 0, 1, 'Test', 'Three', 'three@example.com', '+91 98470 00003', 20.0000,  0, '',           1, 1, 'USD', 1.0, NOW(), NOW()),
(9504, 0, 0, 1, 'Test', 'Four',  'four@example.com',  '+91 98470 00001', 75.0000,  2, 'NEWSLETTER', 1, 1, 'USD', 1.0, NOW(), NOW());
