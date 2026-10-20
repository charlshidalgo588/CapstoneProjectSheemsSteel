-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: prac_crud
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `CategoryID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`CategoryID`),
  UNIQUE KEY `categories_categoryname_unique` (`CategoryName`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Roofing Sheets','Metal roofing sheets including long span, corrugated, and rib type profiles.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(2,'Roof Accessories','Ridge rolls, flashings, gutters, valleys, and trims.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(3,'Fasteners','Tek screws, rivets, washers, and roofing nails.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(4,'Insulation','Roof insulation materials such as foil and fiberglass.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(5,'Polycarbonate Panels','Transparent and translucent roofing panels for skylights and canopies.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(6,'Structural Steel','Purlins, C-channels, Z-purlins, and support framing materials.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(7,'Sealants and Adhesives','Roof sealants, silicone, and waterproofing compounds.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(8,'Ceiling Materials','Ceiling boards, metal furring, and ceiling accessories.','2026-06-11 19:40:57','2026-06-11 19:40:57');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `CustomerID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `CustomerCode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `customers_customercode_unique` (`CustomerCode`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'tgfd','2026-06-11 11:44:07','2026-06-11 11:44:07'),(2,'Walk-in 6/12 0428','2026-06-11 12:29:02','2026-06-11 12:29:02'),(3,'Walk-in 6/15 0116','2026-06-14 09:16:44','2026-06-14 09:16:44'),(4,'Walk-in 6/27 2057','2026-06-27 04:57:43','2026-06-27 04:57:43'),(5,'Walk-in 6/27 2100','2026-06-27 05:00:11','2026-06-27 05:00:11'),(6,'Walk-in 6/27 2101','2026-06-27 05:01:10','2026-06-27 05:01:10'),(7,'Walk-in 6/27 2102','2026-06-27 05:02:26','2026-06-27 05:02:26'),(8,'Walk-in 6/29 0119','2026-06-28 09:19:59','2026-06-28 09:19:59'),(9,'Walk-in 6/30 1548','2026-06-29 23:48:24','2026-06-29 23:48:24'),(10,'Walk-in 7/1 1411','2026-06-30 22:11:43','2026-06-30 22:11:43'),(11,'Walk-in 7/1 1412','2026-06-30 22:12:11','2026-06-30 22:12:11'),(12,'Walk-in 7/1 1426','2026-06-30 22:26:43','2026-06-30 22:26:43'),(13,'Walk-in 7/1 1427','2026-06-30 22:27:35','2026-06-30 22:27:35'),(14,'Walk-in 7/1 1435','2026-06-30 22:35:27','2026-06-30 22:35:27'),(15,'Walk-in 7/1 1436','2026-06-30 22:36:18','2026-06-30 22:36:18'),(16,'Walk-in 7/28 1900','2026-07-28 03:02:37','2026-07-28 03:02:37'),(17,'Walk-in 7/28 1922','2026-07-28 03:22:40','2026-07-28 03:22:40'),(18,'Walk-in 7/28 1928','2026-07-28 03:29:52','2026-07-28 03:29:52'),(19,'Walk-in 7/29 0457','2026-07-28 12:57:44','2026-07-28 12:57:44'),(20,'Walk-in 7/29 0459','2026-07-28 12:59:50','2026-07-28 12:59:50'),(21,'Walk-in 7/29 0508','2026-07-28 13:08:16','2026-07-28 13:08:16'),(22,'Walk-in 7/29 0523','2026-07-28 21:23:09','2026-07-28 21:23:09'),(23,'Walk-in 7/29 0541','2026-07-28 21:41:56','2026-07-28 21:41:56'),(24,'Walk-in 7/29 0625','2026-07-28 22:25:09','2026-07-28 22:25:09'),(25,'Walk-in 7/29 0645','2026-07-28 22:45:27','2026-07-28 22:45:27'),(26,'Walk-in 7/29 0655','2026-07-28 22:55:32','2026-07-28 22:55:32'),(27,'Walk-in 8/2 0429','2026-08-01 20:29:22','2026-08-01 20:29:22'),(28,'Walk-in 8/3 1645','2026-08-03 08:45:52','2026-08-03 08:45:52'),(29,'Walk-in 8/4 0814','2026-08-04 00:15:04','2026-08-04 00:15:04'),(30,'Walk-in 8/6 1726','2026-08-06 09:26:26','2026-08-06 09:26:26'),(31,'Walk-in 8/6 1813','2026-08-06 10:13:58','2026-08-06 10:13:58'),(32,'Walk-in 8/12 1130','2026-08-12 03:30:40','2026-08-12 03:30:40'),(33,'Walk-in 8/13 0138','2026-08-12 17:38:32','2026-08-12 17:38:32'),(34,'Walk-in 8/13 0144','2026-08-12 17:44:30','2026-08-12 17:44:30'),(35,'Walk-in 8/13 0145','2026-08-12 17:45:16','2026-08-12 17:45:16'),(36,'Walk-in 8/13 0149','2026-08-12 17:49:10','2026-08-12 17:49:10'),(37,'Walk-in 8/13 0200','2026-08-12 18:00:47','2026-08-12 18:00:47'),(38,'Walk-in 8/13 0203','2026-08-12 18:03:25','2026-08-12 18:03:25'),(39,'Walk-in 8/13 0204','2026-08-12 18:04:29','2026-08-12 18:04:29'),(40,'Walk-in 8/14 1118','2026-08-14 03:18:59','2026-08-14 03:18:59'),(41,'Walk-in 8/14 1119','2026-08-14 03:19:27','2026-08-14 03:19:27'),(42,'Walk-in 8/14 1127','2026-08-14 03:27:29','2026-08-14 03:27:29'),(43,'Walk-in 8/14 1133','2026-08-14 03:33:42','2026-08-14 03:33:42'),(44,'Walk-in 8/14 1134','2026-08-14 03:34:25','2026-08-14 03:34:25'),(45,'Walk-in 8/14 1142','2026-08-14 03:42:56','2026-08-14 03:42:56'),(46,'Walk-in 8/14 1146','2026-08-14 03:46:13','2026-08-14 03:46:13'),(47,'Walk-in 8/18 0010','2026-08-17 16:10:29','2026-08-17 16:10:29'),(48,'Walk-in 8/18 0011','2026-08-17 16:11:38','2026-08-17 16:11:38'),(49,'Walk-in 8/25 0044','2026-08-24 16:44:32','2026-08-24 16:44:32'),(50,'Walk-in 8/25 0045','2026-08-24 16:45:33','2026-08-24 16:45:33'),(51,'Walk-in 8/25 0056','2026-08-24 16:57:00','2026-08-24 16:57:00'),(52,'Walk-in 8/25 0057','2026-08-24 16:57:30','2026-08-24 16:57:30'),(53,'Walk-in 8/26 1838','2026-08-26 10:38:36','2026-08-26 10:38:36'),(54,'Walk-in 8/26 1922','2026-08-26 11:22:06','2026-08-26 11:22:06'),(55,'Walk-in 8/26 1952','2026-08-26 11:52:21','2026-08-26 11:52:21'),(56,'Walk-in 8/26 1953','2026-08-26 11:53:06','2026-08-26 11:53:06'),(57,'Walk-in 8/28 1716','2026-08-28 09:16:37','2026-08-28 09:16:37'),(58,'Walk-in 8/29 2113','2026-08-29 13:13:12','2026-08-29 13:13:12'),(59,'Walk-in 8/29 2214','2026-08-29 14:14:39','2026-08-29 14:14:39'),(60,'Walk-in 8/30 1707','2026-08-30 09:07:10','2026-08-30 09:07:10'),(61,'Walk-in 8/30 1724','2026-08-30 09:24:07','2026-08-30 09:24:07'),(62,'Walk-in 9/2 0122','2026-09-01 17:22:39','2026-09-01 17:22:39'),(63,'jay','2026-09-02 07:18:03','2026-09-02 07:18:03'),(64,'Walk-in 9/3 0527','2026-09-02 21:28:04','2026-09-02 21:28:04'),(65,'Walk-in 9/17 1819','2026-09-17 10:19:22','2026-09-17 10:19:22');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventories`
--

