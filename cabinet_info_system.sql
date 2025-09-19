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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cabinets`
--

LOCK TABLES `cabinets` WRITE;
/*!40000 ALTER TABLE `cabinets` DISABLE KEYS */;
INSERT INTO `cabinets` VALUES (1,'CAB20250001','Cabinet 1','uploads/1757641698_Cabinet 1.jpg','qrcodes/cabinet_CAB20250001.png','2025-09-10 06:59:19','2025-09-12 01:48:18'),(2,'CAB20250002','Cabinet 2','uploads/1757640475_Cabinet 2.jpg','qrcodes/cabinet_CAB20250002.png','2025-09-10 07:08:55','2025-09-12 01:27:55'),(3,'CAB20250003','Cabinet 3','uploads/1757640485_Cabinet 3.jpg','qrcodes/cabinet_CAB20250003.png','2025-09-10 07:12:09','2025-09-12 01:28:05'),(4,'CAB20250004','Cabinet 4','uploads/1757640430_Cabinet 4.jpg','qrcodes/cabinet_CAB20250004.png','2025-09-10 07:23:35','2025-09-12 01:27:10'),(5,'CAB20250005','Cabinet 5','uploads/1757641922_Cabinet 5.jpg','qrcodes/cabinet_CAB20250005.png','2025-09-12 01:52:02','2025-09-15 06:14:04'),(6,'CAB20250007','Cabinet 7','uploads/1757642233_Cabinet 7.jpg','qrcodes/cabinet_CAB20250007.png','2025-09-12 01:57:13','2025-09-15 06:15:54'),(7,'CAB20250008','Cabinet 8','uploads/1757655666_Cabinet 8.jpg','qrcodes/cabinet_CAB20250008.png','2025-09-12 02:02:43','2025-09-15 06:09:00'),(8,'CAB20250009','Cabinet 9','uploads/1757913206_Cabinet 9.jpg','qrcodes/cabinet_CAB20250009.png','2025-09-12 03:56:33','2025-09-15 05:13:26'),(9,'CAB20250010','Cabinet 10','uploads/1757649558_Cabinet 10.jpg','qrcodes/cabinet_CAB20250010.png','2025-09-12 03:59:18','2025-09-15 06:13:41'),(10,'CAB20250012','Cabinet 12','uploads/1757649662_Cabinet 12.jpg','qrcodes/cabinet_CAB20250012.png','2025-09-12 04:01:02','2025-09-15 06:09:16'),(11,'CAB20250013','Cabinet 13',NULL,'qrcodes/cabinet_CAB20250013.png','2025-09-12 06:57:14','2025-09-15 05:55:22');
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
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (1,4,1,'2019 Miscellaneous Files',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(2,4,1,'Advisory Memorandum Regional Letter (March - Present 2018)',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(3,4,1,'DepEd Advisory Letters/Notice of Meeting',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(4,4,1,'Directory Report Data 2020',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(5,4,1,'Early Registration Report (DATA)',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(6,4,1,'Files CY 2020',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(7,4,1,'IPCRF 2018',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(8,4,1,'Memorandum Advisories Regional Letters Unnumbered Memo (July - Dec 2018)',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(9,4,1,'Memorandum Advisories Regional Letters Unnumbered Memo (July - Dec 2019)',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(10,4,1,'Miscellaneous CY 2020',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(11,4,1,'Project Proposals (Purchase Request for BAC Action)',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(12,4,1,'Recent Files/Data',1,'2025-09-12 01:27:10','2025-09-12 01:27:10'),(13,2,2,'Adventure Plastic Envelopes',1,'2025-09-12 01:27:55','2025-09-12 01:27:55'),(14,2,2,'Clear Book',1,'2025-09-12 01:27:55','2025-09-12 01:27:55'),(15,2,2,'Colored Plastic Envelopes',1,'2025-09-12 01:27:55','2025-09-12 01:27:55'),(16,2,2,'Everyready Batteries',1,'2025-09-12 01:27:55','2025-09-12 01:27:55'),(17,2,2,'ID Case',1,'2025-09-12 01:27:55','2025-09-12 01:27:55'),(18,2,2,'ID Lace',1,'2025-09-12 01:27:55','2025-09-12 01:27:55'),(19,2,2,'Kit Bag',1,'2025-09-12 01:27:55','2025-09-12 01:27:55'),(20,2,2,'Stapler Wire',1,'2025-09-12 01:27:55','2025-09-12 01:27:55'),(21,2,2,'Steno Advanced Notebooks',1,'2025-09-12 01:27:55','2025-09-12 01:27:55'),(22,3,1,'Basic Education Data',1,'2025-09-12 01:28:05','2025-09-12 01:28:05'),(23,3,1,'Elementary Public&Private',1,'2025-09-12 01:28:05','2025-09-12 01:28:05'),(24,3,2,'Batteries',1,'2025-09-12 01:28:05','2025-09-12 01:28:05'),(25,3,2,'Bond Papers',1,'2025-09-12 01:28:05','2025-09-12 01:28:05'),(26,3,2,'Books',1,'2025-09-12 01:28:05','2025-09-12 01:28:05'),(27,3,2,'Staples',1,'2025-09-12 01:28:05','2025-09-12 01:28:05'),(28,3,2,'Storage Files',1,'2025-09-12 01:28:05','2025-09-12 01:28:05'),(29,1,1,'Basic Education Data',1,'2025-09-12 01:48:18','2025-09-12 01:48:18'),(30,1,1,'DepEd Order',1,'2025-09-12 01:48:18','2025-09-12 01:48:18'),(31,1,1,'Documents about schools',1,'2025-09-12 01:48:18','2025-09-12 01:48:18'),(32,1,1,'Letter (to Persons)',1,'2025-09-12 01:48:18','2025-09-12 01:48:18'),(33,1,1,'Memorandum',1,'2025-09-12 01:48:18','2025-09-12 01:48:18'),(34,1,1,'Recent Files (2020-2023)',1,'2025-09-12 01:48:18','2025-09-12 01:48:18'),(35,1,1,'Referral Slips/List of Assignments',1,'2025-09-12 01:48:18','2025-09-12 01:48:18'),(36,1,1,'Regional Memorandum/Addendum',1,'2025-09-12 01:48:18','2025-09-12 01:48:18'),(37,1,1,'Research',1,'2025-09-12 01:48:18','2025-09-12 01:48:18'),(38,5,2,'Bond Papers (A3 & Legal Sizes)',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(39,5,2,'Photo Papers',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(40,5,2,'Masking Tape',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(41,5,2,'Double Sided Tape',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(42,5,2,'Jumbo and Small Paper Clips',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(43,5,2,'Binder Clips',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(44,5,2,'Staples',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(45,5,2,'Air Fresheners',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(46,5,2,'Steno Notebooks',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(47,5,2,'Storage Box',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(48,5,2,'Blades',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(49,5,2,'Ballpens',1,'2025-09-12 01:52:02','2025-09-12 01:52:02'),(50,6,1,'Archiving',1,'2025-09-12 01:57:13','2025-09-12 01:57:13'),(51,6,1,'Implementation Monitoring',1,'2025-09-12 01:57:13','2025-09-12 01:57:13'),(52,6,1,'Dissemination of Research Result',1,'2025-09-12 01:57:13','2025-09-12 01:57:13'),(68,9,2,'Certificates',1,'2025-09-12 03:59:18','2025-09-12 03:59:18'),(69,9,2,'Rugs',1,'2025-09-12 03:59:18','2025-09-12 03:59:18'),(70,9,2,'Brown Envelope',1,'2025-09-12 03:59:18','2025-09-12 03:59:18'),(71,9,2,'Boxes',1,'2025-09-12 03:59:18','2025-09-12 03:59:18'),(72,9,2,'Folders',1,'2025-09-12 03:59:18','2025-09-12 03:59:18'),(73,9,2,'Manuscripts',1,'2025-09-12 03:59:18','2025-09-12 03:59:18'),(74,9,2,'Staples',1,'2025-09-12 03:59:18','2025-09-12 03:59:18'),(75,9,2,'Chlorine Dioxide',1,'2025-09-12 03:59:18','2025-09-12 03:59:18'),(76,10,2,'Cartridges for Printers',1,'2025-09-12 04:01:02','2025-09-12 04:01:02'),(77,10,2,'HP Invent Box',1,'2025-09-12 04:01:02','2025-09-12 04:01:02'),(78,10,2,'PVC Name Badges',1,'2025-09-12 04:01:02','2025-09-12 04:01:02'),(79,10,2,'Steno Notebooks',1,'2025-09-12 04:01:02','2025-09-12 04:01:02'),(80,10,2,'Toner Kits for Printers',1,'2025-09-12 04:01:02','2025-09-12 04:01:02'),(81,10,2,'Bookpapers',1,'2025-09-12 04:01:02','2025-09-12 04:01:02'),(82,7,1,'Research File Holders',16,'2025-09-12 05:41:06','2025-09-12 05:41:06'),(83,11,1,'Caloocan GESP',1,'2025-09-12 06:57:14','2025-09-12 06:57:14'),(84,11,1,'Las Pinas',1,'2025-09-12 06:57:14','2025-09-12 06:57:14'),(85,11,1,'Makati GESP',1,'2025-09-12 06:57:14','2025-09-12 06:57:14'),(86,11,1,'Malabon GESP',1,'2025-09-12 06:57:14','2025-09-12 06:57:14'),(87,11,1,'Malabon / Navotas GESP',1,'2025-09-12 06:57:14','2025-09-12 06:57:14'),(88,11,1,'Mandaluyong GESP',1,'2025-09-12 06:57:14','2025-09-12 06:57:14'),(89,11,1,'Manila GESP',1,'2025-09-12 06:57:14','2025-09-12 06:57:14'),(105,8,1,'BEIS-PI',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(106,8,1,'Budget/OR',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(107,8,1,'Census',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(108,8,1,'DECS Office Directory',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(109,8,1,'DepEd National Planning Conference',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(110,8,1,'Directory Officials (2004-2011)',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(111,8,1,'IQRs',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(112,8,1,'Letter to School Divisions',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(113,8,1,'Letters to Persons',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(114,8,1,'National Action Plan',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(115,8,1,'NEAT/INSAT Files (1999-2015)',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(116,8,1,'Orientation of Central Office and Regional Core Teams',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(117,8,1,'Reports/Halth',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(118,8,1,'UDGS Files (1997-2002)',1,'2025-09-15 05:13:26','2025-09-15 05:13:26'),(119,8,2,'Magazines/CDs',1,'2025-09-15 05:13:26','2025-09-15 05:13:26');
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
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
INSERT INTO `users` VALUES (1,'System','Administrator','IT','Admin','admin@example.com','1234567890','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,'admin','2025-09-10 02:51:19'),(2,'Mico','Intertas','PPRD','NCR','intertas.mico.dichoso@gmail.com','09098874204','Oshinobi','$2y$10$5DiDrui.apDX8atNgSA7S.SP24hs8GuTvrOT3G3Cu4KDM0rsy/W6y','2025-09-17 06:59:24','encoder','2025-09-16 07:42:01');
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

-- Dump completed on 2025-09-19 10:53:56
