
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
CREATE TABLE `api_request_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` binary(16) NOT NULL,
  `url` varchar(255) NOT NULL,
  `query_params` text,
  `method` varchar(45) NOT NULL,
  `ip` varchar(64) NOT NULL,
  `core` tinyint(1) DEFAULT '0',
  `response` text,
  `response_datetime` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_idx` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `database_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `summary` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `message` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `context` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `created` datetime NOT NULL,
  `ip` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `hostname` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `uri` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `refer` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `count` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `created_id` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logger_phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redeem_api_in_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` varchar(36) NOT NULL,
  `url` varchar(255) NOT NULL,
  `request` text,
  `method` varchar(45) NOT NULL,
  `ip` varchar(64) NOT NULL,
  `response_status_code` char(3) DEFAULT NULL,
  `response_datetime` datetime DEFAULT NULL,
  `response` text,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_idx` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redeem_api_out_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` varchar(36) NOT NULL,
  `url` varchar(255) NOT NULL,
  `request` text,
  `method` varchar(45) NOT NULL,
  `ip` varchar(64) NOT NULL,
  `response_status_code` char(3) DEFAULT NULL,
  `response_datetime` datetime DEFAULT NULL,
  `response` text,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_idx` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_api_id` int(11) NOT NULL,
  `order_ref_id` varchar(100) DEFAULT NULL,
  `data` mediumtext,
  `tags` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_api_idx` (`supplier_api_id`),
  KEY `order_ref_id` (`order_ref_id`),
  KEY `created_idx` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