DROP TABLE IF EXISTS `inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventories` (
  `InventoryID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ProductID` bigint unsigned NOT NULL,
  `QuantityOnHand` int NOT NULL DEFAULT '0',
  `ReorderLevel` int NOT NULL DEFAULT '0',
  `LastUpdated` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`InventoryID`),
  KEY `inventories_productid_foreign` (`ProductID`),
  CONSTRAINT `inventories_productid_foreign` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventories`
--

LOCK TABLES `inventories` WRITE;
/*!40000 ALTER TABLE `inventories` DISABLE KEYS */;
INSERT INTO `inventories` VALUES (1,1001,112,20,'2026-06-11 20:03:48','2026-06-11 20:03:48','2026-08-29 14:14:39'),(2,1002,191,30,'2026-06-11 20:03:48','2026-06-11 20:03:48','2026-08-04 00:15:05'),(3,1003,70,10,'2026-06-11 20:03:48','2026-06-11 20:03:48','2026-08-14 03:33:43'),(4,1004,141,30,'2026-06-23 22:18:23','2026-06-11 20:03:48','2026-08-14 03:34:26'),(5,1005,17,5,'2026-06-29 11:30:50','2026-06-11 20:03:48','2026-08-14 03:33:43'),(11,1015,9,10,'2026-08-17 06:25:18','2026-08-06 08:58:03','2026-09-02 07:18:03'),(12,1014,38,10,'2026-08-17 15:58:09','2026-08-06 09:01:57','2026-09-01 17:22:39'),(13,1006,117,20,'2026-08-06 09:14:47','2026-08-06 09:14:47','2026-08-24 16:46:03'),(14,1007,73,15,'2026-08-06 09:14:47','2026-08-06 09:14:47','2026-08-14 03:46:13'),(15,1008,52,10,'2026-08-06 09:14:47','2026-08-06 09:14:47','2026-08-24 16:57:31'),(16,1009,67,10,'2026-08-06 09:14:47','2026-08-06 09:14:47','2026-08-26 11:53:06'),(17,1010,197,40,'2026-08-06 09:14:47','2026-08-06 09:14:47','2026-08-29 13:13:13'),(18,1011,97,20,'2026-08-06 09:14:47','2026-08-06 09:14:47','2026-08-12 17:49:10'),(19,1012,176,40,'2026-08-17 16:00:22','2026-08-06 09:14:47','2026-08-29 13:13:13'),(20,1013,41,10,'2026-08-17 15:59:20','2026-08-06 09:14:47','2026-08-29 14:14:39'),(29,1017,6,10,'2026-08-19 08:28:55','2026-08-14 03:36:39','2026-08-24 16:44:33'),(30,1018,0,10,'2026-08-19 08:28:38','2026-08-17 16:09:51','2026-09-02 21:28:05'),(31,1019,23,10,'2026-08-29 15:03:08','2026-08-29 15:03:08','2026-08-29 15:03:08'),(32,1020,53,25,'2026-09-01 18:19:31','2026-08-29 15:15:22','2026-09-01 18:19:31'),(33,1022,10,5,'2026-08-29 15:19:09','2026-08-29 15:19:09','2026-08-29 15:19:09'),(34,1023,122,50,'2026-08-29 15:21:45','2026-08-29 15:21:45','2026-08-29 15:21:45'),(35,1024,8,12,'2026-09-17 10:10:57','2026-09-17 10:10:57','2026-09-17 10:19:22'),(36,1025,6,21,'2026-09-17 10:14:37','2026-09-17 10:14:37','2026-09-17 10:19:22');
/*!40000 ALTER TABLE `inventories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_logs`
--

DROP TABLE IF EXISTS `inventory_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_logs` (
  `LogID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ProductID` bigint unsigned NOT NULL,
  `type` enum('stock_in','stock_out','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`LogID`),
  KEY `inventory_logs_productid_foreign` (`ProductID`),
  KEY `inventory_logs_created_by_foreign` (`created_by`),
  CONSTRAINT `inventory_logs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `inventory_logs_productid_foreign` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=175 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_logs`
--

LOCK TABLES `inventory_logs` WRITE;
/*!40000 ALTER TABLE `inventory_logs` DISABLE KEYS */;
INSERT INTO `inventory_logs` VALUES (1,1005,'stock_out',1,'POS sale #1',1,'2026-06-11 11:44:07','2026-06-11 11:44:07'),(2,1005,'stock_in',1,'Rollback of sale #1',1,'2026-06-11 11:48:19','2026-06-11 11:48:19'),(3,1004,'stock_out',1,'POS sale #2',1,'2026-06-11 12:29:02','2026-06-11 12:29:02'),(4,1003,'stock_out',1,'POS sale #2',1,'2026-06-11 12:29:02','2026-06-11 12:29:02'),(5,1003,'stock_out',4,'POS sale #3',1,'2026-06-14 09:16:44','2026-06-14 09:16:44'),(6,1002,'stock_out',2,'POS sale #3',1,'2026-06-14 09:16:44','2026-06-14 09:16:44'),(7,1005,'stock_in',1,'Manual restock',1,'2026-06-14 09:41:10','2026-06-14 09:41:10'),(8,1005,'stock_out',1,'POS sale #4',1,'2026-06-27 04:57:43','2026-06-27 04:57:43'),(9,1004,'stock_out',1,'POS sale #5',1,'2026-06-27 05:00:11','2026-06-27 05:00:11'),(10,1002,'stock_out',1,'POS sale #6',1,'2026-06-27 05:01:10','2026-06-27 05:01:10'),(11,1001,'stock_out',1,'POS sale #7',1,'2026-06-27 05:01:40','2026-06-27 05:01:40'),(12,1001,'stock_out',1,'POS sale #8',1,'2026-06-27 05:02:26','2026-06-27 05:02:26'),(13,1004,'stock_out',1,'POS sale #9',1,'2026-06-27 05:02:58','2026-06-27 05:02:58'),(14,1004,'stock_out',1,'POS sale #10',1,'2026-06-28 09:19:59','2026-06-28 09:19:59'),(15,1004,'stock_in',1,'Rollback of sale #10',1,'2026-06-29 05:24:07','2026-06-29 05:24:07'),(16,1004,'stock_in',1,'Rollback of sale #9',1,'2026-06-29 23:43:21','2026-06-29 23:43:21'),(17,1001,'stock_in',1,'Rollback of sale #8',1,'2026-06-29 23:43:59','2026-06-29 23:43:59'),(18,1001,'stock_in',1,'Rollback of sale #7',1,'2026-06-29 23:47:48','2026-06-29 23:47:48'),(19,1004,'stock_out',1,'POS sale #11',1,'2026-06-29 23:48:24','2026-06-29 23:48:24'),(20,1004,'stock_in',1,'Rollback of sale #11',1,'2026-06-29 23:49:00','2026-06-29 23:49:00'),(21,1002,'stock_in',1,'Rollback of sale #6',1,'2026-06-30 22:09:32','2026-06-30 22:09:32'),(22,1004,'stock_in',1,'Rollback of sale #5',1,'2026-06-30 22:10:21','2026-06-30 22:10:21'),(23,1005,'stock_out',1,'POS sale #12',1,'2026-06-30 22:11:43','2026-06-30 22:11:43'),(24,1003,'stock_out',1,'POS sale #12',1,'2026-06-30 22:11:43','2026-06-30 22:11:43'),(25,1002,'stock_out',1,'POS sale #13',1,'2026-06-30 22:12:11','2026-06-30 22:12:11'),(26,1001,'stock_out',1,'POS sale #13',1,'2026-06-30 22:12:11','2026-06-30 22:12:11'),(27,1005,'stock_in',1,'Rollback of sale #12',1,'2026-06-30 22:12:55','2026-06-30 22:12:55'),(28,1003,'stock_in',1,'Rollback of sale #12',1,'2026-06-30 22:12:55','2026-06-30 22:12:55'),(29,1002,'stock_in',1,'Rollback of sale #13',1,'2026-06-30 22:17:57','2026-06-30 22:17:57'),(30,1001,'stock_in',1,'Rollback of sale #13',1,'2026-06-30 22:17:57','2026-06-30 22:17:57'),(31,1005,'stock_in',1,'Rollback of sale #4',1,'2026-06-30 22:19:05','2026-06-30 22:19:05'),(32,1004,'stock_in',1,'Rollback of sale #2',1,'2026-06-30 22:19:25','2026-06-30 22:19:25'),(33,1003,'stock_in',1,'Rollback of sale #2',1,'2026-06-30 22:19:25','2026-06-30 22:19:25'),(34,1003,'stock_in',4,'Rollback of sale #3',1,'2026-06-30 22:19:25','2026-06-30 22:19:25'),(35,1002,'stock_in',2,'Rollback of sale #3',1,'2026-06-30 22:19:25','2026-06-30 22:19:25'),(36,1005,'stock_out',1,'POS sale #14',1,'2026-06-30 22:26:43','2026-06-30 22:26:43'),(37,1004,'stock_out',1,'POS sale #14',1,'2026-06-30 22:26:44','2026-06-30 22:26:44'),(38,1002,'stock_out',1,'POS sale #15',1,'2026-06-30 22:27:02','2026-06-30 22:27:02'),(39,1001,'stock_out',1,'POS sale #15',1,'2026-06-30 22:27:03','2026-06-30 22:27:03'),(40,1005,'stock_out',1,'POS sale #16',1,'2026-06-30 22:27:35','2026-06-30 22:27:35'),(41,1001,'stock_out',1,'POS sale #16',1,'2026-06-30 22:27:35','2026-06-30 22:27:35'),(42,1002,'stock_in',1,'Rollback of sale #15',1,'2026-06-30 22:34:02','2026-06-30 22:34:02'),(43,1001,'stock_in',1,'Rollback of sale #15',1,'2026-06-30 22:34:02','2026-06-30 22:34:02'),(44,1005,'stock_in',1,'Rollback of sale #16',1,'2026-06-30 22:34:02','2026-06-30 22:34:02'),(45,1001,'stock_in',1,'Rollback of sale #16',1,'2026-06-30 22:34:02','2026-06-30 22:34:02'),(46,1005,'stock_in',1,'Rollback of sale #14',1,'2026-06-30 22:34:30','2026-06-30 22:34:30'),(47,1004,'stock_in',1,'Rollback of sale #14',1,'2026-06-30 22:34:30','2026-06-30 22:34:30'),(48,1004,'stock_out',1,'POS sale #17',1,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(49,1001,'stock_out',1,'POS sale #17',1,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(50,1005,'stock_out',1,'POS sale #17',1,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(51,1002,'stock_out',1,'POS sale #17',1,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(52,1002,'stock_out',2,'POS sale #18',1,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(53,1003,'stock_out',2,'POS sale #18',1,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(54,1001,'stock_out',1,'POS sale #18',1,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(55,1004,'stock_out',2,'POS sale #18',1,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(56,1005,'stock_out',1,'POS sale #19',1,'2026-06-30 22:36:18','2026-06-30 22:36:18'),(57,1004,'stock_out',1,'POS sale #19',1,'2026-06-30 22:36:18','2026-06-30 22:36:18'),(58,1001,'stock_out',1,'POS sale #20',1,'2026-07-28 03:02:38','2026-07-28 03:02:38'),(59,1004,'stock_out',1,'POS sale #21',1,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(60,1002,'stock_out',1,'POS sale #21',1,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(61,1003,'stock_out',1,'POS sale #21',1,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(62,1002,'stock_out',1,'POS sale #22',1,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(63,1003,'stock_out',1,'POS sale #22',1,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(64,1001,'stock_out',1,'POS sale #22',1,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(65,1003,'stock_out',1,'POS sale #23',1,'2026-07-28 12:57:45','2026-07-28 12:57:45'),(66,1005,'stock_out',1,'POS sale #24',1,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(67,1002,'stock_out',1,'POS sale #24',1,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(68,1004,'stock_out',1,'POS sale #24',1,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(69,1005,'stock_out',1,'POS sale #25',1,'2026-07-28 13:08:16','2026-07-28 13:08:16'),(70,1002,'stock_out',1,'POS sale #26',1,'2026-07-28 21:23:09','2026-07-28 21:23:09'),(71,1001,'stock_out',1,'POS sale #27',1,'2026-07-28 21:41:56','2026-07-28 21:41:56'),(72,1003,'stock_out',1,'POS sale #28',1,'2026-07-28 22:25:10','2026-07-28 22:25:10'),(73,1004,'stock_out',1,'POS sale #29',1,'2026-07-28 22:45:27','2026-07-28 22:45:27'),(74,1003,'stock_out',1,'POS sale #30',1,'2026-07-28 22:55:32','2026-07-28 22:55:32'),(75,1003,'stock_out',1,'POS sale #31',1,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(76,1004,'stock_out',1,'POS sale #31',1,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(77,1002,'stock_out',1,'POS sale #31',1,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(78,1005,'stock_out',1,'POS sale #31',1,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(79,1001,'stock_out',1,'POS sale #31',1,'2026-08-01 20:29:24','2026-08-01 20:29:24'),(80,1005,'stock_out',1,'POS sale #32',1,'2026-08-01 20:30:05','2026-08-01 20:30:05'),(81,1003,'stock_out',1,'POS sale #33',1,'2026-08-03 08:45:53','2026-08-03 08:45:53'),(82,1005,'stock_out',1,'POS sale #34',1,'2026-08-04 00:15:05','2026-08-04 00:15:05'),(83,1002,'stock_out',1,'POS sale #34',1,'2026-08-04 00:15:05','2026-08-04 00:15:05'),(84,1014,'stock_in',38,'Manual restock',1,'2026-08-06 09:16:39','2026-08-06 09:16:39'),(85,1012,'stock_out',1,'POS sale #35',1,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(86,1013,'stock_out',1,'POS sale #35',1,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(87,1009,'stock_out',1,'POS sale #35',1,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(88,1013,'stock_out',1,'POS sale #36',1,'2026-08-06 10:13:59','2026-08-06 10:13:59'),(89,1015,'stock_out',1,'POS sale #37',1,'2026-08-12 03:30:41','2026-08-12 03:30:41'),(90,1009,'stock_out',1,'POS sale #37',1,'2026-08-12 03:30:41','2026-08-12 03:30:41'),(91,1007,'stock_out',2,'POS sale #37',1,'2026-08-12 03:30:41','2026-08-12 03:30:41'),(92,1008,'stock_out',1,'POS sale #38',1,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(93,1007,'stock_out',1,'POS sale #38',1,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(94,1006,'stock_out',1,'POS sale #38',1,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(95,1005,'stock_out',1,'POS sale #38',1,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(96,1010,'stock_out',1,'POS sale #38',1,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(97,1015,'stock_out',1,'POS sale #38',1,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(98,1011,'stock_out',1,'POS sale #39',1,'2026-08-12 17:44:30','2026-08-12 17:44:30'),(99,1007,'stock_out',1,'POS sale #39',1,'2026-08-12 17:44:30','2026-08-12 17:44:30'),(100,1011,'stock_out',1,'POS sale #40',1,'2026-08-12 17:45:16','2026-08-12 17:45:16'),(101,1007,'stock_out',1,'POS sale #40',1,'2026-08-12 17:45:16','2026-08-12 17:45:16'),(102,1011,'stock_out',1,'POS sale #41',1,'2026-08-12 17:49:10','2026-08-12 17:49:10'),(103,1015,'stock_out',1,'POS sale #41',1,'2026-08-12 17:49:11','2026-08-12 17:49:11'),(104,1009,'stock_out',1,'POS sale #41',1,'2026-08-12 17:49:11','2026-08-12 17:49:11'),(105,1012,'stock_out',1,'POS sale #42',1,'2026-08-12 18:00:47','2026-08-12 18:00:47'),(106,1008,'stock_out',1,'POS sale #42',1,'2026-08-12 18:00:47','2026-08-12 18:00:47'),(107,1014,'stock_out',1,'POS sale #43',1,'2026-08-12 18:03:25','2026-08-12 18:03:25'),(108,1008,'stock_out',1,'POS sale #43',1,'2026-08-12 18:03:25','2026-08-12 18:03:25'),(109,1015,'stock_out',1,'POS sale #44',1,'2026-08-12 18:04:29','2026-08-12 18:04:29'),(110,1006,'stock_out',1,'POS sale #44',1,'2026-08-12 18:04:29','2026-08-12 18:04:29'),(111,1015,'stock_out',1,'POS sale #45',1,'2026-08-14 03:19:00','2026-08-14 03:19:00'),(112,1014,'stock_out',1,'POS sale #45',1,'2026-08-14 03:19:00','2026-08-14 03:19:00'),(113,1015,'stock_out',1,'POS sale #46',1,'2026-08-14 03:19:27','2026-08-14 03:19:27'),(114,1008,'stock_out',1,'POS sale #46',1,'2026-08-14 03:19:27','2026-08-14 03:19:27'),(115,1013,'stock_out',1,'POS sale #47',1,'2026-08-14 03:27:30','2026-08-14 03:27:30'),(116,1012,'stock_out',1,'POS sale #47',1,'2026-08-14 03:27:30','2026-08-14 03:27:30'),(117,1013,'stock_out',1,'POS sale #48',1,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(118,1008,'stock_out',1,'POS sale #48',1,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(119,1005,'stock_out',1,'POS sale #48',1,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(120,1003,'stock_out',1,'POS sale #48',1,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(121,1001,'stock_out',1,'POS sale #48',1,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(122,1014,'stock_out',1,'POS sale #48',1,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(123,1015,'stock_out',1,'POS sale #49',1,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(124,1008,'stock_out',1,'POS sale #49',1,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(125,1007,'stock_out',1,'POS sale #49',1,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(126,1009,'stock_out',1,'POS sale #49',1,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(127,1010,'stock_out',1,'POS sale #49',1,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(128,1004,'stock_out',1,'POS sale #49',1,'2026-08-14 03:34:26','2026-08-14 03:34:26'),(129,1017,'stock_out',1,'POS sale #50',1,'2026-08-14 03:42:57','2026-08-14 03:42:57'),(130,1008,'stock_out',1,'POS sale #51',1,'2026-08-14 03:46:13','2026-08-14 03:46:13'),(131,1007,'stock_out',1,'POS sale #51',1,'2026-08-14 03:46:13','2026-08-14 03:46:13'),(132,1014,'stock_out',1,'POS sale #52',1,'2026-08-17 16:10:30','2026-08-17 16:10:30'),(133,1018,'stock_out',1,'POS sale #53',1,'2026-08-17 16:10:49','2026-08-17 16:10:49'),(134,1015,'stock_out',1,'POS sale #54',1,'2026-08-17 16:11:38','2026-08-17 16:11:38'),(135,1018,'stock_in',10,'Manual restock',1,'2026-08-19 08:28:38','2026-08-19 08:28:38'),(136,1017,'stock_in',8,'Manual restock',1,'2026-08-19 08:28:55','2026-08-19 08:28:55'),(137,1018,'stock_out',12,'POS sale #55',1,'2026-08-24 16:44:33','2026-08-24 16:44:33'),(138,1017,'stock_out',11,'POS sale #55',1,'2026-08-24 16:44:33','2026-08-24 16:44:33'),(139,1014,'stock_out',1,'POS sale #56',1,'2026-08-24 16:45:33','2026-08-24 16:45:33'),(140,1006,'stock_out',1,'POS sale #57',1,'2026-08-24 16:46:03','2026-08-24 16:46:03'),(141,1013,'stock_out',1,'POS sale #58',1,'2026-08-24 16:57:00','2026-08-24 16:57:00'),(142,1009,'stock_out',2,'POS sale #59',1,'2026-08-24 16:57:30','2026-08-24 16:57:30'),(143,1008,'stock_out',1,'POS sale #59',1,'2026-08-24 16:57:31','2026-08-24 16:57:31'),(144,1015,'stock_out',1,'POS sale #60',1,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(145,1014,'stock_out',1,'POS sale #60',1,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(146,1013,'stock_out',1,'POS sale #60',1,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(147,1014,'stock_out',1,'POS sale #61',1,'2026-08-26 11:22:07','2026-08-26 11:22:07'),(148,1009,'stock_out',1,'POS sale #61',1,'2026-08-26 11:22:07','2026-08-26 11:22:07'),(149,1014,'stock_out',1,'POS sale #62',1,'2026-08-26 11:52:21','2026-08-26 11:52:21'),(150,1013,'stock_out',1,'POS sale #63',1,'2026-08-26 11:53:06','2026-08-26 11:53:06'),(151,1009,'stock_out',1,'POS sale #63',1,'2026-08-26 11:53:06','2026-08-26 11:53:06'),(152,1015,'stock_out',1,'POS sale #64',1,'2026-08-28 09:16:38','2026-08-28 09:16:38'),(153,1014,'stock_out',1,'POS sale #65',1,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(154,1013,'stock_out',1,'POS sale #65',1,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(155,1010,'stock_out',1,'POS sale #65',1,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(156,1012,'stock_out',1,'POS sale #65',1,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(157,1013,'stock_out',1,'POS sale #66',1,'2026-08-29 14:14:39','2026-08-29 14:14:39'),(158,1001,'stock_out',1,'POS sale #66',1,'2026-08-29 14:14:39','2026-08-29 14:14:39'),(159,1020,'stock_in',50,'New product added',1,'2026-08-29 15:15:22','2026-08-29 15:15:22'),(160,1022,'stock_in',10,'New product added',1,'2026-08-29 15:19:09','2026-08-29 15:19:09'),(161,1023,'stock_in',122,'New product added',1,'2026-08-29 15:21:45','2026-08-29 15:21:45'),(162,1018,'stock_out',1,'POS sale #67',1,'2026-08-30 09:07:10','2026-08-30 09:07:10'),(163,1020,'stock_out',10,'POS sale #68',1,'2026-08-30 09:24:07','2026-08-30 09:24:07'),(164,1015,'stock_out',1,'POS sale #68',1,'2026-08-30 09:24:07','2026-08-30 09:24:07'),(165,1020,'stock_in',10,'Rollback of sale #68',3,'2026-09-01 06:09:45','2026-09-01 06:09:45'),(166,1015,'stock_in',1,'Rollback of sale #68',3,'2026-09-01 06:09:45','2026-09-01 06:09:45'),(167,1014,'stock_out',1,'POS sale #69',4,'2026-09-01 17:22:40','2026-09-01 17:22:40'),(168,1020,'stock_in',3,'Manual restock',4,'2026-09-01 18:19:31','2026-09-01 18:19:31'),(169,1015,'stock_out',1,'POS sale #70',1,'2026-09-02 07:18:03','2026-09-02 07:18:03'),(170,1018,'stock_out',6,'POS sale #71',1,'2026-09-02 21:28:05','2026-09-02 21:28:05'),(171,1024,'stock_in',21,'New product added',1,'2026-09-17 10:10:57','2026-09-17 10:10:57'),(172,1025,'stock_in',12,'New product added',1,'2026-09-17 10:14:37','2026-09-17 10:14:37'),(173,1024,'stock_out',13,'POS sale #72',1,'2026-09-17 10:19:22','2026-09-17 10:19:22'),(174,1025,'stock_out',6,'POS sale #72',1,'2026-09-17 10:19:22','2026-09-17 10:19:22');
/*!40000 ALTER TABLE `inventory_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2024_03_19_000000_create_users_table',1),(4,'2024_03_19_000001_create_categories_table',1),(5,'2024_03_19_000002_create_suppliers_table',1),(6,'2024_03_19_000003_create_products_table',1),(7,'2024_03_19_000004_create_customers_table',1),(8,'2024_03_19_000005_create_sales_table',1),(9,'2024_03_19_000006_create_sales_items_table',1),(10,'2024_03_19_000007_create_inventories_table',1),(11,'2024_03_19_000008_create_inventory_logs_table',1),(12,'2024_03_19_000009_create_transactions_table',1),(13,'2024_03_19_000010_create_product_suppliers_table',1),(15,'2024_03_20_000001_create_purchase_records_table',1),(16,'2025_12_06_160903_add_description_to_categories_table',1),(17,'2025_12_14_133736_add_specifications_to_products_table',2),(18,'2025_12_15_104219_add_length_width_unit_to_products_table',2),(19,'2026_07_27_154532_add_barcode_to_products_table',3),(20,'2026_07_29_055949_create_notifications_table',4),(21,'2026_07_29_063748_change_sale_date_to_datetime_on_sales_table',5),(23,'2026_09_01_133234_add_role_and_status_to_users_table',6),(24,'2019_12_14_000001_create_personal_access_tokens_table',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notifications_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'sale','New sale completed','Order #31 processed for ₱6,400.00.','App\\Models\\Sale',31,NULL,'2026-08-03 08:41:35','2026-08-01 20:29:23','2026-08-03 08:41:35'),(2,'sale','New sale completed','Order #32 processed for ₱2,800.00.','App\\Models\\Sale',32,NULL,'2026-08-03 08:41:35','2026-08-01 20:30:05','2026-08-03 08:41:35'),(3,'sale','New sale completed','Order #34 processed for ₱3,750.00.','App\\Models\\Sale',34,NULL,'2026-08-12 03:00:12','2026-08-04 00:15:04','2026-08-12 03:00:12'),(4,'low_stock','Low stock alert','Roof Ventilator has only 10 units left.','App\\Models\\Inventory',12,NULL,'2026-08-12 03:00:12','2026-08-06 09:01:57','2026-08-12 03:00:12'),(5,'sale','New sale completed','Order #37 processed for ₱3,580.00.','App\\Models\\Sale',37,NULL,'2026-08-12 18:02:54','2026-08-12 03:30:40','2026-08-12 18:02:54'),(6,'sale','New sale completed','Order #38 processed for ₱6,325.00.','App\\Models\\Sale',38,NULL,'2026-08-12 18:02:54','2026-08-12 17:38:32','2026-08-12 18:02:54'),(7,'sale','New sale completed','Order #43 processed for ₱3,900.00.','App\\Models\\Sale',43,NULL,'2026-08-12 18:10:57','2026-08-12 18:03:25','2026-08-12 18:10:57'),(8,'sale','New sale completed','Order #45 processed for ₱3,510.00.','App\\Models\\Sale',45,NULL,'2026-08-14 03:27:54','2026-08-14 03:19:00','2026-08-14 03:27:54'),(9,'sale','New sale completed','Order #48 processed for ₱9,780.00.','App\\Models\\Sale',48,NULL,'2026-08-17 16:12:13','2026-08-14 03:33:43','2026-08-17 16:12:13'),(10,'sale','New sale completed','Order #49 processed for ₱3,315.00.','App\\Models\\Sale',49,NULL,'2026-08-17 16:12:13','2026-08-14 03:34:25','2026-08-17 16:12:13'),(11,'low_stock','Low stock alert','rew has only 9 units left.','App\\Models\\Inventory',29,NULL,'2026-08-17 16:12:13','2026-08-14 03:42:57','2026-08-17 16:12:13'),(12,'sale','New sale completed','Order #51 processed for ₱2,100.00.','App\\Models\\Sale',51,NULL,'2026-08-17 16:12:13','2026-08-14 03:46:13','2026-08-17 16:12:13'),(13,'sale','New sale completed','Order #52 processed for ₱3,250.00.','App\\Models\\Sale',52,NULL,'2026-08-17 16:12:13','2026-08-17 16:10:30','2026-08-17 16:12:13'),(14,'low_stock','Low stock alert','sample has only 9 units left.','App\\Models\\Inventory',30,NULL,'2026-08-17 16:12:13','2026-08-17 16:10:49','2026-08-17 16:12:13'),(15,'low_stock','Low stock alert','sample has only 7 units left.','App\\Models\\Inventory',30,NULL,'2026-08-29 14:02:02','2026-08-24 16:44:33','2026-08-29 14:02:02'),(16,'low_stock','Low stock alert','rew has only 6 units left.','App\\Models\\Inventory',29,NULL,'2026-08-29 14:02:02','2026-08-24 16:44:33','2026-08-29 14:02:02'),(17,'sale','New sale completed','Order #56 processed for ₱3,250.00.','App\\Models\\Sale',56,NULL,'2026-08-29 14:02:02','2026-08-24 16:45:33','2026-08-29 14:02:02'),(18,'sale','New sale','Sale #58 for Walk-in 8/25 0056 — ₱780.00','App\\Models\\Sale',58,NULL,'2026-08-29 14:02:02','2026-08-24 16:57:00','2026-08-29 14:02:02'),(19,'sale','New sale','Sale #59 for Walk-in 8/25 0057 — ₱1,490.00','App\\Models\\Sale',59,NULL,'2026-08-29 14:02:02','2026-08-24 16:57:31','2026-08-29 14:02:02'),(20,'sale','New sale completed','Order #60 processed for ₱4,290.00.','App\\Models\\Sale',60,NULL,'2026-08-29 14:02:02','2026-08-26 10:38:37','2026-08-29 14:02:02'),(21,'sale','New sale','Sale #60 for Walk-in 8/26 1838 — ₱4,290.00','App\\Models\\Sale',60,NULL,'2026-08-29 14:02:02','2026-08-26 10:38:37','2026-08-29 14:02:02'),(22,'sale','New sale completed','Order #61 processed for ₱3,670.00.','App\\Models\\Sale',61,NULL,'2026-08-29 14:02:02','2026-08-26 11:22:07','2026-08-29 14:02:02'),(23,'sale','New sale','Sale #61 for Walk-in 8/26 1922 — ₱3,670.00','App\\Models\\Sale',61,NULL,'2026-08-29 14:02:02','2026-08-26 11:22:07','2026-08-29 14:02:02'),(24,'sale','New sale completed','Order #62 processed for ₱3,250.00.','App\\Models\\Sale',62,NULL,'2026-08-29 14:02:02','2026-08-26 11:52:21','2026-08-29 14:02:02'),(25,'sale','New sale','Sale #62 for Walk-in 8/26 1952 — ₱3,250.00','App\\Models\\Sale',62,NULL,'2026-08-29 14:02:02','2026-08-26 11:52:21','2026-08-29 14:02:02'),(26,'sale','New sale','Sale #63 for Walk-in 8/26 1953 — ₱1,200.00','App\\Models\\Sale',63,NULL,'2026-08-29 14:02:02','2026-08-26 11:53:06','2026-08-29 14:02:02'),(27,'low_stock','Low stock alert','Roofing Rivets has only 10 units left.','App\\Models\\Inventory',11,NULL,'2026-08-29 14:02:02','2026-08-28 09:16:37','2026-08-29 14:02:02'),(28,'sale','New sale','Sale #64 for Walk-in 8/28 1716 — ₱260.00','App\\Models\\Sale',64,NULL,'2026-08-29 14:02:02','2026-08-28 09:16:38','2026-08-29 14:02:02'),(29,'sale','New sale completed','Order #65 processed for ₱4,605.00.','App\\Models\\Sale',65,NULL,'2026-08-29 14:02:02','2026-08-29 13:13:13','2026-08-29 14:02:02'),(30,'sale','New sale','Sale #65 for Walk-in 8/29 2113 — ₱4,605.00','App\\Models\\Sale',65,NULL,'2026-08-29 14:02:02','2026-08-29 13:13:13','2026-08-29 14:02:02'),(31,'sale','New sale completed','Order #66 processed for ₱2,630.00.','App\\Models\\Sale',66,NULL,'2026-08-30 08:58:38','2026-08-29 14:14:39','2026-08-30 08:58:38'),(32,'sale','New sale','Sale #66 for Walk-in 8/29 2214 — ₱2,630.00','App\\Models\\Sale',66,NULL,'2026-08-30 08:58:38','2026-08-29 14:14:39','2026-08-30 08:58:38'),(33,'product_created','New Product Added','\"SAMPLE 4\" was added to inventory with an opening stock of 122.','App\\Models\\Product',1023,NULL,'2026-08-30 08:58:38','2026-08-29 15:21:45','2026-08-30 08:58:38'),(34,'low_stock','Low stock alert','sample has only 6 units left.','App\\Models\\Inventory',30,NULL,'2026-08-30 17:49:54','2026-08-30 09:07:10','2026-08-30 17:49:54'),(35,'sale','New sale','Sale #67 for Walk-in 8/30 1707 — ₱34.00','App\\Models\\Sale',67,NULL,'2026-08-30 17:49:54','2026-08-30 09:07:10','2026-08-30 17:49:54'),(36,'low_stock','Low stock alert','Roofing Rivets has only 9 units left.','App\\Models\\Inventory',11,NULL,'2026-08-30 17:49:54','2026-08-30 09:24:07','2026-08-30 17:49:54'),(37,'sale','New sale','Sale #68 for Walk-in 8/30 1724 — ₱1,480.00','App\\Models\\Sale',68,NULL,'2026-08-30 17:49:54','2026-08-30 09:24:07','2026-08-30 17:49:54'),(38,'low_stock','Low stock alert','Roofing Rivets has only 10 units left.','App\\Models\\Inventory',11,NULL,NULL,'2026-09-01 06:09:45','2026-09-01 06:09:45'),(39,'sale','New sale completed','Order #69 processed for ₱3,250.00.','App\\Models\\Sale',69,NULL,NULL,'2026-09-01 17:22:39','2026-09-01 17:22:39'),(40,'sale','New sale','Sale #69 for Walk-in 9/2 0122 — ₱3,250.00','App\\Models\\Sale',69,NULL,NULL,'2026-09-01 17:22:40','2026-09-01 17:22:40'),(41,'sale','New sale','Sale #70 for jay — ₱260.00','App\\Models\\Sale',70,NULL,NULL,'2026-09-02 07:18:03','2026-09-02 07:18:03'),(42,'out_of_stock','Out of stock','sample is now out of stock.','App\\Models\\Inventory',30,NULL,NULL,'2026-09-02 21:28:05','2026-09-02 21:28:05'),(43,'out_of_stock','Out of stock','sample is now out of stock.','App\\Models\\Product',1018,NULL,NULL,'2026-09-02 21:28:05','2026-09-02 21:28:05'),(44,'sale','New sale','Sale #71 for Walk-in 9/3 0527 — ₱204.00','App\\Models\\Sale',71,NULL,NULL,'2026-09-02 21:28:05','2026-09-02 21:28:05'),(45,'product_created','New Product Added','\"gg\" was added to inventory with an opening stock of 21.','App\\Models\\Product',1024,NULL,NULL,'2026-09-17 10:10:57','2026-09-17 10:10:57'),(46,'product_created','New Product Added','\"book\" was added to inventory with an opening stock of 12.','App\\Models\\Product',1025,NULL,NULL,'2026-09-17 10:14:37','2026-09-17 10:14:37'),(47,'low_stock','Low stock alert','gg has only 8 units left.','App\\Models\\Inventory',35,NULL,NULL,'2026-09-17 10:19:22','2026-09-17 10:19:22'),(48,'low_stock','Low stock alert','book has only 6 units left.','App\\Models\\Inventory',36,NULL,NULL,'2026-09-17 10:19:22','2026-09-17 10:19:22'),(49,'sale','New sale','Sale #72 for Walk-in 9/17 1819 — ₱228.00','App\\Models\\Sale',72,NULL,NULL,'2026-09-17 10:19:22','2026-09-17 10:19:22');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'auth-token','4ddda4f40c61aea2116651b7e863d06cc3608136b3efc0b1827ad811c7649106','[\"*\"]',NULL,'2026-09-24 21:52:11','2026-09-24 09:52:11','2026-09-24 09:52:11'),(2,'App\\Models\\User',1,'auth-token','c5c604a5663112f846208d3348b57825d723afacfc3237119afbec332db6873b','[\"*\"]',NULL,'2026-09-24 21:52:48','2026-09-24 09:52:48','2026-09-24 09:52:48'),(3,'App\\Models\\User',1,'auth-token','35eed46e4bd6526bbe94b6956726dbc219f84658c8e2e5cd25570572380bb946','[\"*\"]',NULL,'2026-09-24 21:59:46','2026-09-24 09:59:46','2026-09-24 09:59:46'),(4,'App\\Models\\User',1,'auth-token','f03056a4a6e9a42a71030563a0b99523428110e14d0a83cbf3ac24e6743b4b30','[\"*\"]',NULL,'2026-09-24 22:07:05','2026-09-24 10:07:05','2026-09-24 10:07:05'),(5,'App\\Models\\User',1,'auth-token','2be478ab51e8a4353e3322f24648e2710099b6786d927382cb8771b087f466e7','[\"*\"]',NULL,'2026-09-25 06:14:07','2026-09-24 18:14:07','2026-09-24 18:14:07'),(6,'App\\Models\\User',1,'auth-token','834e54ffbd32e66c50faef8384366c5de65d650fbe8e6ed1ce4849e51e4310bd','[\"*\"]','2026-09-24 18:36:30','2026-09-25 06:35:02','2026-09-24 18:35:02','2026-09-24 18:36:30');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_suppliers`
--

