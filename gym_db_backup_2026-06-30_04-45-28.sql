-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: gym_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `checkin_time` datetime DEFAULT current_timestamp(),
  `checkout_time` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `fk_attendance_created_by` (`created_by`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (91,227,'2026-06-29 15:26:52',NULL,NULL,NULL),(92,228,'2026-06-29 15:27:09',NULL,NULL,NULL),(93,228,'2026-06-30 08:39:37','2026-06-30 09:09:46',NULL,NULL),(94,225,'2026-06-30 08:43:19','2026-06-30 09:09:26',NULL,NULL),(95,226,'2026-06-30 09:15:47','2026-06-30 09:19:45',NULL,NULL);
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gym_settings`
--

DROP TABLE IF EXISTS `gym_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gym_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gym_name` varchar(255) NOT NULL DEFAULT 'Gym Management System',
  `logo_path` varchar(255) NOT NULL DEFAULT 'gym logo.jpg',
  `background_path` varchar(255) NOT NULL DEFAULT 'gym background.jpg',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sidebar_theme` varchar(50) NOT NULL DEFAULT 'primary',
  `student_discount_enabled` tinyint(1) DEFAULT 1,
  `per_session_fee` decimal(10,2) DEFAULT 50.00,
  `half_month_fee` decimal(10,2) DEFAULT 300.00,
  `one_month_fee` decimal(10,2) DEFAULT 500.00,
  `two_months_fee` decimal(10,2) DEFAULT 900.00,
  `three_months_fee` decimal(10,2) DEFAULT 1300.00,
  `four_months_fee` decimal(10,2) DEFAULT 1700.00,
  `five_months_fee` decimal(10,2) DEFAULT 2100.00,
  `six_months_fee` decimal(10,2) DEFAULT 2500.00,
  `seven_months_fee` decimal(10,2) DEFAULT 2900.00,
  `eight_months_fee` decimal(10,2) DEFAULT 3300.00,
  `nine_months_fee` decimal(10,2) DEFAULT 3700.00,
  `ten_months_fee` decimal(10,2) DEFAULT 4100.00,
  `eleven_months_fee` decimal(10,2) DEFAULT 4500.00,
  `one_year_fee` decimal(10,2) DEFAULT 5000.00,
  `two_years_fee` decimal(10,2) DEFAULT 9000.00,
  `three_years_fee` decimal(10,2) DEFAULT 13000.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gym_settings`
--

LOCK TABLES `gym_settings` WRITE;
/*!40000 ALTER TABLE `gym_settings` DISABLE KEYS */;
INSERT INTO `gym_settings` VALUES (1,'Olympic Fitness Gym','uploads/gym_logos/gym_logo_1782358597.jpg','uploads/gym_backgrounds/gym_background_1782358608.jpg','2026-06-29 07:24:39','primary',1,55.00,350.00,500.00,900.00,1300.00,1700.00,2100.00,2500.00,2900.00,3300.00,3700.00,4100.00,4500.00,5000.00,9000.00,13000.00);
/*!40000 ALTER TABLE `gym_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_programs`
--

DROP TABLE IF EXISTS `member_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `enrollment_date` date DEFAULT curdate(),
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_member_program` (`member_id`,`program_id`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `member_programs_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_programs_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_programs`
--

LOCK TABLES `member_programs` WRITE;
/*!40000 ALTER TABLE `member_programs` DISABLE KEYS */;
/*!40000 ALTER TABLE `member_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` varchar(50) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `plan` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('ACTIVE','EXPIRED','SUSPENDED') DEFAULT 'ACTIVE',
  `is_student` tinyint(1) DEFAULT 0,
  `student_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_code` (`member_code`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `fk_members_created_by` (`created_by`),
  CONSTRAINT `fk_members_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
INSERT INTO `members` VALUES (225,'MEM20269799E2','test','','','','Half Month','2026-06-29','2026-07-14','ACTIVE',0,'','2026-06-29 05:06:06',1,'qr_codes/8440b1361817725e9a49eb910bc542466116bbaf1deb369827275583a131b1a4.png','8440b1361817725e9a49eb910bc542466116bbaf1deb369827275583a131b1a4'),(226,'MEM20267E666E','test quick',NULL,'',NULL,'1 Month','2026-06-30','2026-07-30','ACTIVE',0,NULL,'2026-06-29 05:06:39',1,'qr_codes/c9612b92292e45ee1be4c64355bac3fffbb6f9914895dd49e93f41f8d82e6a65.png','c9612b92292e45ee1be4c64355bac3fffbb6f9914895dd49e93f41f8d82e6a65'),(227,'MEM202673ACD8','ikai',NULL,'',NULL,'Per Session','2026-06-29','2026-06-29','EXPIRED',0,NULL,'2026-06-29 07:25:11',1,'qr_codes/867de8cbf2953253889b737ee710fc294b1c1a065c2a0ce38e0c879489c634a4.png','867de8cbf2953253889b737ee710fc294b1c1a065c2a0ce38e0c879489c634a4'),(228,'MEM20260655E8','kent','','','','Half Month','2026-06-29','2026-07-14','ACTIVE',0,'','2026-06-29 07:26:05',1,'qr_codes/03abfe831347bcca9dd4dae94a7d6142af1f8b9aff6a32e4c537e1db0179bfe3.png','03abfe831347bcca9dd4dae94a7d6142af1f8b9aff6a32e4c537e1db0179bfe3');
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `is_student_discount` tinyint(1) DEFAULT 0,
  `student_id` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `reference_no` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `member_id` (`member_id`),
  KEY `fk_payments_created_by` (`created_by`),
  CONSTRAINT `fk_payments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (193,225,350.00,'R20260629237BD6','Cash','2026-06-29 00:00:00',NULL,0,'',0.00,'',NULL),(194,226,100.00,'R20260629FDEE38',NULL,'2026-06-29 00:00:00',NULL,0,NULL,0.00,NULL,NULL),(195,227,55.00,'R20260629950095',NULL,'2026-06-29 00:00:00',NULL,0,NULL,0.00,NULL,NULL),(196,228,350.00,'R20260629288126','Maya','2026-06-29 00:00:00',NULL,0,'',0.00,'fd5646',NULL),(197,226,500.00,'R202606307A2D50','Cash','2026-06-30 09:14:55',NULL,0,NULL,0.00,NULL,1);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_items`
--

DROP TABLE IF EXISTS `pos_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pos_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` enum('beverage','snack','supplement','other') NOT NULL DEFAULT 'beverage',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_item_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_items`
--

LOCK TABLES `pos_items` WRITE;
/*!40000 ALTER TABLE `pos_items` DISABLE KEYS */;
INSERT INTO `pos_items` VALUES (1,'Mineral Water (500ml)','beverage',15.00,89,'uploads/products/product_1_1782711063.png',0,'2026-01-26 04:48:46','2026-06-29 07:11:18'),(2,'Mineral Water (1L)','beverage',25.00,33,'uploads/products/product_2_1782717162.png',1,'2026-01-26 04:48:46','2026-06-29 07:12:42'),(3,'Coca-Cola (330ml)','beverage',20.00,69,'uploads/products/product_3_1782715156.webp',1,'2026-01-26 04:48:46','2026-06-29 06:39:16'),(4,'Sprite (330ml)','beverage',20.00,78,NULL,1,'2026-01-26 04:48:46','2026-01-27 06:51:42'),(5,'Red Bull Energy Drink','beverage',85.00,29,NULL,1,'2026-01-26 04:48:46','2026-06-29 07:12:00'),(6,'Monster Energy Drink','beverage',80.00,9,'uploads/products/product_6_1782711093.png',0,'2026-01-26 04:48:46','2026-06-29 07:12:27'),(7,'Protein Bar','snack',45.00,41,'uploads/products/product_7_1782717431.jpg',1,'2026-01-26 04:48:46','2026-06-29 07:17:11'),(8,'Mixed Nuts (100g)','snack',35.00,60,NULL,1,'2026-01-26 04:48:46','2026-01-26 04:48:46'),(9,'Banana','snack',10.00,183,'uploads/products/product_9_1782711148.png',0,'2026-01-26 04:48:46','2026-06-29 07:08:23'),(10,'Apple','snack',15.00,138,'uploads/products/product_10_1782715105.jpg',0,'2026-01-26 04:48:46','2026-06-29 07:08:09'),(11,'Whey Protein (1kg)','supplement',1200.00,0,NULL,1,'2026-01-26 04:48:46','2026-01-27 05:19:55'),(12,'Creatine Monohydrate','supplement',800.00,10,NULL,1,'2026-01-26 04:48:46','2026-04-28 05:38:07'),(13,'Gym Towel','other',50.00,20,NULL,0,'2026-01-26 04:48:46','2026-06-29 06:46:10'),(14,'Lockers Key','other',5.00,49,NULL,1,'2026-01-26 04:48:46','2026-01-27 06:08:43'),(15,'Boiled Eggs','other',20.00,37,NULL,1,'2026-01-26 05:17:16','2026-03-22 04:22:19'),(16,'test','beverage',22.00,2,'uploads/products/product_1782715202_7095a492.jpg',0,'2026-06-29 06:40:02','2026-06-29 07:11:36');
/*!40000 ALTER TABLE `pos_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_sale_items`
--

DROP TABLE IF EXISTS `pos_sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pos_sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `pos_sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pos_sale_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `pos_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_sale_items`
--

LOCK TABLES `pos_sale_items` WRITE;
/*!40000 ALTER TABLE `pos_sale_items` DISABLE KEYS */;
INSERT INTO `pos_sale_items` VALUES (1,1,6,1,80.00,80.00,'2026-01-26 04:51:27'),(2,2,6,1,80.00,80.00,'2026-01-26 04:51:36'),(3,2,4,1,20.00,20.00,'2026-01-26 04:51:36'),(4,2,9,1,10.00,10.00,'2026-01-26 04:51:36'),(5,2,12,1,800.00,800.00,'2026-01-26 04:51:36'),(6,3,10,1,15.00,15.00,'2026-01-26 04:58:28'),(7,3,9,1,10.00,10.00,'2026-01-26 04:58:28'),(8,4,12,1,800.00,800.00,'2026-01-26 05:01:16'),(9,5,12,1,800.00,800.00,'2026-01-26 05:05:39'),(10,6,12,1,800.00,800.00,'2026-01-26 05:08:03'),(11,7,12,1,800.00,800.00,'2026-01-26 05:08:17'),(12,8,9,1,10.00,10.00,'2026-01-26 05:08:45'),(13,8,10,1,15.00,15.00,'2026-01-26 05:08:45'),(14,9,9,1,10.00,10.00,'2026-01-26 05:09:13'),(15,9,10,1,15.00,15.00,'2026-01-26 05:09:13'),(16,10,9,1,10.00,10.00,'2026-01-26 05:10:05'),(17,10,10,1,15.00,15.00,'2026-01-26 05:10:05'),(18,11,9,1,10.00,10.00,'2026-01-26 05:11:48'),(19,11,10,1,15.00,15.00,'2026-01-26 05:11:48'),(20,12,15,6,20.00,120.00,'2026-01-26 05:17:36'),(21,13,15,6,20.00,120.00,'2026-01-26 05:18:45'),(23,15,2,1,25.00,25.00,'2026-01-27 03:02:27'),(24,16,2,1,25.00,25.00,'2026-01-27 03:22:36'),(25,16,3,1,20.00,20.00,'2026-01-27 03:22:36'),(26,17,2,1,25.00,25.00,'2026-01-27 03:22:49'),(27,18,1,1,15.00,15.00,'2026-01-27 03:24:49'),(28,19,10,1,15.00,15.00,'2026-01-27 03:26:10'),(29,20,10,1,15.00,15.00,'2026-01-27 03:26:17'),(30,21,1,1,15.00,15.00,'2026-01-27 03:29:42'),(31,22,1,1,15.00,15.00,'2026-01-27 03:29:51'),(40,31,6,1,80.00,80.00,'2026-01-27 03:51:44'),(41,32,6,1,80.00,80.00,'2026-01-27 03:56:00'),(42,33,9,1,10.00,10.00,'2026-01-27 04:01:28'),(43,34,10,1,15.00,15.00,'2026-01-27 04:07:29'),(44,35,6,1,80.00,80.00,'2026-01-27 05:14:15'),(45,36,6,2,80.00,160.00,'2026-01-27 05:17:18'),(46,37,11,10,1200.00,12000.00,'2026-01-27 05:19:55'),(47,38,9,3,10.00,30.00,'2026-01-27 05:27:29'),(48,39,3,1,20.00,20.00,'2026-01-27 06:01:35'),(49,40,14,1,5.00,5.00,'2026-01-27 06:08:43'),(50,41,4,1,20.00,20.00,'2026-01-27 06:51:42'),(51,41,10,1,15.00,15.00,'2026-01-27 06:51:42'),(52,41,9,1,10.00,10.00,'2026-01-27 06:51:42'),(53,42,6,2,80.00,160.00,'2026-01-27 07:22:55'),(54,43,2,2,25.00,50.00,'2026-01-27 09:16:46'),(55,44,9,1,10.00,10.00,'2026-01-29 06:27:25'),(56,45,1,1,15.00,15.00,'2026-01-29 06:29:44'),(57,45,9,1,10.00,10.00,'2026-01-29 06:29:44'),(58,46,3,1,20.00,20.00,'2026-03-18 03:49:18'),(59,47,3,1,20.00,20.00,'2026-03-18 03:49:27'),(60,48,3,1,20.00,20.00,'2026-03-18 03:49:41'),(61,48,1,1,15.00,15.00,'2026-03-18 03:49:41'),(62,49,3,1,20.00,20.00,'2026-03-18 03:51:39'),(63,49,2,1,25.00,25.00,'2026-03-18 03:51:39'),(64,50,3,2,20.00,40.00,'2026-03-18 03:51:53'),(65,50,1,1,15.00,15.00,'2026-03-18 03:51:53'),(66,50,2,1,25.00,25.00,'2026-03-18 03:51:53'),(67,51,2,1,25.00,25.00,'2026-03-18 03:53:10'),(68,51,3,1,20.00,20.00,'2026-03-18 03:53:10'),(69,52,1,1,15.00,15.00,'2026-03-18 03:53:21'),(70,53,6,1,80.00,80.00,'2026-03-18 03:53:48'),(71,54,2,1,25.00,25.00,'2026-03-22 04:11:36'),(72,54,1,1,15.00,15.00,'2026-03-22 04:11:36'),(73,55,3,1,20.00,20.00,'2026-03-22 04:22:19'),(74,55,15,1,20.00,20.00,'2026-03-22 04:22:19'),(75,56,12,1,800.00,800.00,'2026-04-28 05:38:07'),(76,56,10,1,15.00,15.00,'2026-04-28 05:38:07'),(77,57,10,1,15.00,15.00,'2026-06-25 04:17:44'),(78,57,2,1,25.00,25.00,'2026-06-25 04:17:44'),(79,58,1,1,15.00,15.00,'2026-06-29 04:47:31'),(80,58,2,1,25.00,25.00,'2026-06-29 04:47:31'),(81,58,10,1,15.00,15.00,'2026-06-29 04:47:31'),(82,59,5,1,85.00,85.00,'2026-06-29 07:12:00');
/*!40000 ALTER TABLE `pos_sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_sales`
--

DROP TABLE IF EXISTS `pos_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pos_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','gcash','maya','card') NOT NULL DEFAULT 'cash',
  `reference_no` varchar(50) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `pos_sales_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pos_sales_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_sales`
--

LOCK TABLES `pos_sales` WRITE;
/*!40000 ALTER TABLE `pos_sales` DISABLE KEYS */;
INSERT INTO `pos_sales` VALUES (1,'2026-01-26 04:51:27',80.00,'cash','','','',NULL,1,'2026-01-26 04:51:27'),(2,'2026-01-26 04:51:36',910.00,'cash','','','',NULL,1,'2026-01-26 04:51:36'),(3,'2026-01-26 04:58:28',25.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 04:58:28'),(4,'2026-01-26 05:01:16',800.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:01:16'),(5,'2026-01-26 05:05:39',800.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:05:39'),(6,'2026-01-26 05:08:03',800.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:08:03'),(7,'2026-01-26 05:08:17',800.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:08:17'),(8,'2026-01-26 05:08:45',25.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:08:45'),(9,'2026-01-26 05:09:13',25.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:09:13'),(10,'2026-01-26 05:10:05',25.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:10:05'),(11,'2026-01-26 05:11:48',25.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:11:48'),(12,'2026-01-26 05:17:36',120.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:17:36'),(13,'2026-01-26 05:18:45',120.00,'cash','',NULL,NULL,NULL,1,'2026-01-26 05:18:45'),(15,'2026-01-27 03:02:27',25.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 03:02:27'),(16,'2026-01-27 03:22:36',45.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 03:22:36'),(17,'2026-01-27 03:22:49',25.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 03:22:49'),(18,'2026-01-27 03:24:49',15.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 03:24:49'),(19,'2026-01-27 03:26:10',15.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 03:26:10'),(20,'2026-01-27 03:26:17',15.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 03:26:17'),(21,'2026-01-27 03:29:42',15.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 03:29:42'),(22,'2026-01-27 03:29:51',15.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 03:29:51'),(31,'2026-01-27 03:51:44',80.00,'cash','',NULL,NULL,NULL,1,'2026-01-27 03:51:44'),(32,'2026-01-27 03:56:00',80.00,'cash','',NULL,NULL,NULL,1,'2026-01-27 03:56:00'),(33,'2026-01-27 04:01:28',10.00,'cash','',NULL,NULL,NULL,1,'2026-01-27 04:01:28'),(34,'2026-01-27 04:07:29',15.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 04:07:29'),(35,'2026-01-27 05:14:15',80.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 05:14:15'),(36,'2026-01-27 05:17:18',160.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 05:17:18'),(37,'2026-01-27 05:19:55',12000.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 05:19:55'),(38,'2026-01-27 05:27:29',30.00,'cash','',NULL,NULL,NULL,2,'2026-01-27 05:27:29'),(39,'2026-01-27 06:01:35',20.00,'gcash','500',NULL,NULL,NULL,1,'2026-01-27 06:01:35'),(40,'2026-01-27 06:08:43',5.00,'gcash','555',NULL,NULL,NULL,1,'2026-01-27 06:08:43'),(41,'2026-01-27 06:51:42',45.00,'cash','',NULL,NULL,NULL,1,'2026-01-27 06:51:42'),(42,'2026-01-27 07:22:55',160.00,'cash','',NULL,NULL,NULL,8,'2026-01-27 07:22:55'),(43,'2026-01-27 09:16:46',50.00,'gcash','6644',NULL,NULL,NULL,1,'2026-01-27 09:16:46'),(44,'2026-01-29 06:27:25',10.00,'cash','',NULL,NULL,NULL,1,'2026-01-29 06:27:25'),(45,'2026-01-29 06:29:44',25.00,'cash','',NULL,NULL,NULL,2,'2026-01-29 06:29:44'),(46,'2026-03-18 03:49:18',20.00,'cash','',NULL,NULL,NULL,1,'2026-03-18 03:49:18'),(47,'2026-03-18 03:49:27',20.00,'cash','',NULL,NULL,NULL,1,'2026-03-18 03:49:27'),(48,'2026-03-18 03:49:41',35.00,'cash','',NULL,NULL,NULL,1,'2026-03-18 03:49:41'),(49,'2026-03-18 03:51:39',45.00,'cash','',NULL,NULL,NULL,1,'2026-03-18 03:51:39'),(50,'2026-03-18 03:51:53',80.00,'cash','',NULL,NULL,NULL,1,'2026-03-18 03:51:53'),(51,'2026-03-18 03:53:10',45.00,'cash','',NULL,NULL,NULL,1,'2026-03-18 03:53:10'),(52,'2026-03-18 03:53:21',15.00,'cash','',NULL,NULL,NULL,1,'2026-03-18 03:53:21'),(53,'2026-03-18 03:53:48',80.00,'cash','',NULL,NULL,NULL,1,'2026-03-18 03:53:48'),(54,'2026-03-22 04:11:36',40.00,'cash','',NULL,NULL,NULL,1,'2026-03-22 04:11:36'),(55,'2026-03-22 04:22:19',40.00,'cash','',NULL,NULL,NULL,2,'2026-03-22 04:22:19'),(56,'2026-04-28 05:38:07',815.00,'cash','',NULL,NULL,NULL,1,'2026-04-28 05:38:07'),(57,'2026-06-25 04:17:44',40.00,'cash','',NULL,NULL,NULL,2,'2026-06-25 04:17:44'),(58,'2026-06-29 04:47:31',55.00,'cash','',NULL,NULL,NULL,1,'2026-06-29 04:47:31'),(59,'2026-06-29 07:12:00',85.00,'cash','',NULL,NULL,NULL,1,'2026-06-29 07:12:00');
/*!40000 ALTER TABLE `pos_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('fitness','supplement','other') NOT NULL DEFAULT 'fitness',
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_program_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'Boxing Training','Professional boxing training sessions','fitness',1500.00,30,1,'2026-01-26 04:17:05','2026-01-26 04:17:05'),(2,'Zumba Classes','Fun dance fitness classes','fitness',800.00,30,1,'2026-01-26 04:17:05','2026-01-26 04:17:05'),(3,'Protein Supplements','High-quality protein supplements','supplement',2500.00,NULL,1,'2026-01-26 04:17:05','2026-01-26 04:17:05'),(4,'General Gym Membership','Access to all gym facilities','fitness',1200.00,30,1,'2026-01-26 04:17:05','2026-01-26 04:17:05'),(5,'Per Session','Pay per gym session','fitness',50.00,1,1,'2026-01-26 04:17:05','2026-01-26 04:17:05');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`),
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_settings`
--

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (1,2,'hide_recent_sales','1','2026-01-27 03:02:49','2026-01-27 03:02:49');
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `on_duty` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Gym Administrator','admin','$2y$10$z0JNe43ik6B65LxzXVZTveM5vQJDEVrmvrf1CzAiXcJR6k3yK9hK6','admin','2026-01-14 05:43:14',1),(2,'Gym Cashier','cashier','$2y$10$mNWq/LEmbhESmkANUdX4juSPg49P3/c3IWhdxlL0SVjcadI80tBOG','cashier','2026-01-14 05:43:14',1),(8,'ate cashier','ate','$2y$10$/RhcgkJR7w8fy4C7.4QBv.rj9uni/zqKDzhRxWPaf9hrjkKdFyysW','cashier','2026-01-27 07:17:18',1);
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

-- Dump completed on 2026-06-30 10:45:29