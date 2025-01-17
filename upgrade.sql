
---
--- Commit: 87ba9f123d25b92dc9c8f313336841bf6af8276d
--- Date: Fri Nov 8 17:16:44 2019 +0330
---
ALTER TABLE `financial_banks_accounts` CHANGE `shaba` `shaba` VARCHAR(31) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL DEFAULT NULL;

--
--	Commit: f2408dcf8bc9b02f8d7c41e081450f47b9c665ba
--	Author: Hossein Hosni <hosni.hossein@gmail.com>
--	Date:   Wed Apr 15 12:17:38 2020 +0430
--	Fix #53 - Standardize permission to compatibility to UserPanel new permissions style
--
UPDATE `userpanel_usertypes_permissions` SET name = REPLACE(`name`,'financial_transactions_refund','financial_transactions_refund_add') WHERE `name` LIKE 'financial_transactions_refund';
UPDATE `userpanel_usertypes_permissions` SET name = REPLACE(`name`,'financial_transactions_pays_accept','financial_transactions_pay_accept') WHERE `name` LIKE 'financial_transactions_pays_accept';
UPDATE `userpanel_usertypes_permissions` SET name = REPLACE(`name`,'financial_transactions_guest_pay_link','financial_transactions_guest-pay-link') WHERE `name` LIKE 'financial_transactions_guest_pay_link';

---
---	Commit:	0cf6cb64b9fe11304a5f14443a02247c6fa32147
---	Date:   Sun Aug 2 14:26:15 2020 +0430
---
ALTER TABLE `financial_transactions` CHANGE `price` `price` DOUBLE NOT NULL;
ALTER TABLE `financial_transactions_products` CHANGE `price` `price` DOUBLE NOT NULL; 

---
---	Commit:	256025ce8bbc643e5dcdd0ce1d3d44bf44074e3a
---	Date:   Sun Aug 2 14:27:52 2020 +0430
---
ALTER TABLE `financial_payports_pays` CHANGE `price` `price` DOUBLE NOT NULL;
ALTER TABLE `financial_transactions_pays` CHANGE `price` `price` DOUBLE NOT NULL;

---
---	Commit: 6bde94e1ff6d70e08c5fc9d1b7d13c7b1e7c651e
---	Date:   Tue Aug 4 10:44:32 2020 +0430
---
ALTER TABLE `financial_currencies` ADD UNIQUE(`title`);
ALTER TABLE `financial_currencies`
ADD `rounding_behaviour` TINYINT NOT NULL AFTER `update_at`,
ADD `rounding_precision` TINYINT NOT NULL AFTER `rounding_behaviour`;

--
--	Commit:	7b6225097cfdbf934866fe37e191ffe70705a1b1
--
ALTER TABLE `financial_currencies` ADD `prefix` VARCHAR(31) NULL AFTER `id`;
ALTER TABLE `financial_currencies` ADD `postfix` VARCHAR(31) NULL AFTER `title`;

--
-- Commit:
--
ALTER TABLE `financial_transactions_products` CHANGE `description` `description` varchar(255) COLLATE 'utf8_general_ci' NULL AFTER `title`;

--
-- Commit: 8fcf7d5fde04323c5b43c90c920668f69a80da73
--

ALTER TABLE `financial_transactions_products` ADD `vat` DOUBLE NULL DEFAULT NULL AFTER `number`;

--
-- Commit: 
--

ALTER TABLE `financial_transactions_products` ADD `service_id` INT NULL DEFAULT NULL AFTER `type`;
ALTER TABLE `financial_transactions_products` ADD INDEX(`type`, `service_id`)

-- RUN WITH BACKUP

-- UPDATE
--     financial_transactions_products
-- INNER JOIN financial_transactions_products_params ON financial_transactions_products.id = financial_transactions_products_params.product AND financial_transactions_products_params.name = 'service'
-- SET
--     financial_transactions_products.service_id = financial_transactions_products_params.value
-- WHERE
--     financial_transactions_products.service_id IS NULL;

--
-- Commit: e31c103ba127278a25bbcda29fe99f6486133c7b
--
ALTER TABLE `financial_transactions_pays` CHANGE `method` `method` VARCHAR(25) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL;
UPDATE `financial_transactions_pays` SET `method` = 'credit' WHERE `method` = '1';
UPDATE `financial_transactions_pays` SET `method` = 'banktransfer' WHERE `method` = '2';
UPDATE `financial_transactions_pays` SET `method` = 'onlinepay' WHERE `method` = '3';
ALTER TABLE `financial_transactions_pays` ADD `updated_at` INT NULL DEFAULT NULL AFTER `currency`; 