DROP TABLE IF EXISTS `product_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_suppliers` (
  `ProductSupplierID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ProductID` bigint unsigned NOT NULL,
  `SupplierID` bigint unsigned NOT NULL,
  `PurchasePrice` decimal(10,2) NOT NULL,
  `SupplierProductCode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `LeadTime` int DEFAULT NULL,
  `MinimumOrderQuantity` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ProductSupplierID`),
  UNIQUE KEY `product_suppliers_productid_supplierid_unique` (`ProductID`,`SupplierID`),
  KEY `product_suppliers_supplierid_foreign` (`SupplierID`),
  CONSTRAINT `product_suppliers_productid_foreign` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `product_suppliers_supplierid_foreign` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_suppliers`
--

LOCK TABLES `product_suppliers` WRITE;
/*!40000 ALTER TABLE `product_suppliers` DISABLE KEYS */;
INSERT INTO `product_suppliers` VALUES (4,1017,5,0.00,NULL,NULL,NULL,NULL,NULL),(5,1018,4,0.00,NULL,NULL,NULL,NULL,NULL),(6,1019,4,0.00,NULL,NULL,NULL,NULL,NULL),(7,1020,2,0.00,NULL,NULL,NULL,NULL,NULL),(8,1022,4,0.00,NULL,NULL,NULL,NULL,NULL),(9,1023,4,0.00,NULL,NULL,NULL,NULL,NULL),(10,1025,4,0.00,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `product_suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `ProductID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ProductName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `IsReturnable` tinyint(1) NOT NULL DEFAULT '0',
  `CategoryID` bigint unsigned NOT NULL,
  `SupplierID` bigint unsigned DEFAULT NULL,
  `Brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SKU` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Barcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Description` text COLLATE utf8mb4_unicode_ci,
  `Material` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ProfileType` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Length` decimal(10,2) DEFAULT NULL,
  `LengthUnit` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Width` decimal(10,2) DEFAULT NULL,
  `WidthUnit` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Thickness` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Weight` decimal(10,2) DEFAULT NULL,
  `WeightUnit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SellingPrice` decimal(10,2) NOT NULL,
  `CostPrice` decimal(10,2) NOT NULL,
  `OpeningStock` int DEFAULT NULL,
  `ReorderLevel` int DEFAULT NULL,
  `Product_Image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ProductID`),
  UNIQUE KEY `products_sku_unique` (`SKU`),
  UNIQUE KEY `products_barcode_unique` (`Barcode`),
  KEY `products_categoryid_foreign` (`CategoryID`),
  KEY `products_supplierid_foreign` (`SupplierID`),
  CONSTRAINT `products_categoryid_foreign` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`) ON DELETE CASCADE,
  CONSTRAINT `products_supplierid_foreign` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1026 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1001,'Long Span Roofing Sheet','Sheet',1,1,1,'RoofMaster','RMS-LS-001','9300617065029','Pre-painted roofing sheet','Galvanized Steel','Rib Type','Red',6.00,'m',1.00,'m','0.50',24.50,'kg',1850.00,1450.00,120,20,'longspan_red.jpg','2026-06-11 19:42:58','2026-07-27 10:07:13'),(1002,'Corrugated Roofing Sheet','Sheet',1,1,2,'BuildPro','BPS-CR-002',NULL,'Corrugated roofing sheet','Galvanized Iron','Corrugated','Silver',8.00,'ft',32.00,'in','0.40',18.00,'kg',950.00,720.00,200,30,'corrugated.jpg','2026-06-11 19:42:58','2026-06-11 19:42:58'),(1003,'Roof Ridge Roll','Piece',1,2,1,'RoofMaster','RMS-RR-003',NULL,'Ridge cap for roofing systems','Galvanized Steel','Ridge Roll','Red',2.40,'m',0.45,'m','0.50',5.00,'kg',450.00,320.00,80,10,'ridge_roll.jpg','2026-06-11 19:42:58','2026-06-11 19:42:58'),(1004,'Tek Screw 2in','Box',0,3,4,'FixPro','FPS-TS-004',NULL,'Self-drilling roofing screws','Steel','Fastener','Silver',NULL,NULL,NULL,NULL,NULL,2.50,'kg',350.00,250.00,150,30,'tek_screw.jpg','2026-06-11 19:42:58','2026-06-23 22:18:23'),(1005,'Bubble Foil Insulation','Roll',1,4,5,'ThermoShield','TSI-BF-005',NULL,'Reflective roof insulation','Foil Bubble','Insulation','Silver',30.00,'m',1.20,'m','4.00',8.50,'kg',2800.00,2200.00,25,5,'foil_insulation.jpg','2026-06-11 19:42:58','2026-06-29 11:30:50'),(1006,'C-Purlin 2x4','Piece',1,6,2,'SteelBuild','SB-CP-006',NULL,'Structural C-Purlin for roof framing','Galvanized Steel','C-Purlin','Silver',6.00,'m',100.00,'mm','1.50',16.50,'kg',980.00,760.00,120,20,'c_purlin.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1007,'Z-Purlin 2x6','Piece',1,6,2,'SteelBuild','SB-ZP-007',NULL,'Galvanized Z-Purlin for roofing support','Galvanized Steel','Z-Purlin','Silver',6.00,'m',150.00,'mm','2.00',21.00,'kg',1450.00,1180.00,80,15,'z_purlin.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1008,'Roof Gutter','Piece',1,2,3,'RainFlow','RF-GT-008',NULL,'Pre-painted rain gutter','Galvanized Steel','Gutter','White',2.40,'m',150.00,'mm','0.50',4.50,'kg',650.00,480.00,60,10,'gutter.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1009,'Downspout Pipe','Piece',1,2,3,'RainFlow','RF-DP-009',NULL,'Rainwater downspout pipe','Galvanized Steel','Downspout','White',2.40,'m',75.00,'mm','0.50',3.20,'kg',420.00,300.00,75,10,'downspout.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1010,'Roof Sealant Silicone','Tube',0,7,4,'SealTech','ST-RS-010',NULL,'Weatherproof silicone sealant','Silicone','Sealant','Clear',NULL,NULL,NULL,NULL,NULL,0.30,'kg',185.00,125.00,200,40,'roof_sealant.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1011,'Butyl Flashing Tape','Roll',1,7,4,'SealTech','ST-BT-011',NULL,'Self-adhesive waterproof flashing tape','Butyl Rubber','Flashing Tape','Silver',10.00,'m',100.00,'mm','1.50',1.10,'kg',480.00,340.00,100,20,'flashing_tape.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1012,'Roofing Nail 2in','Box',0,3,4,'FixPro',NULL,NULL,'Galvanized roofing nails','Steel','Roofing Nail','Silver',NULL,NULL,NULL,NULL,NULL,5.00,'kg',390.00,280.00,180,40,'products/cHNHIrv0vHPYLTAI7Me2C78DeRRCoTC8TymMZabn.jpg','2026-08-04 20:01:46','2026-08-17 16:00:22'),(1013,'Metal Fascia Board','Piece',1,2,1,'RoofMaster',NULL,NULL,'Pre-painted metal fascia board for roof edges','Galvanized Steel','Fascia','Brown',2.40,'m',200.00,'mm','0.45',3.80,'kg',780.00,590.00,50,10,'products/8TJCn08NOTU5TwQwlczXk8luu8F4wovJdjiZ0sfD.jpg','2026-08-04 20:01:46','2026-08-17 15:59:20'),(1014,'Roof Ventilator','Piece',1,2,5,'VentPro',NULL,NULL,'Wind-driven roof ventilator','Aluminum','Ventilator','Silver',NULL,NULL,500.00,'mm',NULL,5.50,'kg',3250.00,2650.00,20,5,'products/o1W92aMbyLoXsEgg7FV6p6E9Rc0RKKmnr2I3aT3R.jpg','2026-08-04 20:01:46','2026-08-17 15:58:09'),(1015,'Roofing Rivets','Box',0,3,4,'FixPro',NULL,NULL,'Aluminum blind rivets for roofing installation','Aluminum','Rivet','Silver',NULL,NULL,NULL,NULL,NULL,1.00,'kg',260.00,180.00,250,50,'products/1Hxzfjmp8J7Qn0RvxUYvyo996jVBZ1tZLy4642Yh.jpg','2026-08-04 20:01:46','2026-08-17 06:25:18'),(1017,'rew','Pack',0,3,NULL,NULL,'REW-00001',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',21.00,12.00,NULL,NULL,NULL,'2026-08-14 03:36:39','2026-08-14 03:36:39'),(1018,'sample','Pack',0,7,NULL,NULL,'SAM-00001',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',34.00,14.00,NULL,NULL,NULL,'2026-08-17 16:09:51','2026-08-17 16:09:51'),(1019,'SAMPLE1','Box',0,4,NULL,NULL,'SAM-00002',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',23.00,9.96,NULL,NULL,NULL,'2026-08-29 15:03:08','2026-08-29 15:03:08'),(1020,'SAMPLE2','Set',1,4,NULL,NULL,'SAM-00003',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',122.00,50.00,NULL,NULL,NULL,'2026-08-29 15:15:21','2026-08-29 15:15:21'),(1022,'SAMPLE3','Set',0,1,NULL,NULL,'SAM-00004',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',21.00,11.72,NULL,NULL,NULL,'2026-08-29 15:19:09','2026-08-29 15:19:09'),(1023,'SAMPLE 4','Box',0,5,NULL,NULL,'SAM-00005',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',21.00,11.99,NULL,NULL,NULL,'2026-08-29 15:21:45','2026-08-29 15:21:45'),(1024,'gg','Box',0,1,NULL,NULL,'GGX-00001','B2D21',NULL,'xz',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',12.00,5.00,NULL,NULL,NULL,'2026-09-17 10:10:57','2026-09-17 10:10:57'),(1025,'book','Pack',0,4,NULL,NULL,'BOO-00001','9781781257029',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',12.00,6.00,NULL,NULL,NULL,'2026-09-17 10:14:37','2026-09-17 10:14:37');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_records`
--

DROP TABLE IF EXISTS `purchase_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_records` (
  `PurchaseID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `SupplierID` bigint unsigned NOT NULL,
  `ProductID` bigint unsigned NOT NULL,
  `Quantity` int NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `TotalAmount` decimal(10,2) NOT NULL,
  `PurchaseDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ReferenceNumber` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`PurchaseID`),
  KEY `purchase_records_supplierid_purchasedate_index` (`SupplierID`,`PurchaseDate`),
  KEY `purchase_records_productid_purchasedate_index` (`ProductID`,`PurchaseDate`),
  CONSTRAINT `purchase_records_productid_foreign` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_records_supplierid_foreign` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_records`
