
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
