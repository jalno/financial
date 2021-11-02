CREATE TABLE `financial_banks` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`title` varchar(100) NOT NULL,
	`status` tinyint(1) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `title_fa` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_banks_accounts` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`bank_id` int(11) NOT NULL,
	`user_id` int(11) NOT NULL,
	`owner` varchar(255) COLLATE utf8_persian_ci NOT NULL,
	`account` varchar(100) COLLATE utf8_persian_ci DEFAULT NULL,
	`cart` varchar(19) COLLATE utf8_persian_ci DEFAULT NULL,
	`shaba` varchar(31) COLLATE utf8_persian_ci DEFAULT NULL,
	`reject_reason` varchar(255) COLLATE utf8_persian_ci DEFAULT NULL,
	`oprator_id` int(11) DEFAULT NULL,
	`status` tinyint(4) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `bank_id` (`bank_id`),
	KEY `user_id` (`user_id`),
	KEY `oprator_id` (`oprator_id`),
	CONSTRAINT `financial_banks_accounts_ibfk_1` FOREIGN KEY (`bank_id`) REFERENCES `financial_banks` (`id`) ON DELETE CASCADE,
	CONSTRAINT `financial_banks_accounts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `userpanel_users` (`id`) ON DELETE CASCADE,
	CONSTRAINT `financial_banks_accounts_ibfk_3` FOREIGN KEY (`oprator_id`) REFERENCES `userpanel_users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_persian_ci;

CREATE TABLE `financial_currencies` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`prefix` varchar(31) DEFAULT NULL,
	`title` varchar(25) NOT NULL,
	`postfix` varchar(31) DEFAULT NULL,
	`update_at` int(11) NOT NULL,
	`rounding_behaviour` tinyint(4) NOT NULL,
	`rounding_precision` tinyint(4) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_currencies_params` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`currency` int(11) NOT NULL,
	`name` varchar(255) NOT NULL,
	`value` varchar(255) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `currency` (`currency`),
	CONSTRAINT `financial_currencies_params_ibfk_1` FOREIGN KEY (`currency`) REFERENCES `financial_currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_currencies_rates` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`currency` int(11) NOT NULL,
	`changeTo` int(11) NOT NULL,
	`price` float NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `currency` (`currency`,`changeTo`),
	KEY `changeTo` (`changeTo`),
	CONSTRAINT `financial_currencies_rates_ibfk_1` FOREIGN KEY (`currency`) REFERENCES `financial_currencies` (`id`) ON DELETE CASCADE,
	CONSTRAINT `financial_currencies_rates_ibfk_2` FOREIGN KEY (`changeTo`) REFERENCES `financial_currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_payports` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`controller` varchar(255) COLLATE utf8_persian_ci NOT NULL,
	`title` varchar(100) COLLATE utf8_persian_ci NOT NULL,
	`account` int(11) DEFAULT NULL,
	`status` tinyint(1) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `account` (`account`),
	CONSTRAINT `financial_payports_ibfk_1` FOREIGN KEY (`account`) REFERENCES `financial_banks_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_payports_currencies` (
	`currency` int(11) NOT NULL,
	`payport` int(11) NOT NULL,
	UNIQUE KEY `currency` (`currency`,`payport`),
	KEY `payport` (`payport`),
	CONSTRAINT `financial_payports_currencies_ibfk_1` FOREIGN KEY (`currency`) REFERENCES `financial_currencies` (`id`) ON DELETE CASCADE,
	CONSTRAINT `financial_payports_currencies_ibfk_2` FOREIGN KEY (`payport`) REFERENCES `financial_payports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_payports_params` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`payport` int(11) NOT NULL,
	`name` varchar(255) NOT NULL,
	`value` text NOT NULL,
	PRIMARY KEY (`id`),
	KEY `payport` (`payport`),
	CONSTRAINT `financial_payports_params_ibfk_1` FOREIGN KEY (`payport`) REFERENCES `financial_payports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_transactions` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`token` varchar(15) NOT NULL,
	`user` int(11) DEFAULT NULL,
	`title` varchar(100) NOT NULL,
	`price` DOUBLE NOT NULL,
	`create_at` int(11) NOT NULL,
	`expire_at` int(11) DEFAULT NULL,
	`paid_at` int(11) DEFAULT NULL,
	`currency` int(11) NOT NULL,
	`status` tinyint(4) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `user` (`user`),
	KEY `currency` (`currency`),
	CONSTRAINT `financial_transactions_ibfk_1` FOREIGN KEY (`user`) REFERENCES `userpanel_users` (`id`) ON DELETE CASCADE,
	CONSTRAINT `financial_transactions_ibfk_2` FOREIGN KEY (`currency`) REFERENCES `financial_currencies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_payports_pays` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`payport` int(11) NOT NULL,
	`transaction` int(11) NOT NULL,
	`date` int(11) NOT NULL,
	`price` DOUBLE NOT NULL,
	`currency` int(11) NOT NULL,
	`ip` varchar(15) COLLATE utf8_persian_ci DEFAULT NULL,
	`status` tinyint(1) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `payport` (`payport`),
	KEY `transaction` (`transaction`),
	KEY `currency` (`currency`),
	CONSTRAINT `financial_payports_pays_ibfk_1` FOREIGN KEY (`payport`) REFERENCES `financial_payports` (`id`) ON DELETE CASCADE,
	CONSTRAINT `financial_payports_pays_ibfk_2` FOREIGN KEY (`transaction`) REFERENCES `financial_transactions` (`id`) ON DELETE CASCADE,
	CONSTRAINT `financial_payports_pays_ibfk_3` FOREIGN KEY (`currency`) REFERENCES `financial_currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_persian_ci;

CREATE TABLE `financial_payports_pays_params` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`pay` int(11) NOT NULL,
	`name` varchar(100) NOT NULL,
	`value` text NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `pay_2` (`pay`,`name`),
	KEY `pay` (`pay`),
	CONSTRAINT `financial_payports_pays_params_ibfk_1` FOREIGN KEY (`pay`) REFERENCES `financial_payports_pays` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_transactions_params` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`transaction` int(11) NOT NULL,
	`name` varchar(100) NOT NULL,
	`value` text NOT NULL,
	PRIMARY KEY (`id`),
	KEY `product` (`transaction`),
	CONSTRAINT `financial_transactions_params_ibfk_1` FOREIGN KEY (`transaction`) REFERENCES `financial_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_transactions_pays` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`transaction` int(11) NOT NULL,
	`method` tinyint(4) NOT NULL,
	`date` int(11) NOT NULL,
	`price` DOUBLE NOT NULL,
	`currency` int(11) NOT NULL,
	`status` tinyint(1) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `transaction` (`transaction`),
	KEY `currency` (`currency`),
	CONSTRAINT `financial_transactions_pays_ibfk_1` FOREIGN KEY (`transaction`) REFERENCES `financial_transactions` (`id`) ON DELETE CASCADE,
	CONSTRAINT `financial_transactions_pays_ibfk_2` FOREIGN KEY (`currency`) REFERENCES `financial_currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_persian_ci;

CREATE TABLE `financial_transactions_pays_params` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`pay` int(11) NOT NULL,
	`name` varchar(100) NOT NULL,
	`value` text NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `pay_2` (`pay`,`name`),
	KEY `pay` (`pay`),
	CONSTRAINT `financial_transactions_pays_params_ibfk_1` FOREIGN KEY (`pay`) REFERENCES `financial_transactions_pays` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_transactions_products` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`transaction` int(11) NOT NULL,
	`title` varchar(100) DEFAULT NULL,
	`description` varchar(255) DEFAULT NULL,
	`type` varchar(255) DEFAULT NULL,
	`method` tinyint(4) NOT NULL,
	`price` DOUBLE NOT NULL,
	`discount` float NOT NULL,
	`number` int(11) NOT NULL DEFAULT 1,
	`vat` double DEFAULT NULL,
	`currency` int(11) NOT NULL,
	`configure` tinyint(1) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `transaction` (`transaction`),
	KEY `currency` (`currency`),
	CONSTRAINT `financial_transactions_products_ibfk_1` FOREIGN KEY (`transaction`) REFERENCES `financial_transactions` (`id`) ON DELETE CASCADE,
	CONSTRAINT `financial_transactions_products_ibfk_2` FOREIGN KEY (`currency`) REFERENCES `financial_currencies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `financial_transactions_products_params` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`product` int(11) NOT NULL,
	`name` varchar(100) NOT NULL,
	`value` text NOT NULL,
	PRIMARY KEY (`id`),
	KEY `product` (`product`),
	CONSTRAINT `financial_transactions_products_params_ibfk_1` FOREIGN KEY (`product`) REFERENCES `financial_transactions_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `financial_currencies` (`id`, `title`, `update_at`, `rounding_behaviour`, `rounding_precision`) VALUES ('1', 'ریال', '0', '2', '2');
INSERT INTO `options` (`name`, `value`, `autoload`) VALUES ('packages.financial.defaultCurrency', '1', '1');
