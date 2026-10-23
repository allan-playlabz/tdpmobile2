
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` binary(16) NOT NULL,
  `agent_id` binary(16) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_agent` (`admin_id`,`agent_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` binary(16) NOT NULL,
  `name` varchar(255) CHARACTER SET latin1 NOT NULL,
  `email` varchar(100) CHARACTER SET latin1 NOT NULL,
  `password` varchar(64) CHARACTER SET latin1 NOT NULL,
  `otp` char(6) CHARACTER SET latin1 DEFAULT NULL,
  `otp_created` datetime DEFAULT NULL,
  `role` enum('master','normal','sales','editor') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `status` enum('authorized','not_authorized') CHARACTER SET latin1 DEFAULT 'authorized',
  `all_agents` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_api_last_used` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` binary(16) NOT NULL,
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_agent_api_last_used_agent` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_sessions` (
  `id` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data` blob,
  `expires` int(10) unsigned DEFAULT NULL,
  `agent_user_id` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_user_idx` (`agent_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_topups` (
  `id` binary(16) NOT NULL,
  `agent_id` binary(16) DEFAULT NULL,
  `amount` float(10,2) DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topup_date` date DEFAULT NULL,
  `approved_by` binary(16) DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('approved','rejected','pending','approved_pending_aw_settlement') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_idx` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_users` (
  `id` binary(16) NOT NULL,
  `agent_id` binary(16) NOT NULL,
  `name` varchar(100) CHARACTER SET latin1 NOT NULL,
  `email` varchar(100) CHARACTER SET latin1 NOT NULL,
  `password` varchar(64) CHARACTER SET latin1 NOT NULL,
  `role` enum('master','master2','normal','viewer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `verification_code` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp` int(11) DEFAULT NULL,
  `otp_created` datetime DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_read_time` datetime DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET latin1 NOT NULL DEFAULT 'active',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_idx` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agents` (
  `id` binary(16) NOT NULL,
  `first_name` varchar(50) CHARACTER SET latin1 NOT NULL,
  `last_name` varchar(50) CHARACTER SET latin1 NOT NULL,
  `email` varchar(100) CHARACTER SET latin1 NOT NULL,
  `password` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_code` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp` smallint(6) DEFAULT NULL,
  `otp_created` datetime DEFAULT NULL,
  `company_name` varchar(100) CHARACTER SET latin1 NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `general_category` varchar(50) CHARACTER SET latin1 DEFAULT NULL,
  `country` varchar(100) CHARACTER SET latin1 NOT NULL,
  `issued_bank` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `account_no` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `bank_reference_code` char(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(50) CHARACTER SET latin1 DEFAULT NULL,
  `gender` enum('male','female') CHARACTER SET latin1 NOT NULL,
  `credits` decimal(10,2) unsigned DEFAULT NULL,
  `low_credit_limit` decimal(10,2) unsigned DEFAULT '0.00',
  `low_credit_notification_sent` tinyint(1) DEFAULT '0',
  `rebates` decimal(10,2) NOT NULL DEFAULT '0.00',
  `role` enum('master','sub') CHARACTER SET latin1 DEFAULT 'master',
  `master_agent_id` binary(16) DEFAULT NULL,
  `enable_generation_confirmation` tinyint(1) DEFAULT '0',
  `enable_api` tinyint(1) NOT NULL,
  `api_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secret_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_v1_expiry_date` date DEFAULT NULL,
  `webhook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_secret` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allowed_api_cancel` tinyint(1) DEFAULT '0',
  `email_displayname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET latin1 DEFAULT 'active',
  `can_add_sub` tinyint(1) DEFAULT '0',
  `last_activity` datetime DEFAULT NULL,
  `hide_voucher_status` tinyint(1) DEFAULT '1',
  `enable_agent_login` tinyint(1) DEFAULT '0',
  `enable_credit_logs_view` tinyint(1) DEFAULT '0',
  `mobile_otp` tinyint(1) DEFAULT '1',
  `bcc_to_master` tinyint(1) DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agents_session_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_idx` (`ip`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agents_signups` (
  `id` binary(16) NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('master','sub') COLLATE utf8mb4_unicode_ci DEFAULT 'master',
  `email_displayname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agents_token_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_key` varchar(32) NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_idx` (`session_key`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `airwallex_webhooks` (
  `id` binary(16) NOT NULL,
  `webhook_id` varchar(64) NOT NULL,
  `event_type` varchar(64) NOT NULL,
  `payload` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `bank_reference_code` char(8) DEFAULT NULL,
  `agent_id` binary(16) DEFAULT NULL,
  `agent_topup_id` binary(16) DEFAULT NULL,
  `duplicate_count` int(11) NOT NULL DEFAULT '0',
  `status` enum('received','processed','rejected','error','received_handled','processed_handled','rejected_handled','error_handled') NOT NULL DEFAULT 'received',
  `error_message` varchar(500) DEFAULT NULL,
  `handled_remarks` varchar(500) DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `handled_by` binary(16) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webhook_id_idx` (`webhook_id`),
  KEY `bank_ref_idx` (`bank_reference_code`),
  KEY `agent_id_idx` (`agent_id`),
  KEY `status_created_idx` (`status`,`created`),
  KEY `handled_by_idx` (`handled_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allotments` (
  `id` binary(16) NOT NULL,
  `merchant_id` binary(16) NOT NULL,
  `name` varchar(100) NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `available` int(11) DEFAULT NULL,
  `pricing_type` enum('settlement','fdate','fdatetime') DEFAULT 'fdatetime',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mechant_idx` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allowed_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_id` binary(16) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` binary(16) NOT NULL,
  `subject` varchar(100) CHARACTER SET latin1 NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `publish_start_date` datetime NOT NULL,
  `publish_end_date` datetime NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_pdfs` (
  `id` binary(16) NOT NULL,
  `package_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchased_ticket_sku_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticket_sku_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pdf_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validity_start_date` date NOT NULL,
  `validity_end_date` date NOT NULL,
  `status` enum('new','purchased','used','cancelled','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `sold` tinyint(1) DEFAULT '0',
  `redeemed_datetime` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_sku_idx` (`purchased_ticket_sku_id`),
  KEY `status_idx` (`status`),
  KEY `filename` (`filename`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_request_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` binary(16) NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `query_params` text COLLATE utf8mb4_unicode_ci,
  `method` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `core` tinyint(1) DEFAULT '0',
  `response` text COLLATE utf8mb4_unicode_ci,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_key` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_token` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires` timestamp NULL DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `auth_token` (`auth_token`),
  KEY `session_key` (`session_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attraction_medias` (
  `id` binary(16) NOT NULL,
  `attraction_id` binary(16) NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order` tinyint(4) NOT NULL,
  `mime` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attraction_idx` (`attraction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attraction_operating_hours` (
  `id` binary(16) NOT NULL,
  `attraction_id` binary(16) NOT NULL,
  `day_of_week` int(11) NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
  `slot_number` int(11) NOT NULL DEFAULT '1' COMMENT 'Multiple time slots per day',
  `opens_at` time DEFAULT NULL,
  `closes_at` time DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  `is_24_hour` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attraction_day_slot_idx` (`attraction_id`,`day_of_week`,`slot_number`),
  KEY `attraction_idx` (`attraction_id`),
  KEY `attraction_day_idx` (`attraction_id`,`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attraction_schedules` (
  `id` binary(16) NOT NULL,
  `attraction_id` binary(16) NOT NULL,
  `override_date` date NOT NULL,
  `slot_number` int(11) NOT NULL DEFAULT '1' COMMENT 'Multiple time slots per day',
  `opens_at` time DEFAULT NULL,
  `closes_at` time DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  `is_24_hour` tinyint(1) NOT NULL DEFAULT '0',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Holiday, special event, etc.',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attraction_date_slot_idx` (`attraction_id`,`override_date`,`slot_number`),
  KEY `attraction_idx` (`attraction_id`),
  KEY `attraction_date_idx` (`attraction_id`,`override_date`),
  KEY `date_idx` (`override_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attraction_translations` (
  `id` binary(16) NOT NULL,
  `attraction_id` binary(16) NOT NULL,
  `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `terms_and_conditions` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attraction_idx` (`attraction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attractions` (
  `id` binary(16) NOT NULL,
  `merchant_id` binary(16) DEFAULT NULL,
  `category_id` binary(16) NOT NULL,
  `city_id` binary(16) DEFAULT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_chinese` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku_id` varchar(100) CHARACTER SET latin1 NOT NULL,
  `note_for_reseller` text COLLATE utf8mb4_unicode_ci,
  `display_position` smallint(6) DEFAULT '1',
  `featured_image` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `terms_and_conditions` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `print_as_qr_code` tinyint(1) DEFAULT '1',
  `gallery` text CHARACTER SET latin1,
  `product_type` enum('simple','grouped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simple',
  `pricing_type` enum('settlement','fdate','fdatetime','two_way_fdatetime') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'settlement',
  `type` enum('single','package','transfer') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtype` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_booking_to` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `include_question_group` tinyint(1) NOT NULL DEFAULT '0',
  `min_quantity` smallint(6) DEFAULT NULL,
  `max_quantity` smallint(6) DEFAULT NULL,
  `api_sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_billing_user_info` tinyint(1) DEFAULT '1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `get_availability_as_attraction` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_id_UNIQUE` (`sku_id`),
  KEY `sku_idx` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attractions_connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attraction_id` binary(16) DEFAULT NULL,
  `connection_id` binary(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attraction_idx` (`attraction_id`),
  KEY `connection_idx` (`connection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attractions_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attraction_id` binary(16) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attraction_tag_unq` (`attraction_id`,`tag_id`),
  KEY `tag_id_idx` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auto_populate_allotments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allotment_id` binary(16) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `data` text,
  `no_of_days` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `allotment_idx` (`allotment_id`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auto_populate_fdatesettlements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `auto_populate_fixeddate_id` int(11) NOT NULL,
  `fdatesettlement_id` int(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uniq` (`auto_populate_fixeddate_id`,`fdatesettlement_id`),
  KEY `idx_apf_id` (`auto_populate_fixeddate_id`),
  KEY `idx_fdatesettlement_id` (`fdatesettlement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auto_populate_fixeddates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` binary(16) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `data` text,
  `no_of_days` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title_idx` (`ticket_id`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `barcodes` (
  `id` binary(16) NOT NULL,
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `package_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchased_ticket_sku_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticket_sku_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validity_start_date` date NOT NULL,
  `validity_end_date` date NOT NULL,
  `redeem_start_date` datetime DEFAULT NULL,
  `redeem_end_date` datetime DEFAULT NULL,
  `status` enum('new','purchased','used','flexi_unused','partially_redeemed_expired','cancelled','expired','archiving') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `sold` tinyint(1) DEFAULT '0',
  `redeemed_datetime` datetime DEFAULT NULL,
  `custom_data` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_UNIQUE` (`code`),
  UNIQUE KEY `idx_UNIQUE` (`idx`),
  KEY `ticket_sku_idx` (`purchased_ticket_sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cancelled_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` binary(16) NOT NULL,
  `agent_id` binary(16) DEFAULT NULL,
  `order_ref_id` varchar(45) DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `type` enum('generated','barcode','pdf','api','order') NOT NULL,
  `refunded` tinyint(1) DEFAULT NULL,
  `refunded_amount` decimal(10,2) DEFAULT NULL,
  `return_stock` tinyint(1) DEFAULT NULL,
  `action_by` varchar(45) DEFAULT NULL,
  `is_admin_cancelled` tinyint(1) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_type_idx` (`type`,`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` binary(16) NOT NULL,
  `name` varchar(100) CHARACTER SET latin1 NOT NULL,
  `description` varchar(500) CHARACTER SET latin1 DEFAULT NULL,
  `parent_id` binary(16) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category_translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` binary(16) NOT NULL,
  `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attraction_id` (`category_id`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cities` (
  `id` binary(16) NOT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `connections` (
  `id` binary(16) NOT NULL,
  `name` varchar(255) NOT NULL,
  `api_url` varchar(255) DEFAULT NULL,
  `credentials` text,
  `function_name` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_logs` (
  `id` binary(16) NOT NULL,
  `agent_id` binary(16) NOT NULL,
  `order_id` binary(16) DEFAULT NULL,
  `log_no` int(11) NOT NULL AUTO_INCREMENT,
  `debit_credit` enum('debit','credit') CHARACTER SET latin1 NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `credits` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`),
  KEY `log_no_idx` (`log_no`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_stats` (
  `id` binary(16) NOT NULL,
  `agent_id` binary(16) NOT NULL,
  `attraction_id` binary(16) NOT NULL,
  `date` date NOT NULL,
  `tickets_sold` int(11) NOT NULL DEFAULT '0',
  `transaction_count` int(11) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_agent_attraction_date` (`agent_id`,`attraction_id`,`date`),
  KEY `idx_agent_date` (`agent_id`,`date`),
  KEY `idx_attraction_date` (`attraction_id`,`date`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `database_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `summary` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `context` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `created` datetime NOT NULL,
  `ip` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `hostname` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `uri` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `refer` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ekiosk_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) DEFAULT NULL,
  `source_sku` varchar(100) DEFAULT NULL,
  `target_sku` varchar(100) DEFAULT NULL,
  `target_attraction_sku` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source_sku_UNIQUE` (`source_sku`),
  UNIQUE KEY `label_UNIQUE` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ekiosk_sessions` (
  `id` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data` blob,
  `expires` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `email_to` varchar(255) NOT NULL,
  `email_from` varchar(255) NOT NULL,
  `bcc` varchar(255) DEFAULT NULL,
  `cc` varchar(255) DEFAULT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` text,
  `sent` tinyint(1) DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `external_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_ref_id` varchar(45) NOT NULL,
  `external_ref_id` varchar(100) NOT NULL,
  `connection_name` varchar(45) NOT NULL,
  `other_info` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref_id_unq` (`order_ref_id`,`external_ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorite_attractions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attraction_id` binary(16) NOT NULL,
  `agent_user_id` binary(16) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_user_idx` (`agent_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fdate_override_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fdatesettlement_id` int(11) NOT NULL,
  `fdate_id` int(11) NOT NULL,
  `rate` float NOT NULL,
  `rebate` float NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uniq_rate_fdate` (`fdatesettlement_id`,`fdate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fdatesettlements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` binary(16) NOT NULL,
  `label` varchar(255) NOT NULL,
  `rate` float NOT NULL DEFAULT '0',
  `rebate` float NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fdatesettlements_agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` binary(16) NOT NULL,
  `fdatesettlement_id` int(11) NOT NULL,
  `agent_id` binary(16) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uniq_rate_agent` (`fdatesettlement_id`,`agent_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_fdatesettlement_id` (`fdatesettlement_id`),
  KEY `idx_agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fixeddates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` binary(16) NOT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `rate` float DEFAULT NULL,
  `rebate` float DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `quantity_sold` int(11) DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fixeddates_agents2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attraction_id` binary(16) NOT NULL,
  `ticket_id` binary(16) NOT NULL,
  `agent_id` binary(16) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_idx` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generated_codes` (
  `id` binary(16) NOT NULL,
  `package_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ticket_id` binary(16) DEFAULT NULL,
  `ticket_sku_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('purchased','used','flexi_unused','partially_redeemed_expired','partially_redeemed_cancelled','cancelled','expired') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sold` tinyint(1) DEFAULT '0',
  `redeemable` tinyint(4) DEFAULT NULL,
  `redeemed` tinyint(4) DEFAULT NULL,
  `redeemed_datetime` datetime DEFAULT NULL,
  `merchant_user_id` binary(16) DEFAULT NULL,
  `redeem_start_date` datetime DEFAULT NULL,
  `redeem_end_date` datetime DEFAULT NULL,
  `flexi_redeem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invcharts` (
  `id` binary(16) NOT NULL,
  `sku_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `merchant_id` binary(16) NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('generated','barcode','pdf') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `quantity_sold` int(11) NOT NULL,
  `overwrite_ticket` tinyint(1) NOT NULL,
  `voucher_template` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher_code_type` enum('barcode','qr_code') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overwrite_values` mediumtext COLLATE utf8mb4_unicode_ci,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_id_UNIQUE` (`sku_id`),
  KEY `merchant_idx` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventories` (
  `ticket_id` binary(16) NOT NULL,
  `order_id` binary(16) DEFAULT NULL,
  `user_id` binary(16) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `current_quantity` int(11) DEFAULT NULL,
  `sold` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `type` enum('sold','upload','adjust','delete','create','init','expired','transfer_in','transfer_out','extended') DEFAULT NULL COMMENT 'extended is only for barcodes and pdfs that are not sold and expired',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `remarks` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_idx` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `land_transfer_medias` (
  `id` binary(16) NOT NULL,
  `land_transfer_id` binary(16) NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order` tinyint(4) NOT NULL,
  `mime` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `land_transfer_idx` (`land_transfer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `land_transfers` (
  `id` binary(16) NOT NULL,
  `category_id` binary(16) NOT NULL,
  `sku_id` varchar(100) CHARACTER SET latin1 NOT NULL,
  `note_for_reseller` text CHARACTER SET latin1,
  `display_position` smallint(6) NOT NULL DEFAULT '1',
  `featured_image` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `description` text CHARACTER SET latin1 NOT NULL,
  `terms_and_conditions` text CHARACTER SET latin1 NOT NULL,
  `type` varchar(50) CHARACTER SET latin1 NOT NULL,
  `status` enum('draft','approved','rejected','published','expired','deleted') CHARACTER SET latin1 NOT NULL DEFAULT 'draft',
  `print_as_qr_code` tinyint(1) DEFAULT '0',
  `gallery` text CHARACTER SET latin1,
  `email_bookings_to` varchar(500) CHARACTER SET latin1 DEFAULT NULL,
  `publish_start_date` datetime NOT NULL,
  `publish_end_date` datetime NOT NULL,
  `release_period` tinyint(4) DEFAULT NULL,
  `redemption_start_date` datetime NOT NULL,
  `redemption_end_date` datetime NOT NULL,
  `merchant_id` binary(16) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_id_UNIQUE` (`sku_id`),
  KEY `merchant_idx` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `last_otp_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_user_id` binary(16) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agent_user_ip_unq` (`agent_user_id`,`ip`),
  KEY `agent_user_id_idx` (`agent_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `live_tickets` (
  `id` binary(16) NOT NULL,
  `attraction_id` binary(16) NOT NULL,
  `sku_id` varchar(100) CHARACTER SET latin1 NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `terms_and_conditions` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence` tinyint(4) NOT NULL DEFAULT '1',
  `publish_start_date` datetime NOT NULL,
  `publish_end_date` datetime NOT NULL,
  `price` float DEFAULT NULL,
  `release_period` tinyint(4) DEFAULT NULL,
  `redemption_start_date` datetime NOT NULL,
  `redemption_end_date` datetime NOT NULL,
  `pricing_type` enum('settlement','fdate','fdatetime') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'settlement',
  `instant_confirmation` tinyint(1) DEFAULT '1',
  `quantity` int(11) DEFAULT NULL,
  `quantity_sold` int(11) NOT NULL DEFAULT '0',
  `voucher_note` text COLLATE utf8mb4_unicode_ci,
  `type` enum('normal','complex','ctc') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `flexi_options` text COLLATE utf8mb4_unicode_ci,
  `require_travellers_info` tinyint(1) DEFAULT '0',
  `multiple_redemption` tinyint(1) DEFAULT '0',
  `voucher_template` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher_code_type` enum('barcode','qr_code') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'barcode',
  `agent_notification` text COLLATE utf8mb4_unicode_ci,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attraction_idx` (`attraction_id`),
  KEY `sku_idx` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `loc` enum('agent','admin','redeem','merchant') COLLATE utf8mb4_unicode_ci DEFAULT 'agent',
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_idx` (`ip`,`email`,`loc`),
  KEY `created_idx` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` binary(16) DEFAULT NULL,
  `user_table` varchar(45) CHARACTER SET latin1 NOT NULL,
  `type` varchar(15) CHARACTER SET latin1 NOT NULL,
  `table_name` varchar(40) CHARACTER SET latin1 NOT NULL,
  `record_id` binary(16) NOT NULL,
  `values_before` text CHARACTER SET latin1,
  `values_after` text CHARACTER SET latin1,
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchant_allowed_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `merchant_id` binary(16) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchant_sessions` (
  `id` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data` blob,
  `expires` int(10) unsigned DEFAULT NULL,
  `merchant_user_id` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_user_idx` (`merchant_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchant_users` (
  `id` binary(16) NOT NULL,
  `merchant_id` binary(16) NOT NULL,
  `name` varchar(100) CHARACTER SET latin1 NOT NULL,
  `email` varchar(100) CHARACTER SET latin1 NOT NULL,
  `password` varchar(64) CHARACTER SET latin1 NOT NULL,
  `role` enum('operations','finance','finops') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operations',
  `status` enum('active','inactive') CHARACTER SET latin1 NOT NULL DEFAULT 'active',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_idx` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchants` (
  `id` binary(16) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET latin1 NOT NULL,
  `contact_number` varchar(15) CHARACTER SET latin1 DEFAULT NULL,
  `business_description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person_designation` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person_number` varchar(45) CHARACTER SET latin1 NOT NULL,
  `contact_person_email` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `merchant_website` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') CHARACTER SET latin1 DEFAULT 'active',
  `online_only` tinyint(1) DEFAULT NULL,
  `signature` text CHARACTER SET latin1,
  `voucher_template` varchar(45) CHARACTER SET latin1 DEFAULT NULL,
  `voucher_code_type` enum('barcode','qr_code') CHARACTER SET latin1 DEFAULT 'barcode',
  `api_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secret_key` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `view_sales` tinyint(1) DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchants_session_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_idx` (`ip`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchants_token_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_key` varchar(32) NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_idx` (`session_key`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `old_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no` int(11) DEFAULT NULL,
  `purchased_date` date DEFAULT NULL,
  `purchased_time` time DEFAULT NULL,
  `redemption_date` date DEFAULT NULL,
  `redemption_time` time DEFAULT NULL,
  `merchant` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reseller` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reseller_reference_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmation_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attraction_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_price` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commission_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total_amount` double DEFAULT NULL,
  `total_commission` double DEFAULT NULL,
  `quantity_redeemed` int(11) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visit_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `confirmation_code_UNIQUE` (`confirmation_code`),
  KEY `confirmation_code_idx` (`confirmation_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `onefg_performance_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_ak` varchar(100) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `product_description` varchar(255) DEFAULT NULL,
  `performance_ak` varchar(100) NOT NULL,
  `performance_date` date DEFAULT NULL,
  `performance_time` time DEFAULT NULL,
  `seat_category_description` varchar(255) DEFAULT NULL,
  `availability` int(11) DEFAULT NULL,
  `price` float(10,2) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx` (`product_ak`,`performance_ak`,`performance_date`,`performance_time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `onefg_product_choices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_ak` varchar(80) NOT NULL,
  `dmgcategory_ak` varchar(100) DEFAULT NULL,
  `hash` varchar(64) NOT NULL,
  `choices` varchar(1000) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_ak_idx` (`product_ak`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `onefg_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_ak` varchar(80) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_performance` tinyint(1) DEFAULT '0',
  `is_addon` tinyint(1) DEFAULT '0',
  `event_ak` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_ak_UNIQUE` (`product_ak`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `onefg_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_ref_id` varchar(45) NOT NULL,
  `sale_ak` varchar(100) NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_idx` (`order_ref_id`,`sale_ak`),
  KEY `order_ref_idx` (`order_ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text,
  `autoload` tinyint(1) NOT NULL DEFAULT '1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_option_name` (`name`),
  KEY `idx_autoload` (`autoload`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_item_supplier_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_api_id` int(11) NOT NULL,
  `order_item_id` binary(16) NOT NULL,
  `supplier_api_order_ref_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('created','pending','failed','done','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'created',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` binary(16) NOT NULL,
  `order_id` binary(16) NOT NULL,
  `ticket_id` binary(16) NOT NULL,
  `qty` tinyint(4) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `rebate` decimal(10,2) unsigned DEFAULT '0.00',
  `order_data` text COLLATE utf8mb4_unicode_ci,
  `date_of_visit` date DEFAULT NULL,
  `time_of_visit` time DEFAULT NULL,
  `ticket_info` mediumtext COLLATE utf8mb4_unicode_ci,
  `status` enum('created','waiting','done','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'created',
  `api_vouchers` text COLLATE utf8mb4_unicode_ci,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_idx` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` binary(16) NOT NULL,
  `tracking_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_ref_id` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pi_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_user_id` binary(16) NOT NULL,
  `agent_id` binary(16) DEFAULT NULL,
  `customer_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','in_progress','waiting','done','cancelled','refunded','rejected','void') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `convinience_fee` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `checkout_info` text COLLATE utf8mb4_unicode_ci,
  `payment_method` enum('credits','credit_card','wechat_pay','alipay','paynow','gcash','grabpay','shopeepay') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_api` tinyint(1) DEFAULT '0',
  `email_voucher` tinyint(1) DEFAULT '1',
  `is_email_sent` tinyint(1) DEFAULT '0',
  `failed_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tracking_no_idx` (`tracking_no`),
  KEY `order_ref_id_idx` (`order_ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders_to_complete` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(100) NOT NULL,
  `check_after` timestamp NULL DEFAULT NULL,
  `status` enum('pending','done') DEFAULT 'pending',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_ticket_id` binary(16) NOT NULL,
  `ticket_id` binary(16) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reset_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('admin','agent','merchant_user') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_idx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_gateways` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET latin1 NOT NULL,
  `description` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `data` json DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_markups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_method_id` int(11) NOT NULL,
  `payment_gateway_id` int(11) NOT NULL,
  `rate` float NOT NULL,
  `type` enum('percentage','value') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `additional` float NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_gateway_id` int(11) DEFAULT NULL,
  `status` enum('enabled','disabled') COLLATE utf8mb4_unicode_ci DEFAULT 'disabled',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_ref_id` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paypal_records` (
  `id` binary(16) NOT NULL,
  `order_id` binary(16) NOT NULL,
  `txn_id` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8mb4_unicode_ci,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_idx` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pdfs` (
  `id` binary(16) NOT NULL,
  `package_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchased_ticket_sku_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticket_sku_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validity_start_date` date NOT NULL,
  `validity_end_date` date NOT NULL,
  `status` enum('new','purchased','used','flexi_unused','partially_redeemed_expired','cancelled','expired','archiving') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `sold` tinyint(1) DEFAULT '0',
  `redeemed_datetime` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdf_filename_unq` (`pdf_filename`,`filename`),
  KEY `ticket_sku_idx` (`purchased_ticket_sku_id`),
  KEY `status_idx` (`status`),
  KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_group_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_group_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('text','textarea','checkbox','radio','select','range','date','time','country','country_code','nationality','number') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `sequence` int(11) NOT NULL,
  `min` smallint(5) unsigned DEFAULT NULL,
  `max` smallint(5) unsigned DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `queued_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `task` enum('connection') NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_task_idx` (`email`,`task`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommended_attractions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attraction_id` binary(16) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attraction_id_unq` (`attraction_id`),
  KEY `display_order_idx` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redeem_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_key` varchar(32) NOT NULL,
  `auth_token` varchar(64) DEFAULT NULL,
  `api_key` varchar(64) DEFAULT NULL,
  `expires` timestamp NULL DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `auth_token` (`auth_token`),
  KEY `session_key` (`session_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rws_logos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `type` varchar(45) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rws_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offer_no` varchar(100) NOT NULL,
  `offer_name` varchar(255) DEFAULT NULL,
  `offer_identifier` varchar(255) DEFAULT NULL,
  `choice_constrain` enum('single','multiple','fixed') NOT NULL,
  `package_spec` varchar(255) DEFAULT NULL,
  `validity_start_date` datetime DEFAULT NULL,
  `validity_end_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `offer_no_UNIQUE` (`offer_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rws_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offer_no` varchar(255) NOT NULL,
  `product_no` varchar(100) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `plu_code` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `theme_park` varchar(255) DEFAULT NULL,
  `ticket_type` varchar(100) DEFAULT NULL,
  `choice_constrain` enum('Fixed','Single','Multiple') DEFAULT NULL,
  `admission` varchar(100) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `purchase_with` varchar(255) DEFAULT NULL,
  `language_choice` varchar(255) DEFAULT NULL,
  `need_choice_count` smallint(6) DEFAULT NULL,
  `is_group_ticket` tinyint(1) DEFAULT '0',
  `price_rule_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_no_UNIQUE` (`offer_no`,`product_no`,`plu_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rws_template_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offer_plu` varchar(255) DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offer_plu_idx` (`offer_plu`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rws_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `images` varchar(255) DEFAULT NULL,
  `images_selected` text,
  `park_name` varchar(255) NOT NULL,
  `validity_minimum` varchar(255) DEFAULT NULL,
  `validity_statement` varchar(255) NOT NULL,
  `ticket_type` varchar(500) NOT NULL,
  `ticket_how_to` text NOT NULL,
  `has_timing` tinyint(1) DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  KEY `name_idx` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rws_templates_custom_names` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rws_template_id` int(11) NOT NULL,
  `agent_id` binary(16) NOT NULL,
  `agent_custom_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rws_template_idx` (`rws_template_id`,`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sent_mails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_ref_id` varchar(100) NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_ref_id_UNIQUE` (`order_ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sent_webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_url` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `agent_id` binary(16) NOT NULL,
  `order_ref_id` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `response` varchar(500) COLLATE latin1_general_ci DEFAULT NULL,
  `response_status` int(11) DEFAULT NULL,
  `tries` int(11) NOT NULL DEFAULT '0',
  `next_retry_at` datetime DEFAULT NULL,
  `data` text COLLATE latin1_general_ci,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_ref_id` (`order_ref_id`),
  KEY `idx_retry_poll` (`response_status`,`tries`,`next_retry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data` blob,
  `expires` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settlement_agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attraction_id` binary(16) NOT NULL,
  `ticket_id` binary(16) NOT NULL,
  `settlement_id` int(11) NOT NULL,
  `agent_id` binary(16) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `settlement_idx` (`settlement_id`),
  KEY `agent_idx` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settlements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` binary(16) NOT NULL,
  `settlement_group` tinyint(4) NOT NULL,
  `rate` float(10,2) NOT NULL,
  `rebate` float(10,2) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_idx` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) DEFAULT NULL,
  `allotment_id` binary(16) NOT NULL,
  `date` date NOT NULL,
  `time` time DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `available` int(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_unq` (`uuid`),
  KEY `allotment_id` (`allotment_id`,`date`,`time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_monitoring_pins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` binary(16) NOT NULL,
  `ticket_id` binary(16) NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_ticket_pin` (`admin_id`,`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stripe_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pi_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_apis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_base_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `access_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_token_expires` datetime DEFAULT NULL,
  `send_agent_id` tinyint(1) DEFAULT '0',
  `send_country` tinyint(1) DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_unq` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_api_id` int(11) NOT NULL,
  `order_ref_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_api_order_ref_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','in_progress','waiting','done','cancelled','refunded','rejected') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unq` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_translations` (
  `id` binary(16) NOT NULL,
  `ticket_id` binary(16) NOT NULL,
  `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `terms_and_conditions` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attraction_idx` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_trip_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` binary(16) NOT NULL,
  `type` enum('depart','return') COLLATE utf8mb4_unicode_ci NOT NULL,
  `linked_ticket_id` binary(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` binary(16) NOT NULL,
  `attraction_id` binary(16) NOT NULL,
  `connection_id` binary(16) DEFAULT NULL,
  `allotment_id` binary(16) DEFAULT NULL,
  `supplier_api_id` int(11) DEFAULT NULL,
  `question_group_id` int(11) DEFAULT NULL,
  `sku_id` varchar(100) CHARACTER SET latin1 NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `terms_and_conditions` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence` smallint(6) NOT NULL DEFAULT '1',
  `publish_start_date` datetime NOT NULL,
  `publish_end_date` datetime NOT NULL,
  `price` float(10,2) DEFAULT NULL,
  `release_period` tinyint(4) DEFAULT NULL,
  `cutoff_seconds` int(11) DEFAULT '0',
  `redemption_start_date` datetime DEFAULT NULL,
  `redemption_end_date` datetime DEFAULT NULL,
  `redemption_type` enum('immediate_redemption','today','redemption_selected','redemption_date','duration') COLLATE utf8mb4_unicode_ci DEFAULT 'redemption_date',
  `duration` smallint(6) DEFAULT NULL,
  `duration_type` enum('days','months') COLLATE utf8mb4_unicode_ci DEFAULT 'days',
  `pricing_type` enum('settlement','fdate','fdatetime','two_way_fdatetime') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'settlement',
  `instant_confirmation` tinyint(1) DEFAULT '1',
  `stock` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `quantity_sold` int(11) NOT NULL DEFAULT '0',
  `voucher_note` text COLLATE utf8mb4_unicode_ci,
  `type` enum('normal','complex','ctc') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `flexi_options` text COLLATE utf8mb4_unicode_ci,
  `package_config` text COLLATE utf8mb4_unicode_ci,
  `require_travellers_info` tinyint(1) DEFAULT '0',
  `multiple_redemption` tinyint(1) DEFAULT '0',
  `voucher_template` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher_code_type` enum('barcode','qr_code') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'barcode',
  `agent_notification` text COLLATE utf8mb4_unicode_ci,
  `voucher_type` enum('generated','barcode','pdf','api') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `single_pdf` tinyint(1) DEFAULT '0',
  `cost_price` decimal(10,2) DEFAULT NULL COMMENT 'settlement price of CAT to merchant',
  `api_purchase` tinyint(1) DEFAULT '0',
  `api_sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_cancellable` tinyint(1) DEFAULT '0',
  `show_stock` tinyint(1) DEFAULT '0',
  `min_quantity` smallint(6) DEFAULT NULL,
  `max_quantity` smallint(6) DEFAULT NULL,
  `activity_duration` int(11) DEFAULT NULL COMMENT 'in minutes',
  `lead_pax_only` tinyint(1) DEFAULT '0',
  `markup_value` decimal(10,2) DEFAULT NULL,
  `markup_type` enum('percentage','value') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `minimum_selling_price` decimal(10,2) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attraction_idx` (`attraction_id`),
  KEY `sku_idx` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets_invcharts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` binary(16) NOT NULL,
  `invchart_id` binary(16) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_idx` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_ipaddress` (
  `id` binary(16) NOT NULL,
  `user_id` binary(16) NOT NULL,
  `ip_address` varchar(64) NOT NULL,
  `type` varchar(45) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_idx` (`user_id`,`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voucher_logos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `type` varchar(45) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voucher_merging` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) DEFAULT NULL,
  `agent_id` binary(16) DEFAULT NULL,
  `order_ref_id` varchar(64) DEFAULT NULL,
  `type` enum('singlepdf','zip') DEFAULT NULL,
  `status` enum('pending','creating','done') DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voucher_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_text` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('voucher','invoice','email') COLLATE utf8mb4_unicode_ci DEFAULT 'voucher',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_idx` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vouchers` (
  `id` binary(16) NOT NULL,
  `voucher_type` enum('pdf','barcode','one_code_multi','angry_birds','generated','api') COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'this is the generated code or code from pdf/barcode',
  `pdf_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_item_id` binary(16) NOT NULL,
  `merchant_user_id` binary(16) DEFAULT NULL,
  `order_qty_idx` tinyint(4) DEFAULT NULL,
  `single_pdf` varchar(72) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_id_UNIQUE` (`code_id`),
  KEY `code_idx` (`code_id`),
  KEY `pdf_filename_idx` (`pdf_filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

