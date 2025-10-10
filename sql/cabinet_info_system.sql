-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: cabinet_info_system
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cabinets`
--

DROP TABLE IF EXISTS `cabinets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cabinets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cabinet_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qr_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cabinet_number` (`cabinet_number`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cabinets`
--

LOCK TABLES `cabinets` WRITE;
/*!40000 ALTER TABLE `cabinets` DISABLE KEYS */;
INSERT INTO `cabinets` VALUES (1,'CAB20250001','Cabinet 1','uploads/1757641698_Cabinet 1.jpg','qrcodes/cabinet_CAB20250001.png','2025-09-10 06:59:19','2025-09-23 12:20:10'),(2,'CAB20250002','Cabinet 2','uploads/1757640475_Cabinet 2.jpg','qrcodes/cabinet_CAB20250002.png','2025-09-10 07:08:55','2025-09-24 07:05:43'),(3,'CAB20250003','Cabinet 3','uploads/1757640485_Cabinet 3.jpg','qrcodes/cabinet_CAB20250003.png','2025-09-10 07:12:09','2025-09-23 12:20:10'),(4,'CAB20250004','Cabinet 4','uploads/1757640430_Cabinet 4.jpg','qrcodes/cabinet_CAB20250004.png','2025-09-10 07:23:35','2025-09-23 12:20:10'),(5,'CAB20250005','Cabinet 5','uploads/1757641922_Cabinet 5.jpg','qrcodes/cabinet_CAB20250005.png','2025-09-12 01:52:02','2025-09-23 12:20:10'),(6,'CAB20250006','Cabinet 6',NULL,'qrcodes/cabinet_CAB20250006.png','2025-09-23 11:58:30','2025-09-29 07:03:33'),(7,'CAB20250007','Cabinet 7','uploads/1757642233_Cabinet 7.jpg','qrcodes/cabinet_CAB20250007.png','2025-09-12 01:57:13','2025-09-23 12:20:10'),(8,'CAB20250008','Cabinet 8','uploads/1757655666_Cabinet 8.jpg','qrcodes/cabinet_CAB20250008.png','2025-09-12 02:02:43','2025-09-23 12:20:10'),(9,'CAB20250009','Cabinet 9','uploads/1757913206_Cabinet 9.jpg','qrcodes/cabinet_CAB20250009.png','2025-09-12 03:56:33','2025-09-23 12:20:10'),(10,'CAB20250010','Cabinet 10','uploads/1757649558_Cabinet 10.jpg','qrcodes/cabinet_CAB20250010.png','2025-09-12 03:59:18','2025-09-23 12:20:10'),(11,'CAB20250011','Cabinet 11',NULL,'qrcodes/cabinet_CAB20250011.png','2025-09-23 11:59:50','2025-09-29 07:05:51'),(12,'CAB20250012','Cabinet 12','uploads/1757649662_Cabinet 12.jpg','qrcodes/cabinet_CAB20250012.png','2025-09-12 04:01:02','2025-09-23 12:20:10'),(13,'CAB20250013','Cabinet 13',NULL,'qrcodes/cabinet_CAB20250013.png','2025-09-12 06:57:14','2025-09-23 12:20:10'),(14,'CAB20250014','Cabinet 14','uploads/1758628317_Cabinet 14.jpg','qrcodes/cabinet_CAB20250014.png','2025-09-23 11:49:21','2025-09-29 07:03:53'),(15,'CAB20250015','Cabinet 15','uploads/1758676275_Cabinet 15.jpg','qrcodes/cabinet_CAB20250015.png','2025-09-24 01:11:15','2025-09-29 07:03:58'),(16,'CAB20250016','Cabinet 16',NULL,'qrcodes/cabinet_CAB20250016.png','2025-09-24 01:13:24','2025-09-29 07:04:02'),(17,'CAB20250017','Cabinet 17','uploads/1758677316_Cabinet 17.jpg','qrcodes/cabinet_CAB20250017.png','2025-09-24 01:28:36','2025-09-29 07:04:06'),(18,'CAB20250018','Cabinet 18','uploads/1758677554_Cabinet 18.jpg','qrcodes/cabinet_CAB20250018.png','2025-09-24 01:32:34','2025-09-29 07:04:10'),(19,'CAB20250019','Cabinet 19',NULL,'qrcodes/cabinet_CAB20250019.png','2025-09-24 01:38:43','2025-09-29 07:05:17'),(20,'CAB20250020','Cabinet 20',NULL,'qrcodes/cabinet_CAB20250020.png','2025-09-24 01:40:20','2025-09-29 07:04:14'),(21,'CAB20250021','Cabinet 21','uploads/1758678199_Cabinet 21.jpg','qrcodes/cabinet_CAB20250021.png','2025-09-24 01:43:20','2025-09-29 07:04:17'),(22,'CAB20250022','Cabinet 22',NULL,'qrcodes/cabinet_CAB20250022.png','2025-09-24 01:44:23','2025-09-29 07:04:21'),(23,'CAB20250023','Cabinet 23','uploads/1758678332_Cabinet 23.jpg','qrcodes/cabinet_CAB20250023.png','2025-09-24 01:45:32','2025-09-29 07:04:24'),(24,'CAB20250024','Cabinet 24',NULL,'qrcodes/cabinet_CAB20250024.png','2025-09-24 01:46:45','2025-09-29 07:04:28'),(25,'CAB20250025','Cabinet 25','uploads/1758678483_Cabinet 25.jpg','qrcodes/cabinet_CAB20250025.png','2025-09-24 01:48:03','2025-09-29 07:04:31'),(26,'CAB20250026','Cabinet 26',NULL,'qrcodes/cabinet_CAB20250026.png','2025-09-24 01:48:46','2025-09-29 07:04:34'),(27,'CAB20250027','Cabinet 27',NULL,'qrcodes/cabinet_CAB20250027.png','2025-09-24 01:49:50','2025-09-29 07:04:37'),(28,'CAB20250028','Cabinet 28','uploads/1758678641_Cabinet 28.jpg','qrcodes/cabinet_CAB20250028.png','2025-09-24 01:50:41','2025-09-29 07:04:40'),(29,'CAB20250029','Cabinet 29','uploads/1758678726_Cabinet 29.jpg','qrcodes/cabinet_CAB20250029.png','2025-09-24 01:52:06','2025-09-29 07:04:44'),(30,'CAB20250030','Cabinet 30','uploads/1758678847_Cabinet 30.jpg','qrcodes/cabinet_CAB20250030.png','2025-09-24 01:54:07','2025-09-29 07:04:47'),(31,'CAB20250031','Cabinet 31','uploads/1758679009_Cabinet 31.jpg','qrcodes/cabinet_CAB20250031.png','2025-09-24 01:56:49','2025-09-29 07:04:50'),(32,'CAB20250032','Cabinet 32','uploads/1758679481_Cabinet 32.jpg','qrcodes/cabinet_CAB20250032.png','2025-09-24 02:04:41','2025-09-29 07:04:53'),(33,'CAB20250033','Cabinet 33','uploads/1758680231_Cabinet 33.jpg','qrcodes/cabinet_CAB20250033.png','2025-09-24 02:17:11','2025-09-29 07:04:56'),(34,'CAB20250034','Cabinet 34','uploads/1758680505_Cabinet 34.jpg','qrcodes/cabinet_CAB20250034.png','2025-09-24 02:21:45','2025-09-29 07:05:00'),(35,'CAB20250035','Cabinet 35','uploads/1758680781_Cabinet 35.jpg','qrcodes/cabinet_CAB20250035.png','2025-09-24 02:26:21','2025-09-29 07:05:03'),(36,'CAB20250036','Cabinet 36','uploads/1758680951_Cabinet 36.jpg','qrcodes/cabinet_CAB20250036.png','2025-09-24 02:29:11','2025-09-29 07:05:06'),(37,'CAB20250037','Cabinet 37','uploads/1758681008_Cabinet 37.jpg','qrcodes/cabinet_CAB20250037.png','2025-09-24 02:30:08','2025-09-24 15:46:09'),(38,'CAB20250038','Cabinet 38','uploads/1758681082_Cabinet 38.jpg','qrcodes/cabinet_CAB20250038.png','2025-09-24 02:31:22','2025-09-29 07:05:23'),(39,'CAB20250039','Cabinet 39','uploads/1758681115_Cabinet 39.jpg','qrcodes/cabinet_CAB20250039.png','2025-09-24 02:31:55','2025-09-29 07:05:13'),(40,'CAB20250040','Cabinet 40',NULL,'qrcodes/cabinet_CAB20250040.png','2025-09-24 05:33:01','2025-09-29 07:05:09'),(41,'CAB20250041','Cabinet 41',NULL,'qrcodes/cabinet_CAB20250041.png','2025-09-27 05:17:12','2025-09-29 07:05:31'),(42,'CAB20250042','Cabinet 42',NULL,'qrcodes/cabinet_CAB20250042.png','2025-09-29 01:52:18','2025-09-29 06:39:49');
/*!40000 ALTER TABLE `cabinets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Files'),(2,'Supplies');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cabinet_id` int NOT NULL,
  `category_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cabinet_id` (`cabinet_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `items_ibfk_1` FOREIGN KEY (`cabinet_id`) REFERENCES `cabinets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=301 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (1,1,1,'Memorandum',1,'2025-09-23 12:26:59','2025-09-23 12:26:59'),(2,1,1,'Document about schools',1,'2025-09-23 12:26:59','2025-09-23 12:26:59'),(3,1,1,'Referral Slips / List of Assignments',1,'2025-09-23 12:26:59','2025-09-23 12:26:59'),(4,1,1,'Letters (to Persons)',1,'2025-09-23 12:26:59','2025-09-23 12:26:59'),(5,1,1,'DepEd Order',1,'2025-09-23 12:26:59','2025-09-23 12:26:59'),(6,1,1,'Recent Files (2020-2023)',1,'2025-09-23 12:26:59','2025-09-23 12:26:59'),(7,1,1,'Basic Education Data',1,'2025-09-23 12:26:59','2025-09-23 12:26:59'),(8,1,1,'Research',1,'2025-09-23 12:26:59','2025-09-23 12:26:59'),(9,1,1,'Regional Memorandum/Addendum',1,'2025-09-23 12:26:59','2025-09-23 12:26:59'),(19,3,2,'Batteries',1,'2025-09-23 12:30:30','2025-09-23 12:30:30'),(20,3,2,'Bond Papers',1,'2025-09-23 12:30:30','2025-09-23 12:30:30'),(21,3,2,'Staples',1,'2025-09-23 12:30:30','2025-09-23 12:30:30'),(22,3,2,'Books',1,'2025-09-23 12:30:30','2025-09-23 12:30:30'),(23,3,2,'Storage Files',1,'2025-09-23 12:30:30','2025-09-23 12:30:30'),(24,3,1,'Basic Education Data',1,'2025-09-23 12:30:30','2025-09-23 12:30:30'),(25,3,1,'Elementary Public &amp; Private',1,'2025-09-23 12:30:30','2025-09-23 12:30:30'),(26,4,1,'Advisory Memorandum Regional Letter (March - Present 2018)',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(27,4,1,'Memorandum Advisories Regional Letters Unnumbered Memo(July - Dec 2018)',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(28,4,1,'IPCRF 2018',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(29,4,1,'Project Proposals (Purchase Request for BAC Action)',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(30,4,1,'Early Registration Report(DATA)',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(31,4,1,'Memorandum Advisories Regional Letters Unnumbered Memo(July - Dec 2019)',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(32,4,1,'2019 Miscelleneous Files',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(33,4,1,'Miscelleneous CY 2020',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(34,4,1,'Files CY 2020',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(35,4,1,'Directory Report Data 2020',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(36,4,1,'Recent Files/Data',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(37,4,1,'DepEd Advisory Letters/Notice of Meeting',1,'2025-09-24 00:30:07','2025-09-24 00:30:07'),(38,5,2,'Bond Papers (A3L &amp; Legal Sizes)',1,'2025-09-24 00:32:23','2025-09-24 00:32:23'),(39,5,2,'Photo Papers',1,'2025-09-24 00:32:23','2025-09-24 00:32:23'),(40,5,2,'Masking Tape',1,'2025-09-24 00:32:23','2025-09-24 00:32:23'),(41,5,2,'Double Sided Tape',1,'2025-09-24 00:32:23','2025-09-24 00:32:23'),(42,5,2,'Jumbo and Small Paper Clips',1,'2025-09-24 00:32:23','2025-09-24 00:32:23'),(43,5,2,'Binder Clips',1,'2025-09-24 00:32:23','2025-09-24 00:32:23'),(44,5,2,'Staples',1,'2025-09-24 00:32:23','2025-09-24 00:32:23'),(45,5,2,'Air Fresheners',1,'2025-09-24 00:32:23','2025-09-24 00:32:23'),(46,5,2,'Steno Notebooks',1,'2025-09-24 00:32:23','2025-09-24 00:32:23'),(47,5,2,'Storage Box',1,'2025-09-24 00:32:24','2025-09-24 00:32:24'),(48,5,2,'Blades',1,'2025-09-24 00:32:24','2025-09-24 00:32:24'),(49,5,2,'Ballpens',1,'2025-09-24 00:32:24','2025-09-24 00:32:24'),(50,7,1,'Archiving',1,'2025-09-24 00:57:35','2025-09-24 00:57:35'),(51,7,1,'Implementation Monitoring',1,'2025-09-24 00:57:35','2025-09-24 00:57:35'),(52,7,1,'Dissemination of Research Result',1,'2025-09-24 00:57:35','2025-09-24 00:57:35'),(53,8,1,'Research File Holders',16,'2025-09-24 00:58:35','2025-09-24 00:58:35'),(54,9,1,'DECS Office Directory',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(55,9,1,'Directory Officials (2004-2011)',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(56,9,1,'NEAT / NSAT Files (1999-2015)',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(57,9,1,'Letters to School Divisions',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(58,9,1,'Reports / Halth',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(59,9,1,'Budget / OR',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(60,9,1,'Letters to Persons',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(61,9,1,'UDGS Files (1997-2002)',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(62,9,1,'BEIS-PI',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(63,9,1,'Orientation of Central Office and Regional Core Teams',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(64,9,1,'National Action Plan',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(65,9,1,'IQRs',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(66,9,1,'DepEd National Planning Conference',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(67,9,1,'Census',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(68,9,2,'Magazines / CDs',1,'2025-09-24 01:02:28','2025-09-24 01:02:28'),(69,10,2,'Certificates',1,'2025-09-24 01:04:05','2025-09-24 01:04:05'),(70,10,2,'Rugs',1,'2025-09-24 01:04:05','2025-09-24 01:04:05'),(71,10,2,'Brown Envelope',1,'2025-09-24 01:04:05','2025-09-24 01:04:05'),(72,10,2,'Boxes',1,'2025-09-24 01:04:05','2025-09-24 01:04:05'),(73,10,2,'Folders',1,'2025-09-24 01:04:05','2025-09-24 01:04:05'),(74,10,2,'Manuscripts',1,'2025-09-24 01:04:05','2025-09-24 01:04:05'),(75,10,2,'Staples',1,'2025-09-24 01:04:05','2025-09-24 01:04:05'),(76,10,2,'Chlorine Dioxide',1,'2025-09-24 01:04:05','2025-09-24 01:04:05'),(77,12,2,'Catridges for Printers',1,'2025-09-24 01:05:43','2025-09-24 01:05:43'),(78,12,2,'HP Invent Box',1,'2025-09-24 01:05:43','2025-09-24 01:05:43'),(79,12,2,'PVC Name Badges',1,'2025-09-24 01:05:43','2025-09-24 01:05:43'),(80,12,2,'Steno Notebooks',1,'2025-09-24 01:05:43','2025-09-24 01:05:43'),(81,12,2,'Toner Kits for Printers',1,'2025-09-24 01:05:43','2025-09-24 01:05:43'),(82,12,2,'Bookpapers',1,'2025-09-24 01:05:43','2025-09-24 01:05:43'),(83,13,1,'Caloocan GESP',1,'2025-09-24 01:07:17','2025-09-24 01:07:17'),(84,13,1,'Las Pinas',1,'2025-09-24 01:07:17','2025-09-24 01:07:17'),(85,13,1,'Makati GESP',1,'2025-09-24 01:07:17','2025-09-24 01:07:17'),(86,13,1,'Malabon GESP',1,'2025-09-24 01:07:17','2025-09-24 01:07:17'),(87,13,1,'Malabon / Navotas GESP',1,'2025-09-24 01:07:17','2025-09-24 01:07:17'),(88,13,1,'Mandaluyong GESP',1,'2025-09-24 01:07:17','2025-09-24 01:07:17'),(89,13,1,'Manila GESP',1,'2025-09-24 01:07:17','2025-09-24 01:07:17'),(90,14,1,'Quezon City / Manila',1,'2025-09-24 01:09:46','2025-09-24 01:09:46'),(91,14,1,'Quezon City (12)',1,'2025-09-24 01:09:46','2025-09-24 01:09:46'),(92,14,1,'Pasay / Manila GESP',1,'2025-09-24 01:09:46','2025-09-24 01:09:46'),(93,14,1,'Makati GESP',1,'2025-09-24 01:09:46','2025-09-24 01:09:46'),(94,14,1,'Quezon City GESP (3)',3,'2025-09-24 01:09:46','2025-09-24 01:09:46'),(95,14,1,'Taguig / Pateros GESP (3)',3,'2025-09-24 01:09:46','2025-09-24 01:09:46'),(96,14,1,'Valenzuela GESP (7)',7,'2025-09-24 01:09:46','2025-09-24 01:09:46'),(97,15,1,'Annual Reports',1,'2025-09-24 01:11:15','2025-09-24 01:24:50'),(98,15,1,'Permanent Documents',1,'2025-09-24 01:11:15','2025-09-24 01:24:50'),(99,16,2,'Box (School Supplies)',1,'2025-09-24 01:13:24','2025-09-24 01:24:50'),(100,16,2,'Monitor',1,'2025-09-24 01:13:24','2025-09-24 01:24:50'),(101,16,2,'Envelops',1,'2025-09-24 01:13:24','2025-09-24 01:24:50'),(102,16,2,'Samsung Cyan Toner',1,'2025-09-24 01:13:24','2025-09-24 01:24:50'),(103,16,2,'Samsung Yellow Toner',1,'2025-09-24 01:13:24','2025-09-24 01:24:50'),(104,16,2,'Bag',1,'2025-09-24 01:13:24','2025-09-24 01:24:50'),(105,17,1,'Manila GESP (5)',5,'2025-09-24 01:28:36','2025-09-24 01:28:36'),(106,17,1,'Marikina GESP (4)',4,'2025-09-24 01:28:36','2025-09-24 01:28:36'),(107,17,1,'Muntinlupa GESP (3)',3,'2025-09-24 01:28:36','2025-09-24 01:28:36'),(108,17,1,'Paranaque GESP (3)',3,'2025-09-24 01:28:36','2025-09-24 01:28:36'),(109,17,1,'Pasay GESP (3)',3,'2025-09-24 01:28:36','2025-09-24 01:28:36'),(110,17,1,'Pasig / San Juan GESP (5)',5,'2025-09-24 01:28:36','2025-09-24 01:28:36'),(111,18,1,'BEIS-SSM Valenzuela City, BEIS-SSM Taguig City (2)',2,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(112,18,1,'BEIS-San Juan City, BEIS-SSM Caloocan City',1,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(113,18,1,'BEIS-Las Piñas City, BEIS-SSM Makati City',1,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(114,18,1,'Project Proposal, Research Priorities',1,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(115,18,1,'Training workshop output, BEIS-SSM Malabon / Navotas City(2)',2,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(116,18,1,'BEIS-SSM Mandaluyong City(2), BEIS-SSM Manila City',2,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(117,18,1,'BEIS-SSM Marikina City, BEIS-SSM Muntinlupa City',1,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(118,18,1,'BEIS-SSM Parañaque City, BEIS-SSM Pasay City',1,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(119,18,1,'BEIS-SSM Pasig City(2) / San Juan City',2,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(120,18,1,'BEIS-SSM Pateros City, BEIS-SSM Quezon City(3)',3,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(121,18,1,'BEIS 2002-2011',1,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(122,18,1,'E-BEIS 2011-2015',1,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(123,18,1,'BEIS performance indicators 2003-2008',1,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(124,18,1,'Master list and Annual Reports',1,'2025-09-24 01:32:34','2025-09-24 01:32:34'),(139,20,2,'Box',1,'2025-09-24 01:40:20','2025-09-24 01:40:20'),(140,20,2,'Bag',1,'2025-09-24 01:40:20','2025-09-24 01:40:20'),(141,20,2,'CD-R',1,'2025-09-24 01:40:21','2025-09-24 01:40:21'),(142,20,2,'CD-R',1,'2025-09-24 01:40:21','2025-09-24 01:40:21'),(143,21,2,'Box',1,'2025-09-24 01:43:20','2025-09-24 01:43:20'),(144,21,2,'School supplies',1,'2025-09-24 01:43:20','2025-09-24 01:43:20'),(145,21,2,'Envelopes',1,'2025-09-24 01:43:20','2025-09-24 01:43:20'),(146,21,2,'Cartolina',1,'2025-09-24 01:43:20','2025-09-24 01:43:20'),(147,21,2,'Paper',1,'2025-09-24 01:43:20','2025-09-24 01:43:20'),(148,21,2,'Hard copier paper A3',1,'2025-09-24 01:43:20','2025-09-24 01:43:20'),(149,21,2,'Floppy disks',1,'2025-09-24 01:43:20','2025-09-24 01:43:20'),(150,21,2,'Folders',1,'2025-09-24 01:43:20','2025-09-24 01:43:20'),(151,21,2,'Bag',1,'2025-09-24 01:43:20','2025-09-24 01:43:20'),(152,22,2,'Laminating film',1,'2025-09-24 01:44:23','2025-09-24 01:44:23'),(153,22,2,'Comb binding machine',1,'2025-09-24 01:44:23','2025-09-24 01:44:23'),(154,22,2,'Box',1,'2025-09-24 01:44:23','2025-09-24 01:44:23'),(155,23,2,'Papers',1,'2025-09-24 01:45:32','2025-09-24 01:45:32'),(156,23,2,'Folder',1,'2025-09-24 01:45:32','2025-09-24 01:45:32'),(157,23,2,'Binders',1,'2025-09-24 01:45:32','2025-09-24 01:45:32'),(158,24,2,'Box',1,'2025-09-24 01:46:45','2025-09-24 01:46:45'),(159,24,2,'Portable boombox',1,'2025-09-24 01:46:45','2025-09-24 01:46:45'),(160,24,2,'Straw rope',1,'2025-09-24 01:46:45','2025-09-24 01:46:45'),(161,24,2,'Letters (to Persons)',1,'2025-09-24 01:46:45','2025-09-24 01:46:45'),(162,25,1,'Ruby Anniversary and World teachers day 2015 books',1,'2025-09-24 01:48:03','2025-09-24 01:48:03'),(163,25,1,'NSP-C NCR',1,'2025-09-24 01:48:03','2025-09-24 01:48:03'),(164,25,1,'2015 Regional planning issuance 2021-2023 papers',1,'2025-09-24 01:48:03','2025-09-24 01:48:03'),(165,26,2,'Storage',1,'2025-09-24 01:48:46','2025-09-24 01:48:46'),(166,27,2,'Binders',1,'2025-09-24 01:49:50','2025-09-24 01:49:50'),(167,27,2,'Folders',1,'2025-09-24 01:49:50','2025-09-24 01:49:50'),(168,27,2,'Papers',1,'2025-09-24 01:49:50','2025-09-24 01:49:50'),(169,27,2,'Box',1,'2025-09-24 01:49:50','2025-09-24 01:49:50'),(170,28,2,'Storage',1,'2025-09-24 01:50:41','2025-09-24 01:50:41'),(171,29,2,'Folders',1,'2025-09-24 01:52:06','2025-09-24 01:52:06'),(172,29,2,'Envelopes',1,'2025-09-24 01:52:06','2025-09-24 01:52:06'),(173,29,2,'Binder',1,'2025-09-24 01:52:06','2025-09-24 01:52:06'),(174,29,2,'Books',1,'2025-09-24 01:52:06','2025-09-24 01:52:06'),(175,29,2,'Folder',1,'2025-09-24 01:52:06','2025-09-24 01:52:06'),(176,29,2,'Ribbon',1,'2025-09-24 01:52:06','2025-09-24 01:52:06'),(177,30,1,'ISO 9001:2015 - QMS DepEd NCR',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(178,30,2,'Fan',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(179,30,2,'Folders',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(180,30,2,'Books',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(181,30,2,'Paper',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(182,30,2,'Envelopes',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(183,30,2,'Advance Oslo Paper 250 sheets',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(184,30,2,'Hard copy Copier paper',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(185,30,2,'Papers',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(186,30,2,'Notebooks',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(187,30,2,'e. Paper 500 sheets',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(188,30,2,'Hard copy legal paper 500 sheets',1,'2025-09-24 01:54:07','2025-09-24 01:54:07'),(189,31,2,'Papers',1,'2025-09-24 01:56:49','2025-09-24 01:56:49'),(190,31,2,'Envelopes',1,'2025-09-24 01:56:49','2025-09-24 01:56:49'),(191,31,2,'Cartolina',1,'2025-09-24 01:56:49','2025-09-24 01:56:49'),(192,31,2,'Bag',1,'2025-09-24 01:56:49','2025-09-24 01:56:49'),(193,31,2,'Papers',1,'2025-09-24 01:56:49','2025-09-24 01:56:49'),(194,31,2,'Folders',1,'2025-09-24 01:56:49','2025-09-24 01:56:49'),(195,31,2,'Handbook',1,'2025-09-24 01:56:49','2025-09-24 01:56:49'),(196,31,2,'Whiteboard eraser',1,'2025-09-24 01:56:49','2025-09-24 01:56:49'),(197,31,2,'Stetson hat',1,'2025-09-24 01:56:49','2025-09-24 01:56:49'),(198,32,1,'Compilations of prayers',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(199,32,1,'Statistical data 2006-2007',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(200,32,1,'Basic education curriculum 2002',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(201,32,1,'Statistical document EFA assessment 2000',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(202,32,1,'Special education data 1999-2000',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(203,32,1,'Orientation workshop on revised BEIS forms',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(204,32,1,'Analysis of teacher, instructional rooms',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(205,32,1,'And school furniture needs 2003-2006',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(206,32,1,'Medium term philippine development plan 1999-2004',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(207,32,1,'Educational Management Information System (EMIS)',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(208,32,1,'Analysis of teacher and classroom needs',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(209,32,1,'Call for research papers for publications (Manuscript) 2016-2017',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(210,32,1,'Learning continuity plan',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(211,32,1,'Attendance and feedback sheet',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(212,32,1,'Serving agreement',1,'2025-09-24 02:04:41','2025-09-24 02:04:41'),(213,33,2,'Bags',1,'2025-09-24 02:17:11','2025-09-24 02:17:11'),(214,33,2,'Box',1,'2025-09-24 02:17:11','2025-09-24 02:17:11'),(215,33,2,'Envelopes',1,'2025-09-24 02:17:11','2025-09-24 02:17:11'),(216,33,2,'Books',1,'2025-09-24 02:17:11','2025-09-24 02:17:11'),(217,33,2,'Binders',1,'2025-09-24 02:17:11','2025-09-24 02:17:11'),(218,33,1,'Annual Report 2014',1,'2025-09-24 02:17:11','2025-09-24 02:17:11'),(219,33,2,'Led lights',1,'2025-09-24 02:17:11','2025-09-24 02:17:11'),(220,33,2,'ID Cases',1,'2025-09-24 02:17:11','2025-09-24 02:17:11'),(221,34,1,'Certificates',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(222,34,1,'Directory of public schools 1999-2002',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(223,34,1,'List of receipient elementary schools with computer lab as of December 2011',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(224,34,1,'School improvement plan 2005-2007',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(225,34,1,'Master-list of private elementary and secondary schools 1999-2005',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(226,34,1,'Enrollment data (2)',2,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(227,34,1,'SPED school and pupil data',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(228,34,1,'Log books (2)',2,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(229,34,1,'Consolidated report of grade 2 &amp; grade 8',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(230,34,1,'Learning continuity plan',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(231,34,1,'CY project school improvement plan 2007-2011',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(232,34,1,'School improvement plan 2007-2011',1,'2025-09-24 02:21:45','2025-09-24 02:21:45'),(233,35,2,'Binders',1,'2025-09-24 02:26:21','2025-09-24 02:26:21'),(234,35,2,'Folders',1,'2025-09-24 02:26:21','2025-09-24 02:26:21'),(235,35,2,'Papers',1,'2025-09-24 02:26:21','2025-09-24 02:26:21'),(236,35,1,'PPRD files 2018-2019',1,'2025-09-24 02:26:21','2025-09-24 02:26:21'),(237,35,1,'Results of NCBTS-TSNA, 2016 Physical plan',1,'2025-09-24 02:26:21','2025-09-24 02:26:21'),(238,35,1,'Private pre elementary and elementary schools permit/recognition',1,'2025-09-24 02:26:21','2025-09-24 02:26:21'),(239,35,1,'K12 Transition in private schools',1,'2025-09-24 02:26:21','2025-09-24 02:26:21'),(240,35,1,'Enrollment in public/private secondary schools',1,'2025-09-24 02:26:21','2025-09-24 02:26:21'),(241,36,2,'Cups',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(242,36,2,'Tupperware',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(243,36,2,'Plates',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(244,36,2,'Tumbler',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(245,36,2,'Ice Molds',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(246,36,2,'Wooden Coffee Stirrer',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(247,36,2,'Plastic straws',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(248,36,2,'Plates',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(249,36,2,'Glasses',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(250,36,2,'Containers',1,'2025-09-24 02:29:11','2025-09-24 02:29:11'),(265,40,1,'NA (Edit if Available)',1,'2025-09-24 06:34:49','2025-09-24 06:34:49'),(266,40,1,'NA (Edit if Available)',1,'2025-09-24 06:34:49','2025-09-24 06:34:49'),(267,40,1,'NA (Edit if Available)',1,'2025-09-24 06:34:49','2025-09-24 06:34:49'),(268,39,2,'Storage',1,'2025-09-24 06:35:20','2025-09-24 06:35:20'),(269,19,1,'1993-2006',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(270,19,1,'Consolidated report on Enrollment 2000-2001',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(271,19,1,'Data SPED,National Career Assessment exam 2007-2010',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(272,19,1,'Enrollment data of private elementary and secondary schools',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(273,19,1,'Enrollment data of public elementary school 1998-2002',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(274,19,1,'Governance4 and field Operations Strand Updates 2022',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(275,19,1,'Mancom, CLMD, Physical Plan, Financial Obligation, Expenses',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(276,19,1,'Number of Classes and class size 2003-2005',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(277,19,1,'Office Performance Evaluation System (OPES) Reference Table',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(278,19,1,'Oplan balik eskewla 2006-2015',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(279,19,1,'Oplan balik eskwela 2012 Command reference',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(280,19,1,'Public elementary and secondary schools enrollment1998-2002',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(281,19,1,'Regional Calendar of activities 2007, DEPED NCR Directory OR',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(282,19,1,'School and officials National Training of Trainers (Trainer&#039;s Kit)',1,'2025-09-24 06:39:34','2025-09-24 06:39:34'),(283,38,1,'Folders',1,'2025-09-24 06:39:58','2025-09-24 06:39:58'),(284,38,1,'Papers',1,'2025-09-24 06:39:58','2025-09-24 06:39:58'),(285,38,2,'Binder',1,'2025-09-24 06:39:58','2025-09-24 06:39:58'),(286,38,2,'Books',1,'2025-09-24 06:39:58','2025-09-24 06:39:58'),(287,38,2,'Envelopes',1,'2025-09-24 06:39:58','2025-09-24 06:39:58'),(288,37,2,'Biscuit',1,'2025-09-24 06:43:00','2025-09-24 06:43:00'),(289,37,2,'Envelopes',1,'2025-09-24 06:43:00','2025-09-24 06:43:00'),(290,37,2,'Folders',1,'2025-09-24 06:43:00','2025-09-24 06:43:00'),(291,37,2,'Papers',1,'2025-09-24 06:43:00','2025-09-24 06:43:00'),(292,2,2,'Adventure plastic envelopes',1,'2025-09-24 07:05:43','2025-09-24 07:05:43'),(293,2,2,'Clear Book',1,'2025-09-24 07:05:43','2025-09-24 07:05:43'),(294,2,2,'Colored Plastic Envelopes',1,'2025-09-24 07:05:43','2025-09-24 07:05:43'),(295,2,2,'Everyready Batteries',1,'2025-09-24 07:05:43','2025-09-24 07:05:43'),(296,2,2,'ID Case',1,'2025-09-24 07:05:43','2025-09-24 07:05:43'),(297,2,2,'ID Lace',1,'2025-09-24 07:05:43','2025-09-24 07:05:43'),(298,2,2,'Kit Bag',1,'2025-09-24 07:05:43','2025-09-24 07:05:43'),(299,2,2,'Stapler Wire',1,'2025-09-24 07:05:43','2025-09-24 07:05:43'),(300,2,2,'Steno advanced notebooks',1,'2025-09-24 07:05:43','2025-09-24 07:05:43');
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES (1,2,'b13f2e7c5b30e90834217c907995a1eace667388938310e9f2bee2dece96a99b','2025-09-29 06:18:39',1,'2025-09-29 05:18:39');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pin_change_history`
--

DROP TABLE IF EXISTS `pin_change_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pin_change_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` enum('admin','encoder') COLLATE utf8mb4_general_ci NOT NULL,
  `old_pin_hash` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `new_pin_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `changed_by` int NOT NULL,
  `change_reason` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_changed_by` (`changed_by`),
  KEY `idx_changed_at` (`changed_at` DESC),
  CONSTRAINT `fk_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pin_change_history`
--

LOCK TABLES `pin_change_history` WRITE;
/*!40000 ALTER TABLE `pin_change_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `pin_change_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_pins`
--

DROP TABLE IF EXISTS `system_pins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_pins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` enum('admin','encoder') COLLATE utf8mb4_general_ci NOT NULL,
  `pin` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Hashed PIN for security',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role` (`role`),
  KEY `fk_created_by` (`created_by`),
  KEY `idx_role` (`role`),
  CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_pins`
--

LOCK TABLES `system_pins` WRITE;
/*!40000 ALTER TABLE `system_pins` DISABLE KEYS */;
INSERT INTO `system_pins` VALUES (1,'admin','$2y$10$WGoN5zU1icqYh9KKrwZykeJg7lEdz01DI.wUhIRCealhmE5eblcBa',NULL,'2025-09-30 01:17:29','2025-09-30 02:18:34'),(2,'encoder','$2y$10$7SF8afKihQmCHimkpKQt7eaUchGbRt/8hpfqAAuXEqgX8.5jC/CaW',NULL,'2025-09-30 01:17:29','2025-09-30 01:53:09');
/*!40000 ALTER TABLE `system_pins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `office` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `division` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `role` enum('admin','encoder') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System','Administrator','PPRD','Admin','admin@example.com','1234567890','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,'admin','2025-09-10 02:51:19'),(2,'Mico','Intertas','PPRD','NCR','intertas.mico.dichoso@gmail.com','09098874204','Oshinobi','$2y$10$y4uJkcJrJ7xFcSKkeHv6A.tLkAGVnriM2owwtXSXAR325J3jOAeDa','2025-09-29 05:30:17','encoder','2025-09-27 06:04:37');
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

-- Dump completed on 2025-10-06  8:46:10
