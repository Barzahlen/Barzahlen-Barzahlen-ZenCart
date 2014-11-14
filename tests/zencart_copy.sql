-- phpMyAdmin SQL Dump
-- version 3.3.9.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 30. Mai 2012 um 12:02
-- Server Version: 5.5.9
-- PHP-Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `zencart_copy`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `configuration`
--

CREATE TABLE IF NOT EXISTS `configuration` (
  `configuration_id` int(11) NOT NULL AUTO_INCREMENT,
  `configuration_title` varchar(255) NOT NULL,
  `configuration_key` varchar(255) NOT NULL,
  `configuration_value` text NOT NULL,
  `configuration_description` varchar(255) NOT NULL,
  `configuration_group_id` int(11) NOT NULL,
  `sort_order` int(5) DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `use_function` varchar(255) DEFAULT NULL,
  `set_function` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`configuration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `configuration`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `orders_id` int(11) NOT NULL AUTO_INCREMENT,
  `customers_id` int(11) NOT NULL,
  `customers_cid` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `customers_vat_id` varchar(20) COLLATE latin1_german1_ci DEFAULT NULL,
  `customers_status` int(11) DEFAULT NULL,
  `customers_status_name` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `customers_status_image` varchar(64) COLLATE latin1_german1_ci DEFAULT NULL,
  `customers_status_discount` decimal(4,2) DEFAULT NULL,
  `customers_name` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `customers_firstname` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `customers_lastname` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `customers_company` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `customers_street_address` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `customers_suburb` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `customers_city` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `customers_postcode` varchar(10) COLLATE latin1_german1_ci NOT NULL,
  `customers_state` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `customers_country` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `customers_telephone` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `customers_email_address` varchar(96) COLLATE latin1_german1_ci NOT NULL,
  `customers_address_format_id` int(5) NOT NULL,
  `delivery_name` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `delivery_firstname` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `delivery_lastname` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `delivery_company` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `delivery_street_address` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `delivery_suburb` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `delivery_city` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `delivery_postcode` varchar(10) COLLATE latin1_german1_ci NOT NULL,
  `delivery_state` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `delivery_country` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `delivery_country_iso_code_2` char(2) COLLATE latin1_german1_ci NOT NULL,
  `delivery_address_format_id` int(5) NOT NULL,
  `billing_name` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `billing_firstname` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `billing_lastname` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `billing_company` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `billing_street_address` varchar(64) COLLATE latin1_german1_ci NOT NULL,
  `billing_suburb` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `billing_city` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `billing_postcode` varchar(10) COLLATE latin1_german1_ci NOT NULL,
  `billing_state` varchar(32) COLLATE latin1_german1_ci DEFAULT NULL,
  `billing_country` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `billing_country_iso_code_2` char(2) COLLATE latin1_german1_ci NOT NULL,
  `billing_address_format_id` int(5) NOT NULL,
  `payment_method` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `cc_type` varchar(20) COLLATE latin1_german1_ci DEFAULT NULL,
  `cc_owner` varchar(64) COLLATE latin1_german1_ci DEFAULT NULL,
  `cc_number` varchar(64) COLLATE latin1_german1_ci DEFAULT NULL,
  `cc_expires` varchar(4) COLLATE latin1_german1_ci DEFAULT NULL,
  `cc_start` varchar(4) COLLATE latin1_german1_ci DEFAULT NULL,
  `cc_issue` varchar(3) COLLATE latin1_german1_ci DEFAULT NULL,
  `cc_cvv` varchar(4) COLLATE latin1_german1_ci DEFAULT NULL,
  `comments` varchar(255) COLLATE latin1_german1_ci DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  `date_purchased` datetime DEFAULT NULL,
  `orders_status` int(5) NOT NULL,
  `orders_date_finished` datetime DEFAULT NULL,
  `currency` char(3) COLLATE latin1_german1_ci DEFAULT NULL,
  `currency_value` decimal(14,6) DEFAULT NULL,
  `account_type` int(1) NOT NULL DEFAULT '0',
  `payment_class` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `shipping_method` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `shipping_class` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `customers_ip` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `language` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `afterbuy_success` int(1) NOT NULL DEFAULT '0',
  `afterbuy_id` int(32) NOT NULL DEFAULT '0',
  `refferers_id` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `conversion_type` int(1) NOT NULL DEFAULT '0',
  `orders_ident_key` varchar(128) COLLATE latin1_german1_ci DEFAULT NULL,
  `barzahlen_transaction_id` int(11) NOT NULL DEFAULT '0',
  `barzahlen_transaction_state` varchar(7) NOT NULL DEFAULT '',
  PRIMARY KEY (`orders_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=5 ;

--
-- Daten für Tabelle `orders`
--

INSERT IGNORE INTO `orders` VALUES(1, 1, '', '', 0, 'Admin', 'admin_status.gif', 0.00, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', '0151', 'alexander.diebler@barzahlen.de', 5, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', 'DE', 5, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', 'DE', 5, 'barzahlen', '', '', '', '', '', '', '', '', NULL, '2012-05-07 10:27:23', 1, NULL, 'EUR', 1.000000, 0, 'barzahlen', 'Deutscher Paket Dienst (Versand ', 'dpd_dpd', '::1', 'german', 0, 0, '0', 2, NULL, 6382214, 'pending');
INSERT IGNORE INTO `orders` VALUES(2, 1, '', '', 0, 'Admin', 'admin_status.gif', 0.00, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', '0151', 'alexander.diebler@barzahlen.de', 5, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', 'DE', 5, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', 'DE', 5, 'barzahlen', '', '', '', '', '', '', '', '', NULL, '2012-05-07 10:32:45', 8, NULL, 'EUR', 1.000000, 0, 'barzahlen', 'Deutscher Paket Dienst (Versand ', 'dpd_dpd', '::1', 'german', 0, 0, '0', 2, NULL, 6382566, 'paid');
INSERT IGNORE INTO `orders` VALUES(3, 1, '', '', 0, 'Admin', 'admin_status.gif', 0.00, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', '0151', 'alexander.diebler@barzahlen.de', 5, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', 'DE', 5, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', 'DE', 5, 'barzahlen', '', '', '', '', '', '', '', '', NULL, '2012-05-07 10:33:08', 9, NULL, 'EUR', 1.000000, 0, 'barzahlen', 'Deutscher Paket Dienst (Versand ', 'dpd_dpd', '::1', 'german', 0, 0, '0', 2, NULL, 6382649, 'expired');
INSERT IGNORE INTO `orders` VALUES(4, 1, '', '', 0, 'Admin', 'admin_status.gif', 0.00, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', '0151', 'alexander.diebler@barzahlen.de', 5, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', 'DE', 5, 'Alexander Diebler', 'Alexander', 'Diebler', 'Cash Payment Solutions GmbH', 'Wallstr. 14a', '', 'Berlin', '10179', '', 'Germany', 'DE', 5, 'cod', '', '', '', '', '', '', '', '', NULL, '2012-05-07 10:33:33', 1, NULL, 'EUR', 1.000000, 0, 'cod', 'Deutscher Paket Dienst (Versand ', 'dpd_dpd', '::1', 'german', 0, 0, '0', 2, NULL, 0, '');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `orders_status_history`
--

CREATE TABLE IF NOT EXISTS `orders_status_history` (
  `orders_status_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `orders_id` int(11) NOT NULL,
  `orders_status_id` int(5) NOT NULL,
  `date_added` datetime NOT NULL,
  `customer_notified` int(1) DEFAULT '0',
  `comments` text COLLATE latin1_german1_ci,
  PRIMARY KEY (`orders_status_history_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=8 ;

--
-- Daten für Tabelle `orders_status_history`
--

INSERT IGNORE INTO `orders_status_history` VALUES(1, 1, 1, '2012-05-07 10:27:23', 1, 'Bar zahlen: Zahlschein erfolgreich angefordert und versendet');
INSERT IGNORE INTO `orders_status_history` VALUES(2, 2, 1, '2012-05-07 10:32:45', 1, 'Bar zahlen: Zahlschein erfolgreich angefordert und versendet');
INSERT IGNORE INTO `orders_status_history` VALUES(3, 3, 1, '2012-05-07 10:33:08', 1, 'Bar zahlen: Zahlschein erfolgreich angefordert und versendet');
INSERT IGNORE INTO `orders_status_history` VALUES(4, 4, 1, '2012-05-07 10:33:33', 1, '');
INSERT IGNORE INTO `orders_status_history` VALUES(5, 2, 8, '2012-05-07 10:45:59', 0, 'Der Zahlschein wurde beim Offline-Partner bezahlt.');
INSERT IGNORE INTO `orders_status_history` VALUES(6, 3, 9, '2012-05-07 10:46:41', 0, 'Der Zahlungszeitraum des Zahlscheins ist abgelaufen.');
INSERT IGNORE INTO `orders_status_history` VALUES(7, 5, 10, '2012-05-07 14:56:58', 1, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `orders_total`
--

CREATE TABLE IF NOT EXISTS `orders_total` (
  `orders_total_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `orders_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE latin1_german1_ci NOT NULL,
  `text` varchar(255) COLLATE latin1_german1_ci NOT NULL,
  `value` decimal(15,4) NOT NULL,
  `class` varchar(32) COLLATE latin1_german1_ci NOT NULL,
  `sort_order` int(11) NOT NULL,
  PRIMARY KEY (`orders_total_id`),
  KEY `idx_orders_total_orders_id` (`orders_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=17 ;

--
-- Daten für Tabelle `orders_total`
--

INSERT IGNORE INTO `orders_total` VALUES(1, 1, 'Zwischensumme:', ' 119,00 EUR', 119.0000, 'ot_subtotal', 10);
INSERT IGNORE INTO `orders_total` VALUES(2, 1, 'Deutscher Paket Dienst (Versand nach DE : 3 kg):', ' 3,07 EUR', 3.0700, 'ot_shipping', 30);
INSERT IGNORE INTO `orders_total` VALUES(3, 1, 'inkl. MwSt. 19%:', ' 19,00 EUR', 19.0000, 'ot_tax', 50);
INSERT IGNORE INTO `orders_total` VALUES(4, 1, '<b>Summe</b>:', '<strong> 122,07 EUR</strong>', 122.0700, 'ot_total', 99);
INSERT IGNORE INTO `orders_total` VALUES(5, 2, 'Zwischensumme:', ' 119,00 EUR', 119.0000, 'ot_subtotal', 10);
INSERT IGNORE INTO `orders_total` VALUES(6, 2, 'Deutscher Paket Dienst (Versand nach DE : 3 kg):', ' 3,07 EUR', 3.0700, 'ot_shipping', 30);
INSERT IGNORE INTO `orders_total` VALUES(7, 2, 'inkl. MwSt. 19%:', ' 19,00 EUR', 19.0000, 'ot_tax', 50);
INSERT IGNORE INTO `orders_total` VALUES(8, 2, '<b>Summe</b>:', '<strong> 122,07 EUR</strong>', 122.0700, 'ot_total', 99);
INSERT IGNORE INTO `orders_total` VALUES(9, 3, 'Zwischensumme:', ' 119,00 EUR', 119.0000, 'ot_subtotal', 10);
INSERT IGNORE INTO `orders_total` VALUES(10, 3, 'Deutscher Paket Dienst (Versand nach DE : 3 kg):', ' 3,07 EUR', 3.0700, 'ot_shipping', 30);
INSERT IGNORE INTO `orders_total` VALUES(11, 3, 'inkl. MwSt. 19%:', ' 19,00 EUR', 19.0000, 'ot_tax', 50);
INSERT IGNORE INTO `orders_total` VALUES(12, 3, '<b>Summe</b>:', '<strong> 122,07 EUR</strong>', 122.0700, 'ot_total', 99);
INSERT IGNORE INTO `orders_total` VALUES(13, 4, 'Zwischensumme:', ' 119,00 EUR', 119.0000, 'ot_subtotal', 10);
INSERT IGNORE INTO `orders_total` VALUES(14, 4, 'Deutscher Paket Dienst (Versand nach DE : 3 kg):', ' 3,07 EUR', 3.0700, 'ot_shipping', 30);
INSERT IGNORE INTO `orders_total` VALUES(15, 4, 'inkl. MwSt. 19%:', ' 19,00 EUR', 19.0000, 'ot_tax', 50);
INSERT IGNORE INTO `orders_total` VALUES(16, 4, '<b>Summe</b>:', '<strong> 122,07 EUR</strong>', 122.0700, 'ot_total', 99);