--

LOCK TABLES `purchase_records` WRITE;
/*!40000 ALTER TABLE `purchase_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `SaleID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `SaleDate` datetime NOT NULL,
  `CustomerID` bigint unsigned DEFAULT NULL,
  `TotalAmount` decimal(10,2) NOT NULL,
  `DiscountAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `AmountPaid` decimal(10,2) NOT NULL,
  `PaymentMethod` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ClerkID` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`SaleID`),
  KEY `sales_customerid_foreign` (`CustomerID`),
  KEY `sales_clerkid_foreign` (`ClerkID`),
  CONSTRAINT `sales_clerkid_foreign` FOREIGN KEY (`ClerkID`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_customerid_foreign` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (17,'2026-07-01 06:35:27',14,9800.00,0.00,10000.00,'Cash',1,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(18,'2026-07-01 06:36:03',14,5992.00,0.00,6000.00,'Cash',1,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(19,'2026-07-01 06:36:18',15,3528.00,0.00,3600.00,'Cash',1,'2026-06-30 22:36:18','2026-06-30 22:36:18'),(20,'2026-07-28 11:02:37',16,1823.36,222.00,1824.00,'Cash',1,'2026-07-28 03:02:37','2026-07-28 03:02:37'),(21,'2026-07-28 11:22:40',17,1750.00,0.00,1750.00,'Cash',1,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(22,'2026-07-28 11:29:53',18,3250.00,0.00,3250.00,'Cash',1,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(23,'2026-07-28 20:57:44',19,450.00,0.00,450.00,'Cash',1,'2026-07-28 12:57:44','2026-07-28 12:57:44'),(24,'2026-07-28 20:59:50',20,4100.00,0.00,4500.00,'Cash',1,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(25,'2026-07-28 21:08:16',21,2800.00,0.00,2800.00,'Cash',1,'2026-07-28 13:08:16','2026-07-28 13:08:16'),(26,'2026-07-29 05:23:09',22,950.00,0.00,950.00,'Cash',1,'2026-07-28 21:23:09','2026-07-28 21:23:09'),(27,'2026-07-29 05:41:56',23,1850.00,0.00,1900.00,'Cash',1,'2026-07-28 21:41:56','2026-07-28 21:41:56'),(28,'2026-07-29 06:25:09',24,450.00,0.00,500.00,'Cash',1,'2026-07-28 22:25:09','2026-07-28 22:25:09'),(29,'2026-07-28 22:45:27',25,350.00,0.00,400.00,'Cash',1,'2026-07-28 22:45:27','2026-07-28 22:45:27'),(30,'2026-07-29 06:55:32',26,450.00,0.00,500.00,'Cash',1,'2026-07-28 22:55:32','2026-07-28 22:55:32'),(31,'2026-08-02 04:29:22',27,6400.00,0.00,6400.00,'Cash',1,'2026-08-01 20:29:22','2026-08-01 20:29:22'),(32,'2026-08-02 04:30:05',27,2800.00,0.00,2800.00,'Cash',1,'2026-08-01 20:30:05','2026-08-01 20:30:05'),(33,'2026-08-03 16:45:52',28,450.00,0.00,1000.00,'Cash',1,'2026-08-03 08:45:52','2026-08-03 08:45:52'),(34,'2026-08-04 08:15:04',29,3750.00,0.00,3800.00,'Cash',1,'2026-08-04 00:15:04','2026-08-04 00:15:04'),(35,'2026-08-06 17:26:26',30,1590.00,0.00,1600.00,'Cash',1,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(36,'2026-08-06 18:13:58',31,780.00,0.00,1000.00,'Cash',1,'2026-08-06 10:13:58','2026-08-06 10:13:58'),(37,'2026-08-12 11:30:40',32,3580.00,0.00,5000.00,'Cash',1,'2026-08-12 03:30:40','2026-08-12 03:30:40'),(38,'2026-08-13 01:38:32',33,6325.00,0.00,6400.00,'Cash',1,'2026-08-12 17:38:32','2026-08-12 17:38:32'),(39,'2026-08-13 01:44:30',34,1930.00,0.00,1930.00,'Cash',1,'2026-08-12 17:44:30','2026-08-12 17:44:30'),(40,'2026-08-13 01:45:16',35,1930.00,0.00,2000.00,'Cash',1,'2026-08-12 17:45:16','2026-08-12 17:45:16'),(41,'2026-08-13 01:49:10',36,1160.00,0.00,1200.00,'Cash',1,'2026-08-12 17:49:10','2026-08-12 17:49:10'),(42,'2026-08-13 02:00:47',37,1040.00,0.00,1100.00,'Cash',1,'2026-08-12 18:00:47','2026-08-12 18:00:47'),(43,'2026-08-13 02:03:25',38,3900.00,0.00,4000.00,'Cash',1,'2026-08-12 18:03:25','2026-08-12 18:03:25'),(44,'2026-08-13 02:04:29',39,1240.00,0.00,2000.00,'Cash',1,'2026-08-12 18:04:29','2026-08-12 18:04:29'),(45,'2026-08-14 11:18:59',40,3510.00,0.00,3600.00,'Cash',1,'2026-08-14 03:19:00','2026-08-14 03:19:00'),(46,'2026-08-14 11:19:27',41,910.00,0.00,910.00,'Cash',1,'2026-08-14 03:19:27','2026-08-14 03:19:27'),(47,'2026-08-14 11:27:29',42,1170.00,0.00,1200.00,'Cash',1,'2026-08-14 03:27:29','2026-08-14 03:27:29'),(48,'2026-08-14 11:33:42',43,9780.00,0.00,9780.00,'Cash',1,'2026-08-14 03:33:42','2026-08-14 03:33:42'),(49,'2026-08-14 11:34:25',44,3315.00,0.00,3315.00,'Cash',1,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(50,'2026-08-14 11:42:56',45,21.00,0.00,21.00,'Cash',1,'2026-08-14 03:42:57','2026-08-14 03:42:57'),(51,'2026-08-14 11:46:13',46,2100.00,0.00,2100.00,'Cash',1,'2026-08-14 03:46:13','2026-08-14 03:46:13'),(52,'2026-08-18 00:10:29',47,3250.00,0.00,3300.00,'Cash',1,'2026-08-17 16:10:29','2026-08-17 16:10:29'),(53,'2026-08-18 00:10:49',47,34.00,0.00,100.00,'Cash',1,'2026-08-17 16:10:49','2026-08-17 16:10:49'),(54,'2026-08-18 00:11:38',48,260.00,0.00,300.00,'Cash',1,'2026-08-17 16:11:38','2026-08-17 16:11:38'),(55,'2026-08-25 00:44:32',49,639.00,0.00,700.00,'Cash',1,'2026-08-24 16:44:33','2026-08-24 16:44:33'),(56,'2026-08-25 00:45:33',50,3250.00,0.00,3300.00,'Cash',1,'2026-08-24 16:45:33','2026-08-24 16:45:33'),(57,'2026-08-25 00:46:03',50,980.00,0.00,1000.00,'Cash',1,'2026-08-24 16:46:03','2026-08-24 16:46:03'),(58,'2026-08-25 00:57:00',51,780.00,0.00,1000.00,'Cash',1,'2026-08-24 16:57:00','2026-08-24 16:57:00'),(59,'2026-08-25 00:57:30',52,1490.00,0.00,2000.00,'Cash',1,'2026-08-24 16:57:30','2026-08-24 16:57:30'),(60,'2026-08-26 18:38:37',53,4290.00,0.00,4300.00,'Cash',1,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(61,'2026-08-26 19:22:06',54,3670.00,0.00,3700.00,'Cash',1,'2026-08-26 11:22:07','2026-08-26 11:22:07'),(62,'2026-08-26 19:52:21',55,3250.00,0.00,3300.00,'Cash',1,'2026-08-26 11:52:21','2026-08-26 11:52:21'),(63,'2026-08-26 19:53:06',56,1200.00,0.00,1200.00,'Cash',1,'2026-08-26 11:53:06','2026-08-26 11:53:06'),(64,'2026-08-28 17:16:37',57,260.00,0.00,300.00,'Cash',1,'2026-08-28 09:16:37','2026-08-28 09:16:37'),(65,'2026-08-29 21:13:12',58,4605.00,0.00,4605.00,'Cash',1,'2026-08-29 13:13:12','2026-08-29 13:13:12'),(66,'2026-08-29 22:14:39',59,2630.00,0.00,2700.00,'Cash',1,'2026-08-29 14:14:39','2026-08-29 14:14:39'),(67,'2026-08-30 17:07:10',60,34.00,0.00,500.00,'Cash',1,'2026-08-30 09:07:10','2026-08-30 09:07:10'),(69,'2026-09-02 01:22:39',62,3250.00,0.00,3300.00,'Cash',4,'2026-09-01 17:22:39','2026-09-01 17:22:39'),(70,'2026-09-02 15:18:03',63,260.00,0.00,260.00,'Cash',1,'2026-09-02 07:18:03','2026-09-02 07:18:03'),(71,'2026-09-03 05:28:05',64,204.00,0.00,300.00,'Cash',1,'2026-09-02 21:28:05','2026-09-02 21:28:05'),(72,'2026-09-17 18:19:22',65,228.00,0.00,228.00,'Cash',1,'2026-09-17 10:19:22','2026-09-17 10:19:22');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_items`
--

DROP TABLE IF EXISTS `sales_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_items` (
  `SalesItemID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `SaleID` bigint unsigned NOT NULL,
  `ProductID` bigint unsigned NOT NULL,
  `Quantity` int NOT NULL DEFAULT '1',
  `PriceAtSale` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`SalesItemID`),
  KEY `sales_items_saleid_foreign` (`SaleID`),
  KEY `sales_items_productid_foreign` (`ProductID`),
  CONSTRAINT `sales_items_productid_foreign` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `sales_items_saleid_foreign` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_items`
--

LOCK TABLES `sales_items` WRITE;
/*!40000 ALTER TABLE `sales_items` DISABLE KEYS */;
INSERT INTO `sales_items` VALUES (24,17,1004,1,350.00,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(25,17,1001,1,1850.00,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(26,17,1005,1,5600.00,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(27,17,1002,1,950.00,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(28,18,1002,2,950.00,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(29,18,1003,2,450.00,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(30,18,1001,1,1850.00,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(31,18,1004,2,350.00,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(32,19,1005,1,2800.00,'2026-06-30 22:36:18','2026-06-30 22:36:18'),(33,19,1004,1,350.00,'2026-06-30 22:36:18','2026-06-30 22:36:18'),(34,20,1001,1,1850.00,'2026-07-28 03:02:38','2026-07-28 03:02:38'),(35,21,1004,1,350.00,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(36,21,1002,1,950.00,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(37,21,1003,1,450.00,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(38,22,1002,1,950.00,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(39,22,1003,1,450.00,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(40,22,1001,1,1850.00,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(41,23,1003,1,450.00,'2026-07-28 12:57:44','2026-07-28 12:57:44'),(42,24,1005,1,2800.00,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(43,24,1002,1,950.00,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(44,24,1004,1,350.00,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(45,25,1005,1,2800.00,'2026-07-28 13:08:16','2026-07-28 13:08:16'),(46,26,1002,1,950.00,'2026-07-28 21:23:09','2026-07-28 21:23:09'),(47,27,1001,1,1850.00,'2026-07-28 21:41:56','2026-07-28 21:41:56'),(48,28,1003,1,450.00,'2026-07-28 22:25:09','2026-07-28 22:25:09'),(49,29,1004,1,350.00,'2026-07-28 22:45:27','2026-07-28 22:45:27'),(50,30,1003,1,450.00,'2026-07-28 22:55:32','2026-07-28 22:55:32'),(51,31,1003,1,450.00,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(52,31,1004,1,350.00,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(53,31,1002,1,950.00,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(54,31,1005,1,2800.00,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(55,31,1001,1,1850.00,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(56,32,1005,1,2800.00,'2026-08-01 20:30:05','2026-08-01 20:30:05'),(57,33,1003,1,450.00,'2026-08-03 08:45:53','2026-08-03 08:45:53'),(58,34,1005,1,2800.00,'2026-08-04 00:15:04','2026-08-04 00:15:04'),(59,34,1002,1,950.00,'2026-08-04 00:15:05','2026-08-04 00:15:05'),(60,35,1012,1,390.00,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(61,35,1013,1,780.00,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(62,35,1009,1,420.00,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(63,36,1013,1,780.00,'2026-08-06 10:13:58','2026-08-06 10:13:58'),(64,37,1015,1,260.00,'2026-08-12 03:30:40','2026-08-12 03:30:40'),(65,37,1009,1,420.00,'2026-08-12 03:30:41','2026-08-12 03:30:41'),(66,37,1007,2,1450.00,'2026-08-12 03:30:41','2026-08-12 03:30:41'),(67,38,1008,1,650.00,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(68,38,1007,1,1450.00,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(69,38,1006,1,980.00,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(70,38,1005,1,2800.00,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(71,38,1010,1,185.00,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(72,38,1015,1,260.00,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(73,39,1011,1,480.00,'2026-08-12 17:44:30','2026-08-12 17:44:30'),(74,39,1007,1,1450.00,'2026-08-12 17:44:30','2026-08-12 17:44:30'),(75,40,1011,1,480.00,'2026-08-12 17:45:16','2026-08-12 17:45:16'),(76,40,1007,1,1450.00,'2026-08-12 17:45:16','2026-08-12 17:45:16'),(77,41,1011,1,480.00,'2026-08-12 17:49:10','2026-08-12 17:49:10'),(78,41,1015,1,260.00,'2026-08-12 17:49:10','2026-08-12 17:49:10'),(79,41,1009,1,420.00,'2026-08-12 17:49:11','2026-08-12 17:49:11'),(80,42,1012,1,390.00,'2026-08-12 18:00:47','2026-08-12 18:00:47'),(81,42,1008,1,650.00,'2026-08-12 18:00:47','2026-08-12 18:00:47'),(82,43,1014,1,3250.00,'2026-08-12 18:03:25','2026-08-12 18:03:25'),(83,43,1008,1,650.00,'2026-08-12 18:03:25','2026-08-12 18:03:25'),(84,44,1015,1,260.00,'2026-08-12 18:04:29','2026-08-12 18:04:29'),(85,44,1006,1,980.00,'2026-08-12 18:04:29','2026-08-12 18:04:29'),(86,45,1015,1,260.00,'2026-08-14 03:19:00','2026-08-14 03:19:00'),(87,45,1014,1,3250.00,'2026-08-14 03:19:00','2026-08-14 03:19:00'),(88,46,1015,1,260.00,'2026-08-14 03:19:27','2026-08-14 03:19:27'),(89,46,1008,1,650.00,'2026-08-14 03:19:27','2026-08-14 03:19:27'),(90,47,1013,1,780.00,'2026-08-14 03:27:30','2026-08-14 03:27:30'),(91,47,1012,1,390.00,'2026-08-14 03:27:30','2026-08-14 03:27:30'),(92,48,1013,1,780.00,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(93,48,1008,1,650.00,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(94,48,1005,1,2800.00,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(95,48,1003,1,450.00,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(96,48,1001,1,1850.00,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(97,48,1014,1,3250.00,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(98,49,1015,1,260.00,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(99,49,1008,1,650.00,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(100,49,1007,1,1450.00,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(101,49,1009,1,420.00,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(102,49,1010,1,185.00,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(103,49,1004,1,350.00,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(104,50,1017,1,21.00,'2026-08-14 03:42:57','2026-08-14 03:42:57'),(105,51,1008,1,650.00,'2026-08-14 03:46:13','2026-08-14 03:46:13'),(106,51,1007,1,1450.00,'2026-08-14 03:46:13','2026-08-14 03:46:13'),(107,52,1014,1,3250.00,'2026-08-17 16:10:30','2026-08-17 16:10:30'),(108,53,1018,1,34.00,'2026-08-17 16:10:49','2026-08-17 16:10:49'),(109,54,1015,1,260.00,'2026-08-17 16:11:38','2026-08-17 16:11:38'),(110,55,1018,12,34.00,'2026-08-24 16:44:33','2026-08-24 16:44:33'),(111,55,1017,11,21.00,'2026-08-24 16:44:33','2026-08-24 16:44:33'),(112,56,1014,1,3250.00,'2026-08-24 16:45:33','2026-08-24 16:45:33'),(113,57,1006,1,980.00,'2026-08-24 16:46:03','2026-08-24 16:46:03'),(114,58,1013,1,780.00,'2026-08-24 16:57:00','2026-08-24 16:57:00'),(115,59,1009,2,420.00,'2026-08-24 16:57:30','2026-08-24 16:57:30'),(116,59,1008,1,650.00,'2026-08-24 16:57:31','2026-08-24 16:57:31'),(117,60,1015,1,260.00,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(118,60,1014,1,3250.00,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(119,60,1013,1,780.00,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(120,61,1014,1,3250.00,'2026-08-26 11:22:07','2026-08-26 11:22:07'),(121,61,1009,1,420.00,'2026-08-26 11:22:07','2026-08-26 11:22:07'),(122,62,1014,1,3250.00,'2026-08-26 11:52:21','2026-08-26 11:52:21'),(123,63,1013,1,780.00,'2026-08-26 11:53:06','2026-08-26 11:53:06'),(124,63,1009,1,420.00,'2026-08-26 11:53:06','2026-08-26 11:53:06'),(125,64,1015,1,260.00,'2026-08-28 09:16:37','2026-08-28 09:16:37'),(126,65,1014,1,3250.00,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(127,65,1013,1,780.00,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(128,65,1010,1,185.00,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(129,65,1012,1,390.00,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(130,66,1013,1,780.00,'2026-08-29 14:14:39','2026-08-29 14:14:39'),(131,66,1001,1,1850.00,'2026-08-29 14:14:39','2026-08-29 14:14:39'),(132,67,1018,1,34.00,'2026-08-30 09:07:10','2026-08-30 09:07:10'),(135,69,1014,1,3250.00,'2026-09-01 17:22:39','2026-09-01 17:22:39'),(136,70,1015,1,260.00,'2026-09-02 07:18:03','2026-09-02 07:18:03'),(137,71,1018,6,34.00,'2026-09-02 21:28:05','2026-09-02 21:28:05'),(138,72,1024,13,12.00,'2026-09-17 10:19:22','2026-09-17 10:19:22'),(139,72,1025,6,12.00,'2026-09-17 10:19:22','2026-09-17 10:19:22');
/*!40000 ALTER TABLE `sales_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `SupplierID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `SupplierName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ContactNumber` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`SupplierID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'RoofMaster Supply Corporation','09171234567','sales@roofmaster.com','Quezon City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19'),(2,'BuildPro Construction Supplies','09181234567','info@buildpro.com','Makati City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19'),(3,'SteelTech Roofing Solutions','09191234567','contact@steeltech.com','Pasig City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19'),(4,'FixPro Fasteners Trading','09201234567','support@fixpro.com','Caloocan City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19'),(5,'ThermoShield Insulation Center','09211234567','sales@thermoshield.com','Valenzuela City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `TransactionID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ProductID` bigint unsigned NOT NULL,
  `TransactionType` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `TransactionDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `QuantityChange` int NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `TotalAmount` decimal(10,2) NOT NULL,
  `ReferenceType` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ReferenceID` bigint unsigned DEFAULT NULL,
  `Notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`TransactionID`),
  KEY `transactions_productid_foreign` (`ProductID`),
  CONSTRAINT `transactions_productid_foreign` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,1005,'SALE','2026-06-11 11:44:07',-1,2800.00,2800.00,NULL,1,NULL,'2026-06-11 11:44:07','2026-06-11 11:44:07'),(2,1004,'SALE','2026-06-11 12:29:02',-1,350.00,350.00,NULL,2,NULL,'2026-06-11 12:29:02','2026-06-11 12:29:02'),(3,1003,'SALE','2026-06-11 12:29:02',-1,450.00,450.00,NULL,2,NULL,'2026-06-11 12:29:02','2026-06-11 12:29:02'),(4,1003,'SALE','2026-06-14 09:16:44',-4,450.00,1800.00,NULL,3,NULL,'2026-06-14 09:16:44','2026-06-14 09:16:44'),(5,1002,'SALE','2026-06-14 09:16:44',-2,950.00,1900.00,NULL,3,NULL,'2026-06-14 09:16:44','2026-06-14 09:16:44'),(6,1005,'SALE','2026-06-27 04:57:43',-1,2800.00,2800.00,NULL,4,NULL,'2026-06-27 04:57:43','2026-06-27 04:57:43'),(7,1004,'SALE','2026-06-27 05:00:11',-1,350.00,350.00,NULL,5,NULL,'2026-06-27 05:00:11','2026-06-27 05:00:11'),(8,1002,'SALE','2026-06-27 05:01:10',-1,950.00,950.00,NULL,6,NULL,'2026-06-27 05:01:10','2026-06-27 05:01:10'),(9,1001,'SALE','2026-06-27 05:01:40',-1,1850.00,1850.00,NULL,7,NULL,'2026-06-27 05:01:40','2026-06-27 05:01:40'),(10,1001,'SALE','2026-06-27 05:02:26',-1,1850.00,1850.00,NULL,8,NULL,'2026-06-27 05:02:26','2026-06-27 05:02:26'),(11,1004,'SALE','2026-06-27 05:02:58',-1,350.00,350.00,NULL,9,NULL,'2026-06-27 05:02:58','2026-06-27 05:02:58'),(12,1004,'SALE','2026-06-28 09:19:59',-1,350.00,350.00,NULL,10,NULL,'2026-06-28 09:19:59','2026-06-28 09:19:59'),(13,1004,'SALE','2026-06-29 23:48:24',-1,350.00,350.00,NULL,11,NULL,'2026-06-29 23:48:24','2026-06-29 23:48:24'),(14,1005,'SALE','2026-06-30 22:11:43',-1,2800.00,2800.00,NULL,12,NULL,'2026-06-30 22:11:43','2026-06-30 22:11:43'),(15,1003,'SALE','2026-06-30 22:11:43',-1,450.00,450.00,NULL,12,NULL,'2026-06-30 22:11:43','2026-06-30 22:11:43'),(16,1002,'SALE','2026-06-30 22:12:11',-1,950.00,950.00,NULL,13,NULL,'2026-06-30 22:12:11','2026-06-30 22:12:11'),(17,1001,'SALE','2026-06-30 22:12:11',-1,1850.00,1850.00,NULL,13,NULL,'2026-06-30 22:12:11','2026-06-30 22:12:11'),(18,1005,'SALE','2026-06-30 22:26:43',-1,2800.00,2800.00,NULL,14,NULL,'2026-06-30 22:26:43','2026-06-30 22:26:43'),(19,1004,'SALE','2026-06-30 22:26:44',-1,350.00,350.00,NULL,14,NULL,'2026-06-30 22:26:44','2026-06-30 22:26:44'),(20,1002,'SALE','2026-06-30 22:27:02',-1,950.00,950.00,NULL,15,NULL,'2026-06-30 22:27:02','2026-06-30 22:27:02'),(21,1001,'SALE','2026-06-30 22:27:03',-1,1850.00,1850.00,NULL,15,NULL,'2026-06-30 22:27:03','2026-06-30 22:27:03'),(22,1005,'SALE','2026-06-30 22:27:35',-1,2800.00,2800.00,NULL,16,NULL,'2026-06-30 22:27:35','2026-06-30 22:27:35'),(23,1001,'SALE','2026-06-30 22:27:35',-1,1850.00,1850.00,NULL,16,NULL,'2026-06-30 22:27:35','2026-06-30 22:27:35'),(24,1004,'SALE','2026-06-30 22:35:27',-1,350.00,350.00,NULL,17,NULL,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(25,1001,'SALE','2026-06-30 22:35:27',-1,1850.00,1850.00,NULL,17,NULL,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(26,1005,'SALE','2026-06-30 22:35:27',-1,5600.00,5600.00,NULL,17,NULL,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(27,1002,'SALE','2026-06-30 22:35:27',-1,950.00,950.00,NULL,17,NULL,'2026-06-30 22:35:27','2026-06-30 22:35:27'),(28,1002,'SALE','2026-06-30 22:36:03',-2,950.00,1900.00,NULL,18,NULL,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(29,1003,'SALE','2026-06-30 22:36:03',-2,450.00,900.00,NULL,18,NULL,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(30,1001,'SALE','2026-06-30 22:36:03',-1,1850.00,1850.00,NULL,18,NULL,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(31,1004,'SALE','2026-06-30 22:36:03',-2,350.00,700.00,NULL,18,NULL,'2026-06-30 22:36:03','2026-06-30 22:36:03'),(32,1005,'SALE','2026-06-30 22:36:18',-1,2800.00,2800.00,NULL,19,NULL,'2026-06-30 22:36:18','2026-06-30 22:36:18'),(33,1004,'SALE','2026-06-30 22:36:18',-1,350.00,350.00,NULL,19,NULL,'2026-06-30 22:36:18','2026-06-30 22:36:18'),(34,1001,'SALE','2026-07-28 03:02:38',-1,1850.00,1850.00,NULL,20,NULL,'2026-07-28 03:02:38','2026-07-28 03:02:38'),(35,1004,'SALE','2026-07-28 03:22:40',-1,350.00,350.00,NULL,21,NULL,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(36,1002,'SALE','2026-07-28 03:22:40',-1,950.00,950.00,NULL,21,NULL,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(37,1003,'SALE','2026-07-28 03:22:40',-1,450.00,450.00,NULL,21,NULL,'2026-07-28 03:22:40','2026-07-28 03:22:40'),(38,1002,'SALE','2026-07-28 03:29:53',-1,950.00,950.00,NULL,22,NULL,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(39,1003,'SALE','2026-07-28 03:29:53',-1,450.00,450.00,NULL,22,NULL,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(40,1001,'SALE','2026-07-28 03:29:53',-1,1850.00,1850.00,NULL,22,NULL,'2026-07-28 03:29:53','2026-07-28 03:29:53'),(41,1003,'SALE','2026-07-28 12:57:44',-1,450.00,450.00,NULL,23,NULL,'2026-07-28 12:57:44','2026-07-28 12:57:44'),(42,1005,'SALE','2026-07-28 12:59:50',-1,2800.00,2800.00,NULL,24,NULL,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(43,1002,'SALE','2026-07-28 12:59:50',-1,950.00,950.00,NULL,24,NULL,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(44,1004,'SALE','2026-07-28 12:59:50',-1,350.00,350.00,NULL,24,NULL,'2026-07-28 12:59:50','2026-07-28 12:59:50'),(45,1005,'SALE','2026-07-28 13:08:16',-1,2800.00,2800.00,NULL,25,NULL,'2026-07-28 13:08:16','2026-07-28 13:08:16'),(46,1002,'SALE','2026-07-28 21:23:09',-1,950.00,950.00,NULL,26,NULL,'2026-07-28 21:23:09','2026-07-28 21:23:09'),(47,1001,'SALE','2026-07-28 21:41:56',-1,1850.00,1850.00,NULL,27,NULL,'2026-07-28 21:41:56','2026-07-28 21:41:56'),(48,1003,'SALE','2026-07-28 22:25:10',-1,450.00,450.00,NULL,28,NULL,'2026-07-28 22:25:10','2026-07-28 22:25:10'),(49,1004,'SALE','2026-07-28 22:45:27',-1,350.00,350.00,NULL,29,NULL,'2026-07-28 22:45:27','2026-07-28 22:45:27'),(50,1003,'SALE','2026-07-28 22:55:32',-1,450.00,450.00,NULL,30,NULL,'2026-07-28 22:55:32','2026-07-28 22:55:32'),(51,1003,'SALE','2026-08-01 20:29:23',-1,450.00,450.00,NULL,31,NULL,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(52,1004,'SALE','2026-08-01 20:29:23',-1,350.00,350.00,NULL,31,NULL,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(53,1002,'SALE','2026-08-01 20:29:23',-1,950.00,950.00,NULL,31,NULL,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(54,1005,'SALE','2026-08-01 20:29:23',-1,2800.00,2800.00,NULL,31,NULL,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(55,1001,'SALE','2026-08-01 20:29:23',-1,1850.00,1850.00,NULL,31,NULL,'2026-08-01 20:29:23','2026-08-01 20:29:23'),(56,1005,'SALE','2026-08-01 20:30:05',-1,2800.00,2800.00,NULL,32,NULL,'2026-08-01 20:30:05','2026-08-01 20:30:05'),(57,1003,'SALE','2026-08-03 08:45:53',-1,450.00,450.00,NULL,33,NULL,'2026-08-03 08:45:53','2026-08-03 08:45:53'),(58,1005,'SALE','2026-08-04 00:15:04',-1,2800.00,2800.00,NULL,34,NULL,'2026-08-04 00:15:04','2026-08-04 00:15:04'),(59,1002,'SALE','2026-08-04 00:15:05',-1,950.00,950.00,NULL,34,NULL,'2026-08-04 00:15:05','2026-08-04 00:15:05'),(60,1012,'SALE','2026-08-06 09:26:26',-1,390.00,390.00,NULL,35,NULL,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(61,1013,'SALE','2026-08-06 09:26:26',-1,780.00,780.00,NULL,35,NULL,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(62,1009,'SALE','2026-08-06 09:26:26',-1,420.00,420.00,NULL,35,NULL,'2026-08-06 09:26:26','2026-08-06 09:26:26'),(63,1013,'SALE','2026-08-06 10:13:58',-1,780.00,780.00,NULL,36,NULL,'2026-08-06 10:13:58','2026-08-06 10:13:58'),(64,1015,'SALE','2026-08-12 03:30:40',-1,260.00,260.00,NULL,37,NULL,'2026-08-12 03:30:40','2026-08-12 03:30:40'),(65,1009,'SALE','2026-08-12 03:30:41',-1,420.00,420.00,NULL,37,NULL,'2026-08-12 03:30:41','2026-08-12 03:30:41'),(66,1007,'SALE','2026-08-12 03:30:41',-2,1450.00,2900.00,NULL,37,NULL,'2026-08-12 03:30:41','2026-08-12 03:30:41'),(67,1008,'SALE','2026-08-12 17:38:33',-1,650.00,650.00,NULL,38,NULL,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(68,1007,'SALE','2026-08-12 17:38:33',-1,1450.00,1450.00,NULL,38,NULL,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(69,1006,'SALE','2026-08-12 17:38:33',-1,980.00,980.00,NULL,38,NULL,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(70,1005,'SALE','2026-08-12 17:38:33',-1,2800.00,2800.00,NULL,38,NULL,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(71,1010,'SALE','2026-08-12 17:38:33',-1,185.00,185.00,NULL,38,NULL,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(72,1015,'SALE','2026-08-12 17:38:33',-1,260.00,260.00,NULL,38,NULL,'2026-08-12 17:38:33','2026-08-12 17:38:33'),(73,1011,'SALE','2026-08-12 17:44:30',-1,480.00,480.00,NULL,39,NULL,'2026-08-12 17:44:30','2026-08-12 17:44:30'),(74,1007,'SALE','2026-08-12 17:44:30',-1,1450.00,1450.00,NULL,39,NULL,'2026-08-12 17:44:30','2026-08-12 17:44:30'),(75,1011,'SALE','2026-08-12 17:45:16',-1,480.00,480.00,NULL,40,NULL,'2026-08-12 17:45:16','2026-08-12 17:45:16'),(76,1007,'SALE','2026-08-12 17:45:16',-1,1450.00,1450.00,NULL,40,NULL,'2026-08-12 17:45:16','2026-08-12 17:45:16'),(77,1011,'SALE','2026-08-12 17:49:10',-1,480.00,480.00,NULL,41,NULL,'2026-08-12 17:49:10','2026-08-12 17:49:10'),(78,1015,'SALE','2026-08-12 17:49:10',-1,260.00,260.00,NULL,41,NULL,'2026-08-12 17:49:11','2026-08-12 17:49:11'),(79,1009,'SALE','2026-08-12 17:49:11',-1,420.00,420.00,NULL,41,NULL,'2026-08-12 17:49:11','2026-08-12 17:49:11'),(80,1012,'SALE','2026-08-12 18:00:47',-1,390.00,390.00,NULL,42,NULL,'2026-08-12 18:00:47','2026-08-12 18:00:47'),(81,1008,'SALE','2026-08-12 18:00:47',-1,650.00,650.00,NULL,42,NULL,'2026-08-12 18:00:47','2026-08-12 18:00:47'),(82,1014,'SALE','2026-08-12 18:03:25',-1,3250.00,3250.00,NULL,43,NULL,'2026-08-12 18:03:25','2026-08-12 18:03:25'),(83,1008,'SALE','2026-08-12 18:03:25',-1,650.00,650.00,NULL,43,NULL,'2026-08-12 18:03:25','2026-08-12 18:03:25'),(84,1015,'SALE','2026-08-12 18:04:29',-1,260.00,260.00,NULL,44,NULL,'2026-08-12 18:04:29','2026-08-12 18:04:29'),(85,1006,'SALE','2026-08-12 18:04:29',-1,980.00,980.00,NULL,44,NULL,'2026-08-12 18:04:29','2026-08-12 18:04:29'),(86,1015,'SALE','2026-08-14 03:19:00',-1,260.00,260.00,NULL,45,NULL,'2026-08-14 03:19:00','2026-08-14 03:19:00'),(87,1014,'SALE','2026-08-14 03:19:00',-1,3250.00,3250.00,NULL,45,NULL,'2026-08-14 03:19:00','2026-08-14 03:19:00'),(88,1015,'SALE','2026-08-14 03:19:27',-1,260.00,260.00,NULL,46,NULL,'2026-08-14 03:19:27','2026-08-14 03:19:27'),(89,1008,'SALE','2026-08-14 03:19:27',-1,650.00,650.00,NULL,46,NULL,'2026-08-14 03:19:27','2026-08-14 03:19:27'),(90,1013,'SALE','2026-08-14 03:27:30',-1,780.00,780.00,NULL,47,NULL,'2026-08-14 03:27:30','2026-08-14 03:27:30'),(91,1012,'SALE','2026-08-14 03:27:30',-1,390.00,390.00,NULL,47,NULL,'2026-08-14 03:27:30','2026-08-14 03:27:30'),(92,1013,'SALE','2026-08-14 03:33:43',-1,780.00,780.00,NULL,48,NULL,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(93,1008,'SALE','2026-08-14 03:33:43',-1,650.00,650.00,NULL,48,NULL,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(94,1005,'SALE','2026-08-14 03:33:43',-1,2800.00,2800.00,NULL,48,NULL,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(95,1003,'SALE','2026-08-14 03:33:43',-1,450.00,450.00,NULL,48,NULL,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(96,1001,'SALE','2026-08-14 03:33:43',-1,1850.00,1850.00,NULL,48,NULL,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(97,1014,'SALE','2026-08-14 03:33:43',-1,3250.00,3250.00,NULL,48,NULL,'2026-08-14 03:33:43','2026-08-14 03:33:43'),(98,1015,'SALE','2026-08-14 03:34:25',-1,260.00,260.00,NULL,49,NULL,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(99,1008,'SALE','2026-08-14 03:34:25',-1,650.00,650.00,NULL,49,NULL,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(100,1007,'SALE','2026-08-14 03:34:25',-1,1450.00,1450.00,NULL,49,NULL,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(101,1009,'SALE','2026-08-14 03:34:25',-1,420.00,420.00,NULL,49,NULL,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(102,1010,'SALE','2026-08-14 03:34:25',-1,185.00,185.00,NULL,49,NULL,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(103,1004,'SALE','2026-08-14 03:34:25',-1,350.00,350.00,NULL,49,NULL,'2026-08-14 03:34:25','2026-08-14 03:34:25'),(104,1017,'SALE','2026-08-14 03:42:57',-1,21.00,21.00,NULL,50,NULL,'2026-08-14 03:42:57','2026-08-14 03:42:57'),(105,1008,'SALE','2026-08-14 03:46:13',-1,650.00,650.00,NULL,51,NULL,'2026-08-14 03:46:13','2026-08-14 03:46:13'),(106,1007,'SALE','2026-08-14 03:46:13',-1,1450.00,1450.00,NULL,51,NULL,'2026-08-14 03:46:13','2026-08-14 03:46:13'),(107,1014,'SALE','2026-08-17 16:10:30',-1,3250.00,3250.00,NULL,52,NULL,'2026-08-17 16:10:30','2026-08-17 16:10:30'),(108,1018,'SALE','2026-08-17 16:10:49',-1,34.00,34.00,NULL,53,NULL,'2026-08-17 16:10:49','2026-08-17 16:10:49'),(109,1015,'SALE','2026-08-17 16:11:38',-1,260.00,260.00,NULL,54,NULL,'2026-08-17 16:11:38','2026-08-17 16:11:38'),(110,1018,'SALE','2026-08-24 16:44:33',-12,34.00,408.00,NULL,55,NULL,'2026-08-24 16:44:33','2026-08-24 16:44:33'),(111,1017,'SALE','2026-08-24 16:44:33',-11,21.00,231.00,NULL,55,NULL,'2026-08-24 16:44:33','2026-08-24 16:44:33'),(112,1014,'SALE','2026-08-24 16:45:33',-1,3250.00,3250.00,NULL,56,NULL,'2026-08-24 16:45:33','2026-08-24 16:45:33'),(113,1006,'SALE','2026-08-24 16:46:03',-1,980.00,980.00,NULL,57,NULL,'2026-08-24 16:46:03','2026-08-24 16:46:03'),(114,1013,'SALE','2026-08-24 16:57:00',-1,780.00,780.00,NULL,58,NULL,'2026-08-24 16:57:00','2026-08-24 16:57:00'),(115,1009,'SALE','2026-08-24 16:57:30',-2,420.00,840.00,NULL,59,NULL,'2026-08-24 16:57:30','2026-08-24 16:57:30'),(116,1008,'SALE','2026-08-24 16:57:31',-1,650.00,650.00,NULL,59,NULL,'2026-08-24 16:57:31','2026-08-24 16:57:31'),(117,1015,'SALE','2026-08-26 10:38:37',-1,260.00,260.00,NULL,60,NULL,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(118,1014,'SALE','2026-08-26 10:38:37',-1,3250.00,3250.00,NULL,60,NULL,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(119,1013,'SALE','2026-08-26 10:38:37',-1,780.00,780.00,NULL,60,NULL,'2026-08-26 10:38:37','2026-08-26 10:38:37'),(120,1014,'SALE','2026-08-26 11:22:07',-1,3250.00,3250.00,NULL,61,NULL,'2026-08-26 11:22:07','2026-08-26 11:22:07'),(121,1009,'SALE','2026-08-26 11:22:07',-1,420.00,420.00,NULL,61,NULL,'2026-08-26 11:22:07','2026-08-26 11:22:07'),(122,1014,'SALE','2026-08-26 11:52:21',-1,3250.00,3250.00,NULL,62,NULL,'2026-08-26 11:52:21','2026-08-26 11:52:21'),(123,1013,'SALE','2026-08-26 11:53:06',-1,780.00,780.00,NULL,63,NULL,'2026-08-26 11:53:06','2026-08-26 11:53:06'),(124,1009,'SALE','2026-08-26 11:53:06',-1,420.00,420.00,NULL,63,NULL,'2026-08-26 11:53:06','2026-08-26 11:53:06'),(125,1015,'SALE','2026-08-28 09:16:37',-1,260.00,260.00,NULL,64,NULL,'2026-08-28 09:16:37','2026-08-28 09:16:37'),(126,1014,'SALE','2026-08-29 13:13:13',-1,3250.00,3250.00,NULL,65,NULL,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(127,1013,'SALE','2026-08-29 13:13:13',-1,780.00,780.00,NULL,65,NULL,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(128,1010,'SALE','2026-08-29 13:13:13',-1,185.00,185.00,NULL,65,NULL,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(129,1012,'SALE','2026-08-29 13:13:13',-1,390.00,390.00,NULL,65,NULL,'2026-08-29 13:13:13','2026-08-29 13:13:13'),(130,1013,'SALE','2026-08-29 14:14:39',-1,780.00,780.00,NULL,66,NULL,'2026-08-29 14:14:39','2026-08-29 14:14:39'),(131,1001,'SALE','2026-08-29 14:14:39',-1,1850.00,1850.00,NULL,66,NULL,'2026-08-29 14:14:39','2026-08-29 14:14:39'),(132,1018,'SALE','2026-08-30 09:07:10',-1,34.00,34.00,NULL,67,NULL,'2026-08-30 09:07:10','2026-08-30 09:07:10'),(133,1020,'SALE','2026-08-30 09:24:07',-10,122.00,1220.00,NULL,68,NULL,'2026-08-30 09:24:07','2026-08-30 09:24:07'),(134,1015,'SALE','2026-08-30 09:24:07',-1,260.00,260.00,NULL,68,NULL,'2026-08-30 09:24:07','2026-08-30 09:24:07'),(135,1014,'SALE','2026-09-01 17:22:39',-1,3250.00,3250.00,NULL,69,NULL,'2026-09-01 17:22:39','2026-09-01 17:22:39'),(136,1015,'SALE','2026-09-02 07:18:03',-1,260.00,260.00,NULL,70,NULL,'2026-09-02 07:18:03','2026-09-02 07:18:03'),(137,1018,'SALE','2026-09-02 21:28:05',-6,34.00,204.00,NULL,71,NULL,'2026-09-02 21:28:05','2026-09-02 21:28:05'),(138,1024,'SALE','2026-09-17 10:19:22',-13,12.00,156.00,NULL,72,NULL,'2026-09-17 10:19:22','2026-09-17 10:19:22'),(139,1025,'SALE','2026-09-17 10:19:22',-6,12.00,72.00,NULL,72,NULL,'2026-09-17 10:19:22','2026-09-17 10:19:22');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cashier',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '1',
  `last_active_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Charls Admin','charls@admin.com','Administrator','active',0,'2026-09-24 18:36:21','2026-06-11 07:37:18','$2y$10$sNCYgvV8Xfz6MBrxDyj9K.OWNl24o/PIX8vzNeJUzBDNrwhqJUBwm','uR1HUlGTSzoCjp5zPNWcjvV2ap5crRnqQKijYOy7LBY8Rrr8sBphD7q4KeNy','2026-06-11 07:37:18','2026-09-24 18:36:21'),(3,'jaygeer varona','jaygeer@sheems.com','Cashier','active',0,'2026-09-02 05:46:47',NULL,'$2y$10$wkPGBJl/C6UcChFWjMwf2uIAU1o77k1GcMTv1E0EEa08rxPYMePu2',NULL,'2026-09-01 05:54:10','2026-09-02 05:46:47'),(4,'benthings','benthings@sheems.com','Cashier','disabled',0,'2026-09-01 18:58:14',NULL,'$2y$10$V1VAZz/NVT0l3YxVd0TyJua1PsppwqsD5OrQWE2FMjns74vy4pgM.',NULL,'2026-09-01 11:05:29','2026-09-01 18:58:14');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-26  0:20:39
