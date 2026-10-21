-- MySQL dump 10.13  Distrib 8.0.35, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: guro_assist
-- ------------------------------------------------------
-- Server version	8.0.35-0ubuntu0.22.04.1

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
-- Table structure for table `academic_years`
--

DROP TABLE IF EXISTS `academic_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_years`
--

LOCK TABLES `academic_years` WRITE;
/*!40000 ALTER TABLE `academic_years` DISABLE KEYS */;
INSERT INTO `academic_years` VALUES (1,'2026-2027','2026-06-01','2027-03-31',1,'2026-09-22 11:52:33','2026-10-09 08:57:35');
/*!40000 ALTER TABLE `academic_years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_submissions`
--

DROP TABLE IF EXISTS `assignment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submitted_date` date DEFAULT NULL,
  `feedback` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assignment_submissions_assignment_id_student_id_unique` (`assignment_id`,`student_id`),
  KEY `assignment_submissions_recorded_by_foreign` (`recorded_by`),
  KEY `assignment_submissions_student_id_index` (`student_id`),
  CONSTRAINT `assignment_submissions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_submissions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assignment_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_submissions`
--

LOCK TABLES `assignment_submissions` WRITE;
/*!40000 ALTER TABLE `assignment_submissions` DISABLE KEYS */;
INSERT INTO `assignment_submissions` VALUES (32,6,46,'excused',NULL,'excused',1,'2026-10-12 13:06:57','2026-10-12 13:07:15'),(34,6,49,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(35,6,34,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(36,6,41,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(37,6,45,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(38,6,35,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(39,6,48,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(40,6,37,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(41,6,36,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(42,6,43,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(43,6,44,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(44,6,42,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(45,6,40,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(46,6,47,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(47,6,38,'submitted','2026-10-12',NULL,1,'2026-10-12 13:06:57','2026-10-12 13:06:57'),(48,7,46,'submitted','2026-10-11',NULL,1,'2026-10-12 13:09:58','2026-10-12 13:09:58'),(49,6,39,'late','2026-10-14',NULL,1,'2026-10-14 09:56:53','2026-10-14 09:56:53'),(51,7,49,'late','2026-10-14',NULL,1,'2026-10-14 09:57:16','2026-10-14 09:57:16'),(52,8,49,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(53,8,34,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(54,8,41,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(55,8,45,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(56,8,35,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(57,8,48,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(58,8,37,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(59,8,36,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(60,8,43,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(61,8,44,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(62,8,42,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(63,8,40,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(64,8,47,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44'),(65,8,38,'late','2026-10-17',NULL,1,'2026-10-17 10:21:22','2026-10-17 10:22:44');
/*!40000 ALTER TABLE `assignment_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_assignment_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `assigned_date` date NOT NULL,
  `due_date` date NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignments_created_by_foreign` (`created_by`),
  KEY `assignments_class_assignment_id_due_date_index` (`class_assignment_id`,`due_date`),
  CONSTRAINT `assignments_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
INSERT INTO `assignments` VALUES (1,8,'Assignment 1','Write a short traditional Filipino poem or 3-line haiku celebrating nature, family (pamilya), or local community life using proper rhyme and meter.','2026-10-12','2026-10-14',1,'2026-10-12 09:31:24','2026-10-12 10:12:32','2026-10-12 10:12:32'),(2,8,'Pagbasa','this is test only','2026-10-09','2026-10-11',1,'2026-10-12 09:52:12','2026-10-12 09:54:02','2026-10-12 09:54:02'),(3,8,'Tula','Create tula','2026-10-12','2026-10-13',1,'2026-10-12 10:13:05','2026-10-12 10:21:38','2026-10-12 10:21:38'),(4,8,'Tula','test','2026-10-10','2026-10-11',1,'2026-10-12 10:21:56','2026-10-12 12:31:39','2026-10-12 12:31:39'),(5,8,'Tula','sdf','2026-10-10','2026-10-11',1,'2026-10-12 12:29:27','2026-10-12 12:30:54',NULL),(6,12,'Grammar and Vocabulary','Identify the Parts of Speech','2026-10-10','2026-10-12',1,'2026-10-12 13:04:16','2026-10-12 13:06:28',NULL),(7,12,'Practical Writing',NULL,'2026-10-10','2026-10-11',1,'2026-10-12 13:08:49','2026-10-12 13:08:49',NULL),(8,12,'English','sdfsdfsdf','2026-10-01','2026-10-09',1,'2026-10-17 10:20:56','2026-10-17 10:22:44',NULL);
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_assignment_id` bigint unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_unique` (`student_id`,`class_assignment_id`,`attendance_date`),
  KEY `attendance_recorded_by_foreign` (`recorded_by`),
  KEY `attendance_attendance_date_index` (`attendance_date`),
  KEY `attendance_class_assignment_id_attendance_date_index` (`class_assignment_id`,`attendance_date`),
  CONSTRAINT `attendance_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=275 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (2,4,2,'2026-10-03','present',NULL,1,'2026-10-03 06:30:17','2026-10-03 06:38:25'),(5,2,1,'2026-10-01','present',NULL,1,'2026-10-03 06:39:21','2026-10-03 06:39:35'),(12,2,1,'2026-10-02','excused',NULL,1,'2026-10-03 06:43:43','2026-10-03 06:43:43'),(40,6,1,'2026-10-03','late',NULL,1,'2026-10-03 07:18:31','2026-10-03 07:31:43'),(41,5,1,'2026-10-03','absent',NULL,1,'2026-10-03 07:18:31','2026-10-03 07:31:43'),(42,2,1,'2026-10-03','absent',NULL,1,'2026-10-03 07:18:31','2026-10-03 07:31:43'),(44,7,1,'2026-10-07','present',NULL,1,'2026-10-07 12:38:04','2026-10-10 04:54:26'),(45,5,1,'2026-10-07','late',NULL,1,'2026-10-07 12:38:04','2026-10-10 04:54:26'),(46,2,1,'2026-10-07','late',NULL,1,'2026-10-07 12:38:04','2026-10-10 04:54:26'),(47,7,2,'2026-10-07','excused',NULL,1,'2026-10-07 12:56:54','2026-10-07 12:56:54'),(48,7,1,'2026-10-05','present',NULL,1,'2026-10-07 22:28:11','2026-10-07 22:28:11'),(49,5,1,'2026-10-05','present',NULL,1,'2026-10-07 22:28:11','2026-10-07 22:28:11'),(50,2,1,'2026-10-05','present',NULL,1,'2026-10-07 22:28:11','2026-10-07 22:28:11'),(51,15,3,'2026-10-09','present',NULL,1,'2026-10-09 09:16:57','2026-10-09 09:16:57'),(52,11,2,'2026-10-09','late',NULL,1,'2026-10-09 09:17:06','2026-10-09 09:17:06'),(53,7,2,'2026-10-09','late',NULL,1,'2026-10-09 09:17:06','2026-10-09 09:17:06'),(54,5,2,'2026-10-09','late',NULL,1,'2026-10-09 09:17:06','2026-10-09 09:17:06'),(55,11,2,'2026-10-08','excused',NULL,1,'2026-10-09 09:17:12','2026-10-09 09:17:12'),(56,7,2,'2026-10-08','excused',NULL,1,'2026-10-09 09:17:12','2026-10-09 09:17:12'),(57,5,2,'2026-10-08','excused',NULL,1,'2026-10-09 09:17:12','2026-10-09 09:17:12'),(70,11,1,'2026-10-07','absent',NULL,1,'2026-10-10 04:54:26','2026-10-10 04:54:26'),(74,11,1,'2026-10-08','absent',NULL,1,'2026-10-10 04:54:30','2026-10-10 11:13:04'),(75,11,1,'2026-10-09','absent',NULL,1,'2026-10-10 04:54:35','2026-10-10 11:13:11'),(76,15,3,'2026-10-10','present',NULL,1,'2026-10-10 07:39:13','2026-10-10 07:39:13'),(77,7,1,'2026-10-08','absent',NULL,1,'2026-10-10 11:13:04','2026-10-10 11:13:04'),(79,7,1,'2026-10-09','present',NULL,1,'2026-10-10 11:13:11','2026-10-10 11:13:11'),(81,11,1,'2030-01-01','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(82,11,1,'2030-01-02','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(83,11,1,'2030-01-03','late',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(84,11,1,'2030-01-04','absent',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(85,11,1,'2030-01-05','absent',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(86,11,1,'2030-01-06','absent',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(87,7,1,'2030-01-01','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(88,7,1,'2030-01-02','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(89,7,1,'2030-01-03','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(90,7,1,'2030-01-04','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(91,7,1,'2030-01-05','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(92,7,1,'2030-01-06','excused','Medical',2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(93,5,1,'2030-01-02','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(109,11,1,'2026-10-06','present',NULL,1,'2026-10-10 16:08:38','2026-10-10 16:08:38'),(110,11,1,'2026-10-10','present',NULL,1,'2026-10-10 16:13:00','2026-10-10 16:13:00'),(163,46,12,'2026-10-12','absent',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(164,39,12,'2026-10-12','absent',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(165,49,12,'2026-10-12','absent',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(166,34,12,'2026-10-12','absent',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(167,41,12,'2026-10-12','late',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(168,45,12,'2026-10-12','late',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(169,35,12,'2026-10-12','late',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(170,48,12,'2026-10-12','present',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(171,37,12,'2026-10-12','present',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(172,36,12,'2026-10-12','present',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(173,43,12,'2026-10-12','present',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(174,44,12,'2026-10-12','present',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(175,42,12,'2026-10-12','present',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(176,40,12,'2026-10-12','present',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(177,47,12,'2026-10-12','present',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(178,38,12,'2026-10-12','present',NULL,1,'2026-10-12 13:01:04','2026-10-12 13:01:04'),(179,46,12,'2026-10-13','absent',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(180,39,12,'2026-10-13','absent',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(181,49,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(182,34,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(183,41,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(184,45,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(185,35,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(186,48,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(187,37,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(188,36,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(189,43,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(190,44,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(191,42,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(192,40,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(193,47,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(194,38,12,'2026-10-13','present',NULL,1,'2026-10-17 10:18:19','2026-10-17 10:18:19'),(195,46,12,'2026-10-14','absent',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(196,39,12,'2026-10-14','absent',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(197,49,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(198,34,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(199,41,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(200,45,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(201,35,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(202,48,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(203,37,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(204,36,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(205,43,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(206,44,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(207,42,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(208,40,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(209,47,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(210,38,12,'2026-10-14','present',NULL,1,'2026-10-17 10:18:31','2026-10-17 10:18:31'),(211,46,12,'2026-10-15','absent',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(212,39,12,'2026-10-15','absent',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(213,49,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(214,34,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(215,41,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(216,45,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(217,35,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(218,48,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(219,37,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(220,36,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(221,43,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(222,44,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(223,42,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(224,40,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(225,47,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(226,38,12,'2026-10-15','present',NULL,1,'2026-10-17 10:18:39','2026-10-17 10:18:39'),(227,46,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(228,39,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(229,49,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(230,34,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(231,41,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(232,45,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(233,35,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(234,48,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(235,37,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(236,36,12,'2026-10-19','absent',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(237,43,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(238,44,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(239,42,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(240,40,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(241,47,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(242,38,12,'2026-10-19','present',NULL,1,'2026-10-19 14:56:46','2026-10-19 14:56:46'),(243,46,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(244,39,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(245,49,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(246,34,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(247,41,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(248,45,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(249,35,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(250,48,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(251,37,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(252,36,12,'2026-10-18','absent',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(253,43,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(254,44,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(255,42,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(256,40,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(257,47,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(258,38,12,'2026-10-18','present',NULL,1,'2026-10-19 14:58:07','2026-10-19 14:58:07'),(259,46,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(260,39,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(261,49,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(262,34,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(263,41,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(264,45,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(265,35,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(266,48,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(267,37,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(268,36,12,'2026-10-17','absent',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(269,43,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(270,44,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(271,42,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(272,40,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(273,47,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26'),(274,38,12,'2026-10-17','present',NULL,1,'2026-10-19 14:58:26','2026-10-19 14:58:26');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_notifications`
--

DROP TABLE IF EXISTS `attendance_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_assignment_id` bigint unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `error` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_notice_unique` (`student_id`,`class_assignment_id`,`attendance_date`,`status`),
  KEY `attendance_notifications_class_assignment_id_foreign` (`class_assignment_id`),
  CONSTRAINT `attendance_notifications_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_notifications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_notifications`
--

LOCK TABLES `attendance_notifications` WRITE;
/*!40000 ALTER TABLE `attendance_notifications` DISABLE KEYS */;
INSERT INTO `attendance_notifications` VALUES (13,11,1,'2026-10-11','absent','sent',NULL,'2026-10-11 03:23:07','2026-10-11 03:23:05','2026-10-11 03:23:07'),(14,7,1,'2026-10-11','absent','sent',NULL,'2026-10-11 03:23:08','2026-10-11 03:23:07','2026-10-11 03:23:08'),(15,5,1,'2026-10-11','late','sent',NULL,'2026-10-11 03:23:09','2026-10-11 03:23:08','2026-10-11 03:23:09'),(16,2,1,'2026-10-11','late','sent',NULL,'2026-10-11 03:23:09','2026-10-11 03:23:09','2026-10-11 03:23:09'),(22,49,12,'2026-10-12','absent','sent',NULL,'2026-10-12 13:01:22','2026-10-12 13:01:04','2026-10-12 13:01:22'),(23,35,12,'2026-10-12','late','sent',NULL,'2026-10-12 13:01:22','2026-10-12 13:01:22','2026-10-12 13:01:22'),(24,36,12,'2026-10-19','absent','sent',NULL,'2026-10-19 14:56:51','2026-10-19 14:56:46','2026-10-19 14:56:51'),(25,36,12,'2026-10-18','absent','sent',NULL,'2026-10-19 14:58:09','2026-10-19 14:58:07','2026-10-19 14:58:09'),(26,36,12,'2026-10-17','absent','sent',NULL,'2026-10-19 14:58:28','2026-10-19 14:58:26','2026-10-19 14:58:28');
/*!40000 ALTER TABLE `attendance_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `auditable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_id` bigint unsigned NOT NULL,
  `event` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=296 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (77,1,'App\\Models\\Enrollment',24,'deleted','{\"student_id\": 11, \"class_assignment_id\": 3}',NULL,'192.168.10.1','2026-10-09 08:59:38'),(78,1,'App\\Models\\Enrollment',27,'created',NULL,'{\"student_id\": 14, \"class_assignment_id\": 3}','192.168.10.1','2026-10-09 09:00:54'),(79,1,'App\\Models\\Enrollment',27,'deleted','{\"student_id\": 14, \"class_assignment_id\": 3}',NULL,'192.168.10.1','2026-10-09 09:01:17'),(80,1,'App\\Models\\ClassAssignment',3,'exported',NULL,'{\"students\": 0}','192.168.10.1','2026-10-09 09:13:16'),(81,1,'App\\Models\\Student',15,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"78944\", \"class_assignment_id\": 3}','192.168.10.1','2026-10-09 09:14:04'),(82,1,'App\\Models\\Enrollment',28,'created',NULL,'{\"student_id\": 15, \"class_assignment_id\": 3}','192.168.10.1','2026-10-09 09:14:04'),(83,1,'App\\Models\\Enrollment',29,'created',NULL,'{\"student_id\": 7, \"class_assignment_id\": 2}','192.168.10.1','2026-10-09 09:15:15'),(84,1,'App\\Models\\ClassAssignment',2,'exported',NULL,'{\"students\": 3}','192.168.10.1','2026-10-09 09:16:32'),(85,1,'App\\Models\\Enrollment',30,'created',NULL,'{\"student_id\": 16, \"class_assignment_id\": 3}','192.168.10.1','2026-10-11 03:30:01'),(86,1,'App\\Models\\Enrollment',31,'created',NULL,'{\"student_id\": 17, \"class_assignment_id\": 7}','192.168.10.1','2026-10-11 04:28:38'),(87,1,'App\\Models\\Enrollment',32,'created',NULL,'{\"student_id\": 18, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 09:29:06'),(88,1,'App\\Models\\Assignment',1,'created',NULL,'{\"title\": \"Assignment 1\", \"due_date\": \"2026-10-14\", \"assigned_date\": \"2026-10-12\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 09:31:24'),(89,1,'App\\Models\\AssignmentSubmission',1,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 18, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": true}','192.168.10.1','2026-10-12 09:49:40'),(90,1,'App\\Models\\Assignment',2,'created',NULL,'{\"title\": \"Pagbasa\", \"due_date\": \"2026-10-11\", \"assigned_date\": \"2026-10-09\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 09:52:12'),(91,1,'App\\Models\\Assignment',2,'updated','{\"title\": \"Pagbasa\", \"due_date\": \"2026-10-11\", \"assigned_date\": \"2026-10-09\", \"class_assignment_id\": 8}','{\"title\": \"Pagbasa\", \"due_date\": \"2026-10-13\", \"assigned_date\": \"2026-10-12\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 09:53:05'),(92,1,'App\\Models\\Assignment',2,'updated','{\"title\": \"Pagbasa\", \"due_date\": \"2026-10-13\", \"assigned_date\": \"2026-10-12\", \"class_assignment_id\": 8}','{\"title\": \"Pagbasa\", \"due_date\": \"2026-10-11\", \"assigned_date\": \"2026-10-09\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 09:53:28'),(93,1,'App\\Models\\Assignment',2,'deleted','{\"title\": \"Pagbasa\", \"due_date\": \"2026-10-11\", \"assigned_date\": \"2026-10-09\", \"class_assignment_id\": 8}',NULL,'192.168.10.1','2026-10-12 09:54:02'),(94,1,'App\\Models\\Enrollment',32,'deleted','{\"student_id\": 18, \"class_assignment_id\": 8}',NULL,'192.168.10.1','2026-10-12 10:11:25'),(95,1,'App\\Models\\Student',19,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0001\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(96,1,'App\\Models\\Student',20,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0002\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(97,1,'App\\Models\\Student',21,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0003\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(98,1,'App\\Models\\Student',22,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0004\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(99,1,'App\\Models\\Student',23,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0005\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(100,1,'App\\Models\\Student',24,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0006\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(101,1,'App\\Models\\Student',25,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0007\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(102,1,'App\\Models\\Student',26,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0008\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(103,1,'App\\Models\\Student',27,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0009\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(104,1,'App\\Models\\Student',28,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0010\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(105,1,'App\\Models\\Student',29,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0011\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(106,1,'App\\Models\\Student',30,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0012\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(107,1,'App\\Models\\Student',31,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0013\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(108,1,'App\\Models\\Student',32,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0014\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(109,1,'App\\Models\\Student',33,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0015\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:34'),(110,1,'App\\Models\\Enrollment',33,'created',NULL,'{\"student_id\": 19, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(111,1,'App\\Models\\Enrollment',34,'created',NULL,'{\"student_id\": 20, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(112,1,'App\\Models\\Enrollment',35,'created',NULL,'{\"student_id\": 21, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(113,1,'App\\Models\\Enrollment',36,'created',NULL,'{\"student_id\": 22, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(114,1,'App\\Models\\Enrollment',37,'created',NULL,'{\"student_id\": 23, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(115,1,'App\\Models\\Enrollment',38,'created',NULL,'{\"student_id\": 24, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(116,1,'App\\Models\\Enrollment',39,'created',NULL,'{\"student_id\": 25, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(117,1,'App\\Models\\Enrollment',40,'created',NULL,'{\"student_id\": 26, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(118,1,'App\\Models\\Enrollment',41,'created',NULL,'{\"student_id\": 27, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(119,1,'App\\Models\\Enrollment',42,'created',NULL,'{\"student_id\": 28, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(120,1,'App\\Models\\Enrollment',43,'created',NULL,'{\"student_id\": 29, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(121,1,'App\\Models\\Enrollment',44,'created',NULL,'{\"student_id\": 30, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(122,1,'App\\Models\\Enrollment',45,'created',NULL,'{\"student_id\": 31, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(123,1,'App\\Models\\Enrollment',46,'created',NULL,'{\"student_id\": 32, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(124,1,'App\\Models\\Enrollment',47,'created',NULL,'{\"student_id\": 33, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:11:35'),(125,1,'App\\Models\\Assignment',1,'deleted','{\"title\": \"Assignment 1\", \"due_date\": \"2026-10-14\", \"assigned_date\": \"2026-10-12\", \"class_assignment_id\": 8}',NULL,'192.168.10.1','2026-10-12 10:12:32'),(126,1,'App\\Models\\Assignment',3,'created',NULL,'{\"title\": \"Tula\", \"due_date\": \"2026-10-13\", \"assigned_date\": \"2026-10-12\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:13:05'),(127,1,'App\\Models\\AssignmentSubmission',2,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 31, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(128,1,'App\\Models\\AssignmentSubmission',3,'created',NULL,'{\"status\": \"excused\", \"student_id\": 24, \"submitted_date\": null, \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(129,1,'App\\Models\\AssignmentSubmission',4,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 19, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(130,1,'App\\Models\\AssignmentSubmission',5,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 26, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(131,1,'App\\Models\\AssignmentSubmission',6,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 30, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(132,1,'App\\Models\\AssignmentSubmission',7,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 20, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(133,1,'App\\Models\\AssignmentSubmission',8,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 33, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(134,1,'App\\Models\\AssignmentSubmission',9,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 22, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(135,1,'App\\Models\\AssignmentSubmission',10,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 21, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(136,1,'App\\Models\\AssignmentSubmission',11,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 28, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(137,1,'App\\Models\\AssignmentSubmission',12,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 29, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(138,1,'App\\Models\\AssignmentSubmission',13,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 27, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(139,1,'App\\Models\\AssignmentSubmission',14,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 25, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(140,1,'App\\Models\\AssignmentSubmission',15,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 32, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(141,1,'App\\Models\\AssignmentSubmission',16,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 23, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 10:19:54'),(142,1,'App\\Models\\Assignment',3,'deleted','{\"title\": \"Tula\", \"due_date\": \"2026-10-13\", \"assigned_date\": \"2026-10-12\", \"class_assignment_id\": 8}',NULL,'192.168.10.1','2026-10-12 10:21:38'),(143,1,'App\\Models\\Assignment',4,'created',NULL,'{\"title\": \"Tula\", \"due_date\": \"2026-10-11\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 10:21:56'),(144,1,'App\\Models\\Assignment',5,'created',NULL,'{\"title\": \"Tula\", \"due_date\": \"2026-10-18\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 12:29:27'),(145,1,'App\\Models\\Assignment',5,'updated','{\"title\": \"Tula\", \"due_date\": \"2026-10-18\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 8}','{\"title\": \"Tula\", \"due_date\": \"2026-10-11\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 12:30:54'),(146,1,'App\\Models\\AssignmentSubmission',17,'created',NULL,'{\"status\": \"late\", \"student_id\": 31, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(147,1,'App\\Models\\AssignmentSubmission',18,'created',NULL,'{\"status\": \"late\", \"student_id\": 24, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(148,1,'App\\Models\\AssignmentSubmission',19,'created',NULL,'{\"status\": \"late\", \"student_id\": 19, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(149,1,'App\\Models\\AssignmentSubmission',20,'created',NULL,'{\"status\": \"late\", \"student_id\": 26, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(150,1,'App\\Models\\AssignmentSubmission',21,'created',NULL,'{\"status\": \"late\", \"student_id\": 30, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(151,1,'App\\Models\\AssignmentSubmission',22,'created',NULL,'{\"status\": \"excused\", \"student_id\": 20, \"submitted_date\": null, \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(152,1,'App\\Models\\AssignmentSubmission',23,'created',NULL,'{\"status\": \"late\", \"student_id\": 33, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(153,1,'App\\Models\\AssignmentSubmission',24,'created',NULL,'{\"status\": \"late\", \"student_id\": 22, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(154,1,'App\\Models\\AssignmentSubmission',25,'created',NULL,'{\"status\": \"late\", \"student_id\": 21, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(155,1,'App\\Models\\AssignmentSubmission',26,'created',NULL,'{\"status\": \"late\", \"student_id\": 28, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(156,1,'App\\Models\\AssignmentSubmission',27,'created',NULL,'{\"status\": \"late\", \"student_id\": 29, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(157,1,'App\\Models\\AssignmentSubmission',28,'created',NULL,'{\"status\": \"excused\", \"student_id\": 27, \"submitted_date\": null, \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(158,1,'App\\Models\\AssignmentSubmission',29,'created',NULL,'{\"status\": \"late\", \"student_id\": 25, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(159,1,'App\\Models\\AssignmentSubmission',30,'created',NULL,'{\"status\": \"late\", \"student_id\": 32, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(160,1,'App\\Models\\AssignmentSubmission',31,'created',NULL,'{\"status\": \"late\", \"student_id\": 23, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:07'),(161,1,'App\\Models\\AssignmentSubmission',21,'updated','{\"status\": \"late\", \"student_id\": 30, \"submitted_date\": \"2026-10-12\"}','{\"status\": \"submitted\", \"student_id\": 30, \"submitted_date\": \"2026-10-11\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 12:31:20'),(162,1,'App\\Models\\Assignment',4,'deleted','{\"title\": \"Tula\", \"due_date\": \"2026-10-11\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 8}',NULL,'192.168.10.1','2026-10-12 12:31:39'),(163,1,'App\\Models\\Student',34,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0001\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(164,1,'App\\Models\\Student',35,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0002\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(165,1,'App\\Models\\Student',36,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0003\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(166,1,'App\\Models\\Student',37,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0004\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(167,1,'App\\Models\\Student',38,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0005\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(168,1,'App\\Models\\Student',39,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0006\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(169,1,'App\\Models\\Student',40,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0007\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(170,1,'App\\Models\\Student',41,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0008\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(171,1,'App\\Models\\Student',42,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0009\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(172,1,'App\\Models\\Student',43,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0010\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(173,1,'App\\Models\\Student',44,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0011\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(174,1,'App\\Models\\Student',45,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0012\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(175,1,'App\\Models\\Student',46,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0013\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(176,1,'App\\Models\\Student',47,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0014\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(177,1,'App\\Models\\Student',48,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"DEMO-0015\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(178,1,'App\\Models\\Enrollment',48,'created',NULL,'{\"student_id\": 34, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(179,1,'App\\Models\\Enrollment',49,'created',NULL,'{\"student_id\": 35, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(180,1,'App\\Models\\Enrollment',50,'created',NULL,'{\"student_id\": 36, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(181,1,'App\\Models\\Enrollment',51,'created',NULL,'{\"student_id\": 37, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(182,1,'App\\Models\\Enrollment',52,'created',NULL,'{\"student_id\": 38, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(183,1,'App\\Models\\Enrollment',53,'created',NULL,'{\"student_id\": 39, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(184,1,'App\\Models\\Enrollment',54,'created',NULL,'{\"student_id\": 40, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(185,1,'App\\Models\\Enrollment',55,'created',NULL,'{\"student_id\": 41, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(186,1,'App\\Models\\Enrollment',56,'created',NULL,'{\"student_id\": 42, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(187,1,'App\\Models\\Enrollment',57,'created',NULL,'{\"student_id\": 43, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(188,1,'App\\Models\\Enrollment',58,'created',NULL,'{\"student_id\": 44, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(189,1,'App\\Models\\Enrollment',59,'created',NULL,'{\"student_id\": 45, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(190,1,'App\\Models\\Enrollment',60,'created',NULL,'{\"student_id\": 46, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(191,1,'App\\Models\\Enrollment',61,'created',NULL,'{\"student_id\": 47, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(192,1,'App\\Models\\Enrollment',62,'created',NULL,'{\"student_id\": 48, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:54:51'),(193,1,'App\\Models\\Enrollment',63,'created',NULL,'{\"student_id\": 49, \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 12:58:52'),(194,1,'App\\Models\\Assignment',6,'created',NULL,'{\"title\": \"Grammar and Vocabulary\", \"due_date\": \"2026-10-19\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 13:04:16'),(195,1,'App\\Models\\Assignment',6,'updated','{\"title\": \"Grammar and Vocabulary\", \"due_date\": \"2026-10-19\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 12}','{\"title\": \"Grammar and Vocabulary\", \"due_date\": \"2026-10-12\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 13:06:29'),(196,1,'App\\Models\\AssignmentSubmission',32,'created',NULL,'{\"status\": \"excused\", \"student_id\": 46, \"submitted_date\": null, \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(197,1,'App\\Models\\AssignmentSubmission',33,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 39, \"submitted_date\": \"2026-10-11\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(198,1,'App\\Models\\AssignmentSubmission',34,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 49, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(199,1,'App\\Models\\AssignmentSubmission',35,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 34, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(200,1,'App\\Models\\AssignmentSubmission',36,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 41, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(201,1,'App\\Models\\AssignmentSubmission',37,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 45, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(202,1,'App\\Models\\AssignmentSubmission',38,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 35, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(203,1,'App\\Models\\AssignmentSubmission',39,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 48, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(204,1,'App\\Models\\AssignmentSubmission',40,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 37, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(205,1,'App\\Models\\AssignmentSubmission',41,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 36, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(206,1,'App\\Models\\AssignmentSubmission',42,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 43, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(207,1,'App\\Models\\AssignmentSubmission',43,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 44, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(208,1,'App\\Models\\AssignmentSubmission',44,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 42, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(209,1,'App\\Models\\AssignmentSubmission',45,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 40, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(210,1,'App\\Models\\AssignmentSubmission',46,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 47, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(211,1,'App\\Models\\AssignmentSubmission',47,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 38, \"submitted_date\": \"2026-10-12\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:06:57'),(212,1,'App\\Models\\AssignmentSubmission',32,'updated','{\"status\": \"excused\", \"student_id\": 46, \"submitted_date\": null}','{\"status\": \"excused\", \"student_id\": 46, \"submitted_date\": null, \"feedback_changed\": true}','192.168.10.1','2026-10-12 13:07:15'),(213,1,'App\\Models\\Assignment',6,'updated','{\"title\": \"Grammar and Vocabulary\", \"due_date\": \"2026-10-12\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 12}','{\"title\": \"Grammar and Vocabulary\", \"due_date\": \"2026-10-12\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 13:07:34'),(214,1,'App\\Models\\AssignmentSubmission',33,'deleted','{\"status\": \"submitted\", \"student_id\": 39, \"submitted_date\": \"2026-10-11\"}',NULL,'192.168.10.1','2026-10-12 13:07:52'),(215,1,'App\\Models\\Assignment',7,'created',NULL,'{\"title\": \"Practical Writing\", \"due_date\": \"2026-10-11\", \"assigned_date\": \"2026-10-10\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-12 13:08:49'),(216,1,'App\\Models\\AssignmentSubmission',48,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 46, \"submitted_date\": \"2026-10-11\", \"feedback_changed\": false}','192.168.10.1','2026-10-12 13:09:58'),(217,1,'App\\Models\\AssignmentSubmission',49,'created',NULL,'{\"status\": \"late\", \"student_id\": 39, \"submitted_date\": \"2026-10-14\", \"feedback_changed\": false}','192.168.10.1','2026-10-14 09:56:53'),(218,1,'App\\Models\\AssignmentSubmission',50,'created',NULL,'{\"status\": \"late\", \"student_id\": 39, \"submitted_date\": \"2026-10-14\", \"feedback_changed\": false}','192.168.10.1','2026-10-14 09:57:16'),(219,1,'App\\Models\\AssignmentSubmission',51,'created',NULL,'{\"status\": \"late\", \"student_id\": 49, \"submitted_date\": \"2026-10-14\", \"feedback_changed\": false}','192.168.10.1','2026-10-14 09:57:16'),(220,1,'App\\Models\\Intervention',1,'created',NULL,'{\"status\": \"planned\", \"outcome\": null, \"student_id\": 46, \"concern_type\": \"attendance_concern\", \"follow_up_date\": \"2026-11-07\", \"intervention_date\": \"2026-10-17\", \"intervention_type\": \"parent_meeting\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-17 10:19:16'),(221,1,'App\\Models\\Intervention',2,'created',NULL,'{\"status\": \"planned\", \"outcome\": null, \"student_id\": 39, \"concern_type\": \"attendance_concern\", \"follow_up_date\": \"2026-10-31\", \"intervention_date\": \"2026-10-17\", \"intervention_type\": \"tutoring\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-17 10:19:59'),(222,1,'App\\Models\\Assignment',8,'created',NULL,'{\"title\": \"English\", \"due_date\": \"2026-10-24\", \"assigned_date\": \"2026-10-17\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-17 10:20:56'),(223,1,'App\\Models\\AssignmentSubmission',52,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 49, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(224,1,'App\\Models\\AssignmentSubmission',53,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 34, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(225,1,'App\\Models\\AssignmentSubmission',54,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 41, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(226,1,'App\\Models\\AssignmentSubmission',55,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 45, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(227,1,'App\\Models\\AssignmentSubmission',56,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 35, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(228,1,'App\\Models\\AssignmentSubmission',57,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 48, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(229,1,'App\\Models\\AssignmentSubmission',58,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 37, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(230,1,'App\\Models\\AssignmentSubmission',59,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 36, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(231,1,'App\\Models\\AssignmentSubmission',60,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 43, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(232,1,'App\\Models\\AssignmentSubmission',61,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 44, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(233,1,'App\\Models\\AssignmentSubmission',62,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 42, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(234,1,'App\\Models\\AssignmentSubmission',63,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 40, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(235,1,'App\\Models\\AssignmentSubmission',64,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 47, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(236,1,'App\\Models\\AssignmentSubmission',65,'created',NULL,'{\"status\": \"submitted\", \"student_id\": 38, \"submitted_date\": \"2026-10-17\", \"feedback_changed\": false}','192.168.10.1','2026-10-17 10:21:22'),(237,1,'App\\Models\\Assignment',8,'updated','{\"title\": \"English\", \"due_date\": \"2026-10-24\", \"assigned_date\": \"2026-10-17\", \"class_assignment_id\": 12}','{\"title\": \"English\", \"due_date\": \"2026-10-09\", \"assigned_date\": \"2026-10-01\", \"class_assignment_id\": 12, \"recalculated_submissions\": 14}','192.168.10.1','2026-10-17 10:22:44'),(238,1,'App\\Models\\Intervention',3,'created',NULL,'{\"status\": \"planned\", \"outcome\": null, \"student_id\": 39, \"concern_type\": \"missing_work\", \"follow_up_date\": \"2026-10-31\", \"intervention_date\": \"2026-10-17\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-17 10:31:47'),(239,1,'App\\Models\\AssignmentSubmission',50,'deleted','{\"status\": \"late\", \"student_id\": 39, \"submitted_date\": \"2026-10-14\"}',NULL,'192.168.10.1','2026-10-17 10:33:29'),(240,1,'App\\Models\\Intervention',3,'rescheduled','{\"status\": \"planned\", \"outcome\": null, \"follow_up_date\": \"2026-10-31\"}','{\"status\": \"planned\", \"outcome\": null, \"by_admin\": false, \"followup_id\": 1, \"follow_up_date\": \"2026-10-31\"}','192.168.10.1','2026-10-17 12:40:12'),(241,1,'App\\Models\\Intervention',2,'completed','{\"status\": \"planned\", \"outcome\": null, \"follow_up_date\": \"2026-10-31\"}','{\"status\": \"completed\", \"outcome\": \"improved\", \"by_admin\": false, \"followup_id\": 2, \"follow_up_date\": null}','192.168.10.1','2026-10-17 12:43:49'),(242,1,'App\\Models\\StudentNote',1,'created',NULL,'{\"student_id\": 34, \"class_assignment_id\": null}','192.168.10.1','2026-10-18 01:18:15'),(243,1,'App\\Models\\ParentCommunication',1,'created',NULL,'{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-18\", \"follow_up_date\": null, \"class_assignment_id\": null}','192.168.10.1','2026-10-18 01:21:37'),(244,1,'App\\Models\\Intervention',3,'rescheduled','{\"status\": \"planned\", \"outcome\": null, \"follow_up_date\": \"2026-10-31\"}','{\"status\": \"planned\", \"outcome\": null, \"by_admin\": false, \"followup_id\": 3, \"follow_up_date\": \"2026-11-18\"}','192.168.10.1','2026-10-18 03:57:27'),(245,1,'App\\Models\\ParentCommunication',2,'created',NULL,'{\"method\": \"phone_call\", \"student_id\": 34, \"contact_date\": \"2026-10-18\", \"follow_up_date\": null, \"follow_up_done_at\": null, \"class_assignment_id\": 12}','192.168.10.1','2026-10-18 12:29:06'),(246,1,'App\\Models\\ParentCommunication',1,'updated','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-18\", \"follow_up_date\": null, \"follow_up_done_at\": null, \"class_assignment_id\": null}','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-10\", \"follow_up_date\": \"2026-10-17\", \"reason_changed\": false, \"summary_changed\": false, \"follow_up_done_at\": null, \"class_assignment_id\": null, \"follow_up_done_cleared\": false}','192.168.10.1','2026-10-18 12:29:59'),(247,1,'App\\Models\\ParentCommunication',1,'updated','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-10\", \"follow_up_date\": \"2026-10-17\", \"follow_up_done_at\": null, \"class_assignment_id\": null}','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-10\", \"follow_up_date\": \"2026-10-16\", \"reason_changed\": false, \"summary_changed\": false, \"follow_up_done_at\": null, \"class_assignment_id\": null, \"follow_up_done_cleared\": false}','192.168.10.1','2026-10-18 12:30:34'),(248,1,'App\\Models\\ParentCommunication',1,'follow_up_done','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-10\", \"follow_up_date\": \"2026-10-16\", \"follow_up_done_at\": null, \"class_assignment_id\": null}','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-10\", \"follow_up_date\": \"2026-10-16\", \"follow_up_done_at\": \"2026-10-18\", \"class_assignment_id\": null, \"follow_up_done_changed\": true}','192.168.10.1','2026-10-18 12:34:43'),(249,1,'App\\Models\\ParentCommunication',1,'follow_up_undone','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-10\", \"follow_up_date\": \"2026-10-16\", \"follow_up_done_at\": \"2026-10-18\", \"class_assignment_id\": null}','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-10\", \"follow_up_date\": \"2026-10-16\", \"follow_up_done_at\": null, \"class_assignment_id\": null, \"follow_up_done_changed\": true}','192.168.10.1','2026-10-18 12:34:48'),(250,1,'App\\Models\\ParentCommunication',1,'follow_up_done','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-10\", \"follow_up_date\": \"2026-10-16\", \"follow_up_done_at\": null, \"class_assignment_id\": null}','{\"method\": \"text\", \"student_id\": 34, \"contact_date\": \"2026-10-10\", \"follow_up_date\": \"2026-10-16\", \"follow_up_done_at\": \"2026-10-18\", \"class_assignment_id\": null, \"follow_up_done_changed\": true}','192.168.10.1','2026-10-18 12:57:24'),(251,1,'App\\Models\\Intervention',4,'created',NULL,'{\"status\": \"planned\", \"outcome\": null, \"student_id\": 36, \"concern_type\": \"attendance_concern\", \"follow_up_date\": \"2026-10-20\", \"intervention_date\": \"2026-10-19\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 03:45:22'),(252,1,'App\\Models\\Intervention',4,'completed','{\"status\": \"planned\", \"outcome\": null, \"follow_up_date\": \"2026-10-20\"}','{\"status\": \"completed\", \"outcome\": \"partly_improved\", \"by_admin\": false, \"followup_id\": 4, \"follow_up_date\": null}','192.168.10.1','2026-10-19 03:46:07'),(253,1,'App\\Models\\Intervention',4,'reopened','{\"status\": \"completed\", \"outcome\": \"partly_improved\", \"follow_up_date\": null}','{\"status\": \"in_progress\", \"outcome\": null, \"by_admin\": false, \"followup_id\": 5, \"follow_up_date\": null}','192.168.10.1','2026-10-19 03:46:46'),(254,1,'App\\Models\\Intervention',4,'completed','{\"status\": \"in_progress\", \"outcome\": null, \"follow_up_date\": null}','{\"status\": \"completed\", \"outcome\": \"improved\", \"by_admin\": false, \"followup_id\": 6, \"follow_up_date\": null}','192.168.10.1','2026-10-19 03:47:18'),(255,1,'App\\Models\\Intervention',4,'deleted','{\"status\": \"completed\", \"outcome\": \"improved\", \"student_id\": 36, \"concern_type\": \"attendance_concern\", \"follow_up_date\": null, \"intervention_date\": \"2026-10-19\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}',NULL,'192.168.10.1','2026-10-19 03:59:22'),(256,1,'App\\Models\\Intervention',2,'deleted','{\"status\": \"completed\", \"outcome\": \"improved\", \"student_id\": 39, \"concern_type\": \"attendance_concern\", \"follow_up_date\": null, \"intervention_date\": \"2026-10-17\", \"intervention_type\": \"tutoring\", \"class_assignment_id\": 12}',NULL,'192.168.10.1','2026-10-19 03:59:29'),(257,1,'App\\Models\\StudentNote',2,'created',NULL,'{\"student_id\": 35, \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 04:00:36'),(258,1,'App\\Models\\StudentNote',2,'deleted','{\"student_id\": 35, \"class_assignment_id\": 12}',NULL,'192.168.10.1','2026-10-19 04:01:47'),(259,1,'App\\Models\\ParentCommunication',3,'created',NULL,'{\"method\": \"phone_call\", \"student_id\": 35, \"contact_date\": \"2026-10-19\", \"follow_up_date\": \"2026-10-24\", \"follow_up_done_at\": null, \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 04:02:02'),(260,1,'App\\Models\\ParentCommunication',3,'deleted','{\"method\": \"phone_call\", \"student_id\": 35, \"contact_date\": \"2026-10-19\", \"follow_up_date\": \"2026-10-24\", \"follow_up_done_at\": null, \"class_assignment_id\": 12}',NULL,'192.168.10.1','2026-10-19 04:02:08'),(261,1,'App\\Models\\NotificationPreference',1,'created',NULL,'{\"in_app_enabled\": true, \"include_students_to_review\": false}','192.168.10.1','2026-10-19 07:23:07'),(262,1,'App\\Models\\NotificationPreference',1,'updated','{\"in_app_enabled\": true, \"include_students_to_review\": false}','{\"in_app_enabled\": true, \"include_students_to_review\": true}','192.168.10.1','2026-10-19 07:23:10'),(263,1,'App\\Models\\Intervention',5,'created',NULL,'{\"status\": \"planned\", \"outcome\": null, \"student_id\": 39, \"concern_type\": \"missing_work\", \"follow_up_date\": null, \"intervention_date\": \"2026-10-19\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 07:23:34'),(264,1,'App\\Models\\Intervention',6,'created',NULL,'{\"status\": \"planned\", \"outcome\": null, \"student_id\": 39, \"concern_type\": \"attendance_concern\", \"follow_up_date\": null, \"intervention_date\": \"2026-10-19\", \"intervention_type\": \"tutoring\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 07:24:17'),(265,1,'App\\Models\\Intervention',3,'completed','{\"status\": \"planned\", \"outcome\": null, \"follow_up_date\": \"2026-11-18\"}','{\"status\": \"completed\", \"outcome\": \"improved\", \"by_admin\": false, \"followup_id\": 7, \"follow_up_date\": null}','192.168.10.1','2026-10-19 07:27:49'),(266,1,'App\\Models\\Intervention',3,'deleted','{\"status\": \"completed\", \"outcome\": \"improved\", \"student_id\": 39, \"concern_type\": \"missing_work\", \"follow_up_date\": null, \"intervention_date\": \"2026-10-17\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}',NULL,'192.168.10.1','2026-10-19 07:28:48'),(267,1,'App\\Models\\Intervention',5,'completed','{\"status\": \"planned\", \"outcome\": null, \"follow_up_date\": null}','{\"status\": \"completed\", \"outcome\": \"improved\", \"by_admin\": false, \"followup_id\": 8, \"follow_up_date\": null}','192.168.10.1','2026-10-19 07:29:29'),(268,1,'App\\Models\\Intervention',6,'completed','{\"status\": \"planned\", \"outcome\": null, \"follow_up_date\": null}','{\"status\": \"completed\", \"outcome\": \"improved\", \"by_admin\": false, \"followup_id\": 9, \"follow_up_date\": null}','192.168.10.1','2026-10-19 07:29:40'),(269,1,'App\\Models\\Intervention',7,'created',NULL,'{\"status\": \"planned\", \"outcome\": null, \"student_id\": 46, \"concern_type\": \"attendance_concern\", \"follow_up_date\": \"2026-10-24\", \"intervention_date\": \"2026-10-19\", \"intervention_type\": \"tutoring\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 09:36:01'),(270,1,'App\\Models\\Intervention',7,'completed','{\"status\": \"planned\", \"outcome\": null, \"follow_up_date\": \"2026-10-24\"}','{\"status\": \"completed\", \"outcome\": \"improved\", \"by_admin\": false, \"followup_id\": 10, \"follow_up_date\": null}','192.168.10.1','2026-10-19 09:36:34'),(271,1,'App\\Models\\ParentCommunication',4,'created',NULL,'{\"method\": \"phone_call\", \"student_id\": 37, \"contact_date\": \"2026-10-12\", \"follow_up_date\": \"2026-10-18\", \"follow_up_done_at\": null, \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 09:38:04'),(272,1,'App\\Models\\ParentCommunication',4,'follow_up_done','{\"method\": \"phone_call\", \"student_id\": 37, \"contact_date\": \"2026-10-12\", \"follow_up_date\": \"2026-10-18\", \"follow_up_done_at\": null, \"class_assignment_id\": 12}','{\"method\": \"phone_call\", \"student_id\": 37, \"contact_date\": \"2026-10-12\", \"follow_up_date\": \"2026-10-18\", \"follow_up_done_at\": \"2026-10-19\", \"class_assignment_id\": 12, \"follow_up_done_changed\": true}','192.168.10.1','2026-10-19 09:40:02'),(273,1,'App\\Models\\ParentCommunication',4,'follow_up_undone','{\"method\": \"phone_call\", \"student_id\": 37, \"contact_date\": \"2026-10-12\", \"follow_up_date\": \"2026-10-18\", \"follow_up_done_at\": \"2026-10-19\", \"class_assignment_id\": 12}','{\"method\": \"phone_call\", \"student_id\": 37, \"contact_date\": \"2026-10-12\", \"follow_up_date\": \"2026-10-18\", \"follow_up_done_at\": null, \"class_assignment_id\": 12, \"follow_up_done_changed\": true}','192.168.10.1','2026-10-19 09:40:31'),(274,1,'App\\Models\\ParentCommunication',4,'deleted','{\"method\": \"phone_call\", \"student_id\": 37, \"contact_date\": \"2026-10-12\", \"follow_up_date\": \"2026-10-18\", \"follow_up_done_at\": null, \"class_assignment_id\": 12}',NULL,'192.168.10.1','2026-10-19 09:43:03'),(275,1,'App\\Models\\StudentNote',3,'created',NULL,'{\"student_id\": 37, \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 09:43:14'),(276,1,'App\\Models\\StudentNote',3,'deleted','{\"student_id\": 37, \"class_assignment_id\": 12}',NULL,'192.168.10.1','2026-10-19 09:43:19'),(277,1,'App\\Models\\StudentNote',3,'restored',NULL,'{\"student_id\": 37, \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 09:43:25'),(278,1,'App\\Models\\ClassAssignment',12,'exported',NULL,'{\"students\": 16}','192.168.10.1','2026-10-19 14:38:03'),(279,1,'App\\Models\\Intervention',8,'created',NULL,'{\"status\": \"planned\", \"outcome\": null, \"student_id\": 36, \"concern_type\": \"attendance_concern\", \"follow_up_date\": \"2026-10-24\", \"intervention_date\": \"2026-10-19\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 15:00:02'),(280,1,'App\\Models\\Intervention',8,'updated','{\"status\": \"planned\", \"outcome\": null, \"student_id\": 36, \"concern_type\": \"attendance_concern\", \"follow_up_date\": \"2026-10-24\", \"intervention_date\": \"2026-10-19\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}','{\"status\": \"planned\", \"outcome\": null, \"student_id\": 36, \"concern_type\": \"attendance_concern\", \"notes_changed\": false, \"follow_up_date\": \"2026-10-24\", \"concern_changed\": false, \"intervention_date\": \"2026-10-18\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 15:07:46'),(281,1,'App\\Models\\Intervention',8,'followup_recorded','{\"status\": \"planned\", \"outcome\": null, \"follow_up_date\": \"2026-10-24\"}','{\"status\": \"in_progress\", \"outcome\": null, \"by_admin\": false, \"followup_id\": 11, \"follow_up_date\": \"2026-10-20\"}','192.168.10.1','2026-10-19 15:09:03'),(282,1,'App\\Models\\Intervention',9,'created',NULL,'{\"status\": \"planned\", \"outcome\": null, \"student_id\": 36, \"concern_type\": \"attendance_concern\", \"follow_up_date\": \"2026-10-21\", \"intervention_date\": \"2026-10-19\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}','192.168.10.1','2026-10-19 15:09:29'),(283,1,'App\\Models\\Intervention',9,'deleted','{\"status\": \"planned\", \"outcome\": null, \"student_id\": 36, \"concern_type\": \"attendance_concern\", \"follow_up_date\": \"2026-10-21\", \"intervention_date\": \"2026-10-19\", \"intervention_type\": \"consultation\", \"class_assignment_id\": 12}',NULL,'192.168.10.1','2026-10-19 15:10:25'),(284,1,'App\\Models\\Intervention',8,'completed','{\"status\": \"in_progress\", \"outcome\": null, \"follow_up_date\": \"2026-10-20\"}','{\"status\": \"completed\", \"outcome\": \"improved\", \"by_admin\": false, \"followup_id\": 12, \"follow_up_date\": null}','192.168.10.1','2026-10-19 15:10:42'),(285,1,'App\\Models\\Exam',1,'created',NULL,'{\"set_id\": 1, \"exam_date\": \"2026-10-20\", \"choice_count\": 4, \"class_assignment_id\": 12}','192.168.10.1','2026-10-20 12:17:31'),(286,1,'App\\Models\\Competency',1,'created',NULL,'{\"owner_id\": 1, \"subject_id\": null}','192.168.10.1','2026-10-20 12:19:57'),(287,1,'App\\Models\\Exam',1,'question_added',NULL,'{\"set_id\": 1, \"position\": 1, \"question_id\": 1, \"competency_id\": 1}','192.168.10.1','2026-10-20 12:20:12'),(288,1,'App\\Models\\Exam',1,'question_added',NULL,'{\"set_id\": 1, \"position\": 2, \"question_id\": 2, \"competency_id\": 1}','192.168.10.1','2026-10-20 12:20:52'),(289,1,'App\\Models\\Exam',1,'set_added',NULL,'{\"label\": \"B\", \"set_id\": 2}','192.168.10.1','2026-10-20 23:08:45'),(290,1,'App\\Models\\Exam',1,'set_deleted','{\"label\": \"B\", \"set_id\": 2, \"question_count\": 0}',NULL,'192.168.10.1','2026-10-20 23:08:51'),(291,1,'App\\Models\\Exam',1,'set_added',NULL,'{\"label\": \"B\", \"set_id\": 3}','192.168.10.1','2026-10-21 10:40:39'),(292,1,'App\\Models\\Exam',1,'set_deleted','{\"label\": \"B\", \"set_id\": 3, \"question_count\": 0}',NULL,'192.168.10.1','2026-10-21 10:40:44'),(293,1,'App\\Models\\Exam',2,'created',NULL,'{\"set_id\": 4, \"exam_date\": \"2026-10-21\", \"choice_count\": 3, \"class_assignment_id\": 14}','192.168.10.1','2026-10-21 11:07:15'),(294,1,'App\\Models\\Competency',2,'created',NULL,'{\"owner_id\": 1, \"subject_id\": null}','192.168.10.1','2026-10-21 11:07:39'),(295,1,'App\\Models\\Exam',2,'question_added',NULL,'{\"set_id\": 4, \"position\": 1, \"question_id\": 3, \"competency_id\": 2}','192.168.10.1','2026-10-21 11:07:56');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
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
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
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
-- Table structure for table `class_assignments`
--

DROP TABLE IF EXISTS `class_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `section_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_class_assignment` (`teacher_id`,`subject_id`,`section_id`,`academic_year_id`),
  KEY `class_assignments_subject_id_foreign` (`subject_id`),
  KEY `class_assignments_academic_year_id_foreign` (`academic_year_id`),
  KEY `class_assignments_section_id_academic_year_id_index` (`section_id`,`academic_year_id`),
  CONSTRAINT `class_assignments_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_assignments_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_assignments_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_assignments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_assignments`
--

LOCK TABLES `class_assignments` WRITE;
/*!40000 ALTER TABLE `class_assignments` DISABLE KEYS */;
INSERT INTO `class_assignments` VALUES (1,1,1,1,1,'2026-09-22 11:52:33','2026-10-11 04:17:12','archived','2026-10-11 04:17:12'),(2,1,1,2,1,'2026-10-02 12:15:43','2026-10-11 04:17:07','archived','2026-10-11 04:17:07'),(3,1,4,3,1,'2026-10-08 12:44:07','2026-10-11 04:17:10','archived','2026-10-11 04:17:10'),(7,1,4,7,1,'2026-10-11 04:27:37','2026-10-12 02:50:56','archived','2026-10-12 02:50:56'),(8,1,4,8,1,'2026-10-12 09:28:14','2026-10-12 12:47:02','archived','2026-10-12 12:47:02'),(9,1,4,9,1,'2026-10-12 12:49:47','2026-10-12 12:52:18','archived','2026-10-12 12:52:18'),(10,1,3,10,1,'2026-10-12 12:50:12','2026-10-12 12:50:36','archived','2026-10-12 12:50:36'),(11,1,3,11,1,'2026-10-12 12:51:02','2026-10-12 12:52:21','archived','2026-10-12 12:52:21'),(12,1,3,9,1,'2026-10-12 12:53:36','2026-10-12 12:53:36','active',NULL),(14,1,3,12,1,'2026-10-12 12:54:29','2026-10-12 12:54:29','active',NULL);
/*!40000 ALTER TABLE `class_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competencies`
--

DROP TABLE IF EXISTS `competencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint unsigned NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `competencies_subject_id_foreign` (`subject_id`),
  KEY `competencies_owner_id_name_index` (`owner_id`,`name`),
  CONSTRAINT `competencies_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  CONSTRAINT `competencies_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competencies`
--

LOCK TABLES `competencies` WRITE;
/*!40000 ALTER TABLE `competencies` DISABLE KEYS */;
INSERT INTO `competencies` VALUES (1,1,'good',NULL,'2026-10-20 12:19:57','2026-10-20 12:19:57'),(2,1,'sfsdf',NULL,'2026-10-21 11:07:39','2026-10-21 11:07:39');
/*!40000 ALTER TABLE `competencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_assignment_id` bigint unsigned DEFAULT NULL,
  `section_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollments_student_class_unique` (`student_id`,`class_assignment_id`),
  KEY `enrollments_academic_year_id_foreign` (`academic_year_id`),
  KEY `enrollments_section_id_academic_year_id_index` (`section_id`,`academic_year_id`),
  KEY `enrollments_class_assignment_id_foreign` (`class_assignment_id`),
  CONSTRAINT `enrollments_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
INSERT INTO `enrollments` VALUES (1,1,1,1,1,'2026-09-22 11:52:33','2026-09-22 11:52:33'),(2,2,1,1,1,'2026-09-22 11:52:33','2026-09-22 11:52:33'),(3,3,1,1,1,'2026-10-01 22:47:45','2026-10-01 22:47:45'),(5,5,1,1,1,'2026-10-03 07:03:14','2026-10-03 07:03:14'),(12,7,1,1,1,'2026-10-07 12:36:17','2026-10-07 12:36:17'),(21,5,2,2,1,'2026-10-08 11:56:21','2026-10-08 11:56:21'),(22,11,2,2,1,'2026-10-08 12:03:39','2026-10-08 12:03:39'),(23,11,1,1,1,'2026-10-08 12:18:27','2026-10-08 12:18:27'),(28,15,3,3,1,'2026-10-09 09:14:04','2026-10-09 09:14:04'),(29,7,2,2,1,'2026-10-09 09:15:15','2026-10-09 09:15:15'),(30,16,3,3,1,'2026-10-11 03:30:01','2026-10-11 03:30:01'),(48,34,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(49,35,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(50,36,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(51,37,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(52,38,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(53,39,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(54,40,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(55,41,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(56,42,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(57,43,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(58,44,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(59,45,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(60,46,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(61,47,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(62,48,12,9,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(63,49,12,9,1,'2026-10-12 12:58:52','2026-10-12 12:58:52');
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_questions`
--

DROP TABLE IF EXISTS `exam_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_set_id` bigint unsigned NOT NULL,
  `position` smallint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `choices` json NOT NULL,
  `correct_choice` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competency_id` bigint unsigned NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_alt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_questions_competency_id_foreign` (`competency_id`),
  KEY `exam_questions_exam_set_id_position_index` (`exam_set_id`,`position`),
  CONSTRAINT `exam_questions_competency_id_foreign` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`),
  CONSTRAINT `exam_questions_exam_set_id_foreign` FOREIGN KEY (`exam_set_id`) REFERENCES `exam_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_questions`
--

LOCK TABLES `exam_questions` WRITE;
/*!40000 ALTER TABLE `exam_questions` DISABLE KEYS */;
INSERT INTO `exam_questions` VALUES (1,1,1,'George is _____ than Nick.','[\"tall\", \"taller\", \"tallest\", \"-\"]','A',1,NULL,NULL,'2026-10-20 12:20:12','2026-10-20 12:20:12'),(2,1,2,'My friend _____ lives in Australia is a nurse.','[\"who\", \"which\", \"whose\", \"-\"]','B',1,NULL,NULL,'2026-10-20 12:20:52','2026-10-20 12:20:52'),(3,4,1,'sdfsf','[\"sdf\", \"sdf\", \"sdf\"]','B',2,NULL,NULL,'2026-10-21 11:07:56','2026-10-21 11:07:56');
/*!40000 ALTER TABLE `exam_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_sets`
--

DROP TABLE IF EXISTS `exam_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` bigint unsigned NOT NULL,
  `label` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_sets_exam_id_label_unique` (`exam_id`,`label`),
  UNIQUE KEY `exam_sets_qr_token_unique` (`qr_token`),
  CONSTRAINT `exam_sets_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_sets`
--

LOCK TABLES `exam_sets` WRITE;
/*!40000 ALTER TABLE `exam_sets` DISABLE KEYS */;
INSERT INTO `exam_sets` VALUES (1,1,'A','rriLMbrEdjFMzvs3uhkT6ciZgpnHdHShGvBnvluv','2026-10-20 12:17:31','2026-10-20 12:17:31'),(4,2,'A','oWP5gtA49IVOhhBh2r8repbzCqGGyueZGPrZKv4d','2026-10-21 11:07:15','2026-10-21 11:07:15');
/*!40000 ALTER TABLE `exam_sets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `author_id` bigint unsigned NOT NULL,
  `class_assignment_id` bigint unsigned NOT NULL,
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exam_date` date NOT NULL,
  `choice_count` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exams_author_id_exam_date_index` (`author_id`,`exam_date`),
  KEY `exams_class_assignment_id_index` (`class_assignment_id`),
  CONSTRAINT `exams_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  CONSTRAINT `exams_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
INSERT INTO `exams` VALUES (1,1,12,'fsdf','2026-10-20',4,'2026-10-20 12:17:31','2026-10-20 12:17:31',NULL),(2,1,14,'dsad','2026-10-21',3,'2026-10-21 11:07:15','2026-10-21 11:07:15',NULL);
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
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
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
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
-- Table structure for table `grade_levels`
--

DROP TABLE IF EXISTS `grade_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_levels`
--

LOCK TABLES `grade_levels` WRITE;
/*!40000 ALTER TABLE `grade_levels` DISABLE KEYS */;
INSERT INTO `grade_levels` VALUES (1,'Grade 8',8,'2026-09-22 11:52:33','2026-09-22 11:52:33'),(2,'Grade 9',9,'2026-10-08 12:43:01','2026-10-08 12:43:01');
/*!40000 ALTER TABLE `grade_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intervention_followups`
--

DROP TABLE IF EXISTS `intervention_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intervention_followups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `intervention_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned NOT NULL,
  `entry_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `followup_date` date NOT NULL,
  `from_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `progress` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `outcome` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `intervention_followups_author_id_foreign` (`author_id`),
  KEY `intervention_followups_intervention_id_followup_date_index` (`intervention_id`,`followup_date`),
  CONSTRAINT `intervention_followups_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `intervention_followups_intervention_id_foreign` FOREIGN KEY (`intervention_id`) REFERENCES `interventions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intervention_followups`
--

LOCK TABLES `intervention_followups` WRITE;
/*!40000 ALTER TABLE `intervention_followups` DISABLE KEYS */;
INSERT INTO `intervention_followups` VALUES (1,3,1,'rescheduled','2026-10-17','planned','planned',NULL,'dfsd',NULL,'2026-10-31','2026-10-17 12:40:12','2026-10-17 12:40:12'),(2,2,1,'completed','2026-10-17','planned','completed',NULL,NULL,NULL,NULL,'2026-10-17 12:43:49','2026-10-17 12:43:49'),(3,3,1,'rescheduled','2026-10-18','planned','planned',NULL,NULL,NULL,'2026-11-18','2026-10-18 03:57:27','2026-10-18 03:57:27'),(4,4,1,'completed','2026-10-19','planned','completed',NULL,NULL,'partly_improved',NULL,'2026-10-19 03:46:07','2026-10-19 03:46:07'),(5,4,1,'reopened','2026-10-19','completed','in_progress','sdfs','sdf',NULL,NULL,'2026-10-19 03:46:46','2026-10-19 03:46:46'),(6,4,1,'completed','2026-10-19','in_progress','completed','dfg','dfg','improved',NULL,'2026-10-19 03:47:18','2026-10-19 03:47:18'),(7,3,1,'completed','2026-10-19','planned','completed','sdf','sdf','improved',NULL,'2026-10-19 07:27:48','2026-10-19 07:27:48'),(8,5,1,'completed','2026-10-19','planned','completed','gdf','dfg','improved',NULL,'2026-10-19 07:29:29','2026-10-19 07:29:29'),(9,6,1,'completed','2026-10-19','planned','completed','dgf','dfg','improved',NULL,'2026-10-19 07:29:40','2026-10-19 07:29:40'),(10,7,1,'completed','2026-10-19','planned','completed','sdf','sdf','improved',NULL,'2026-10-19 09:36:34','2026-10-19 09:36:34'),(11,8,1,'followup','2026-10-19','planned','in_progress','f','sdfsd',NULL,'2026-10-20','2026-10-19 15:09:03','2026-10-19 15:09:03'),(12,8,1,'completed','2026-10-19','in_progress','completed','fg','dfg','improved',NULL,'2026-10-19 15:10:42','2026-10-19 15:10:42');
/*!40000 ALTER TABLE `intervention_followups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interventions`
--

DROP TABLE IF EXISTS `interventions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interventions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_assignment_id` bigint unsigned DEFAULT NULL,
  `author_id` bigint unsigned NOT NULL,
  `concern_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `concern` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `intervention_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intervention_date` date NOT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `outcome` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interventions_class_assignment_id_foreign` (`class_assignment_id`),
  KEY `interventions_author_id_status_follow_up_date_index` (`author_id`,`status`,`follow_up_date`),
  KEY `interventions_student_id_index` (`student_id`),
  CONSTRAINT `interventions_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `interventions_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `interventions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interventions`
--

LOCK TABLES `interventions` WRITE;
/*!40000 ALTER TABLE `interventions` DISABLE KEYS */;
INSERT INTO `interventions` VALUES (1,46,12,1,'attendance_concern','4 consecutive absences.','parent_meeting','2026-10-17','2026-11-07','planned',NULL,NULL,'2026-10-17 10:19:16','2026-10-17 10:19:16',NULL),(2,39,12,1,'attendance_concern','4 consecutive absences.','tutoring','2026-10-17',NULL,'completed','improved',NULL,'2026-10-17 10:19:59','2026-10-19 03:59:29','2026-10-19 03:59:29'),(3,39,12,1,'missing_work','1 overdue assignments have no submission recorded (review threshold 1).','consultation','2026-10-17',NULL,'completed','improved',NULL,'2026-10-17 10:31:47','2026-10-19 07:28:48','2026-10-19 07:28:48'),(4,36,12,1,'attendance_concern','sdf','consultation','2026-10-19',NULL,'completed','improved','sdf','2026-10-19 03:45:22','2026-10-19 03:59:22','2026-10-19 03:59:22'),(5,39,12,1,'missing_work','Attendance rate is 0% over 4 recorded days (review threshold 80%). 4 consecutive absences.','consultation','2026-10-19',NULL,'completed','improved','fsdf','2026-10-19 07:23:34','2026-10-19 07:29:29',NULL),(6,39,12,1,'attendance_concern','Attendance rate is 0% over 4 recorded days (review threshold 80%). 4 consecutive absences.','tutoring','2026-10-19',NULL,'completed','improved','sadf','2026-10-19 07:24:17','2026-10-19 07:29:40',NULL),(7,46,12,1,'attendance_concern','Attendance rate is 0% over 4 recorded days (review threshold 80%). 4 consecutive absences.','tutoring','2026-10-19',NULL,'completed','improved','dfsdf','2026-10-19 09:36:01','2026-10-19 09:36:34',NULL),(8,36,12,1,'attendance_concern','Attendance rate is 57.1% over 7 recorded days (review threshold 80%). 3 consecutive absences.','consultation','2026-10-18',NULL,'completed','improved','fsdf','2026-10-19 15:00:02','2026-10-19 15:10:42',NULL),(9,36,12,1,'attendance_concern','Attendance rate is 57.1% over 7 recorded days (review threshold 80%). 3 consecutive absences.','consultation','2026-10-19','2026-10-21','planned',NULL,'sdf','2026-10-19 15:09:29','2026-10-19 15:10:25','2026-10-19 15:10:25');
/*!40000 ALTER TABLE `interventions` ENABLE KEYS */;
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
  `attempts` smallint unsigned NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_13_051953_create_personal_access_tokens_table',1),(5,'2026_09_13_060024_add_role_to_users_table',1),(6,'2026_09_13_060217_create_teachers_table',1),(7,'2026_09_21_094009_create_academic_years_table',1),(8,'2026_09_21_094211_create_grade_levels_table',1),(9,'2026_09_21_094256_create_subjects_table',1),(10,'2026_09_21_094334_create_sections_table',1),(11,'2026_09_21_100923_create_class_assignments_table',1),(12,'2026_09_21_102720_create_students_table',1),(13,'2026_09_21_103747_create_enrollments_table',1),(14,'2026_10_02_103750_add_status_and_soft_deletes_to_class_assignments',2),(15,'2026_10_03_050926_create_attendance_table',3),(16,'2026_10_03_084556_create_terms_table',4),(17,'2026_10_03_084559_create_grading_tables',4),(18,'2026_10_04_115605_drop_grading_tables',5),(19,'2026_10_07_102816_add_class_to_enrollments',6),(20,'2026_10_09_080553_create_student_grade_levels_table',7),(21,'2026_10_09_093256_create_monitoring_settings_table',8),(22,'2026_10_10_100000_add_guardian_email_and_notifications',9),(23,'2026_10_12_063452_create_assignments_tables',10),(24,'2026_10_12_064911_assignments_followup_index',10),(25,'2026_10_15_041247_create_intervention_tables',11),(26,'2026_10_18_113358_add_follow_up_done_to_parent_communications',12),(27,'2026_10_18_120000_ensure_follow_up_done_at_column',13),(28,'2026_10_19_000001_add_outcome_to_intervention_followups',14),(29,'2026_10_19_100000_create_notification_preferences_table',15),(30,'2026_10_20_100000_create_exam_tables',16);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monitoring_settings`
--

DROP TABLE IF EXISTS `monitoring_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monitoring_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `attendance_rate_threshold` decimal(5,2) NOT NULL DEFAULT '80.00',
  `consecutive_absence_threshold` tinyint unsigned NOT NULL DEFAULT '3',
  `min_days_for_rate` tinyint unsigned NOT NULL DEFAULT '5',
  `missing_assignments_threshold` tinyint unsigned NOT NULL DEFAULT '3',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `guardian_email_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `notify_on_absent` tinyint(1) NOT NULL DEFAULT '1',
  `notify_on_late` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `monitoring_settings_teacher_id_unique` (`teacher_id`),
  CONSTRAINT `monitoring_settings_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monitoring_settings`
--

LOCK TABLES `monitoring_settings` WRITE;
/*!40000 ALTER TABLE `monitoring_settings` DISABLE KEYS */;
INSERT INTO `monitoring_settings` VALUES (4,1,80.00,3,4,2,'2026-10-11 02:34:55','2026-10-17 10:32:21',1,1,1);
/*!40000 ALTER TABLE `monitoring_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `in_app_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `include_students_to_review` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_preferences_teacher_id_unique` (`teacher_id`),
  CONSTRAINT `notification_preferences_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
INSERT INTO `notification_preferences` VALUES (1,1,1,1,'2026-10-19 07:23:07','2026-10-19 07:23:10');
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_communications`
--

DROP TABLE IF EXISTS `parent_communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parent_communications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_assignment_id` bigint unsigned DEFAULT NULL,
  `author_id` bigint unsigned NOT NULL,
  `contact_date` date NOT NULL,
  `method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `follow_up_date` date DEFAULT NULL,
  `follow_up_done_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_communications_class_assignment_id_foreign` (`class_assignment_id`),
  KEY `parent_communications_student_id_contact_date_index` (`student_id`,`contact_date`),
  KEY `parent_communications_author_id_follow_up_date_index` (`author_id`,`follow_up_date`),
  CONSTRAINT `parent_communications_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `parent_communications_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `parent_communications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_communications`
--

LOCK TABLES `parent_communications` WRITE;
/*!40000 ALTER TABLE `parent_communications` DISABLE KEYS */;
INSERT INTO `parent_communications` VALUES (1,34,NULL,1,'2026-10-10','text','test','fsdf','2026-10-16','2026-10-18','2026-10-18 01:21:37','2026-10-18 12:57:24',NULL),(2,34,12,1,'2026-10-18','phone_call','asd','asd',NULL,NULL,'2026-10-18 12:29:06','2026-10-18 12:29:06',NULL),(3,35,12,1,'2026-10-19','phone_call','das','dasd','2026-10-24',NULL,'2026-10-19 04:02:02','2026-10-19 04:02:08','2026-10-19 04:02:08'),(4,37,12,1,'2026-10-12','phone_call','das','adasd','2026-10-18',NULL,'2026-10-19 09:38:04','2026-10-19 09:43:03','2026-10-19 09:43:03');
/*!40000 ALTER TABLE `parent_communications` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_level_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `adviser_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sections_academic_year_id_foreign` (`academic_year_id`),
  KEY `sections_adviser_id_foreign` (`adviser_id`),
  KEY `sections_grade_level_id_academic_year_id_index` (`grade_level_id`,`academic_year_id`),
  CONSTRAINT `sections_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sections_adviser_id_foreign` FOREIGN KEY (`adviser_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sections_grade_level_id_foreign` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sections`
--

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
INSERT INTO `sections` VALUES (1,'Mabini',1,1,1,'2026-09-22 11:52:33','2026-09-22 11:52:33'),(2,'Mahogany',1,1,NULL,'2026-10-02 12:15:43','2026-10-03 07:01:32'),(3,'Narra',2,1,NULL,'2026-10-08 12:44:07','2026-10-08 12:44:07'),(7,'Rizal',2,1,NULL,'2026-10-11 04:27:37','2026-10-11 04:27:37'),(8,'Wisdom',2,1,NULL,'2026-10-12 09:28:14','2026-10-12 09:28:14'),(9,'Wisdom',1,1,NULL,'2026-10-12 12:49:47','2026-10-12 12:49:47'),(10,'Intergrity',2,1,NULL,'2026-10-12 12:50:12','2026-10-12 12:50:12'),(11,'Integrity',1,1,NULL,'2026-10-12 12:51:02','2026-10-12 12:51:02'),(12,'Faith',1,1,NULL,'2026-10-12 12:54:29','2026-10-12 12:54:29');
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;
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
-- Table structure for table `student_grade_levels`
--

DROP TABLE IF EXISTS `student_grade_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_grade_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `grade_level_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_grade_levels_student_id_academic_year_id_unique` (`student_id`,`academic_year_id`),
  KEY `student_grade_levels_academic_year_id_foreign` (`academic_year_id`),
  KEY `student_grade_levels_grade_level_id_foreign` (`grade_level_id`),
  CONSTRAINT `student_grade_levels_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_grade_levels_grade_level_id_foreign` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `student_grade_levels_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_grade_levels`
--

LOCK TABLES `student_grade_levels` WRITE;
/*!40000 ALTER TABLE `student_grade_levels` DISABLE KEYS */;
INSERT INTO `student_grade_levels` VALUES (1,1,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(2,2,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(3,3,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(4,5,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(5,7,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(6,11,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(9,14,1,2,'2026-10-09 09:00:54','2026-10-09 09:00:54'),(10,15,1,2,'2026-10-09 09:14:04','2026-10-09 09:14:04'),(11,16,1,2,'2026-10-11 03:30:01','2026-10-11 03:30:01'),(29,34,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(30,35,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(31,36,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(32,37,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(33,38,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(34,39,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(35,40,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(36,41,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(37,42,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(38,43,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(39,44,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(40,45,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(41,46,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(42,47,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(43,48,1,1,'2026-10-12 12:54:51','2026-10-12 12:54:51'),(44,49,1,1,'2026-10-12 12:58:52','2026-10-12 12:58:52');
/*!40000 ALTER TABLE `student_grade_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_notes`
--

DROP TABLE IF EXISTS `student_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_assignment_id` bigint unsigned DEFAULT NULL,
  `author_id` bigint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_notes_class_assignment_id_foreign` (`class_assignment_id`),
  KEY `student_notes_student_id_created_at_index` (`student_id`,`created_at`),
  KEY `student_notes_author_id_index` (`author_id`),
  CONSTRAINT `student_notes_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `student_notes_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `student_notes_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_notes`
--

LOCK TABLES `student_notes` WRITE;
/*!40000 ALTER TABLE `student_notes` DISABLE KEYS */;
INSERT INTO `student_notes` VALUES (1,34,NULL,1,'test note general student','2026-10-18 01:18:15','2026-10-18 01:18:15',NULL),(2,35,12,1,'fsdfs','2026-10-19 04:00:36','2026-10-19 04:01:47','2026-10-19 04:01:47'),(3,37,12,1,'adsasd','2026-10-19 09:43:14','2026-10-19 09:43:25',NULL);
/*!40000 ALTER TABLE `student_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sex` enum('male','female') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date NOT NULL,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','archived','transferred') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_student_id_unique` (`student_id`),
  KEY `students_student_id_index` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'2026-0001','Juan',NULL,'Dela Cruz','male','2013-01-04',NULL,NULL,NULL,'guillenpaul070@gmail.com','archived','2026-09-22 11:52:33','2026-09-23 08:24:14','2026-09-23 08:24:14'),(2,'2026-0002','Maria','Asp','Santos','female','2013-03-15',NULL,NULL,NULL,'guillenpaul070@gmail.com','archived','2026-09-22 11:52:33','2026-10-11 04:17:30','2026-10-11 04:17:30'),(3,'78988','paul','aspera','guillen','male','2026-03-12','dfsdf','dsfsdf','sdf','guillenpaul070@gmail.com','archived','2026-10-01 22:47:45','2026-10-01 22:48:36','2026-10-01 22:48:36'),(4,'7858975','Johny sinns',NULL,'sdfsdf','male','2007-03-03','566','565','dsf','guillenpaul070@gmail.com','archived','2026-10-03 02:47:47','2026-10-11 04:17:31','2026-10-11 04:17:31'),(5,'7895521','Peter',NULL,'Parker','male','2015-03-03','0989898998','Malou','098689868','guillenpaul070@gmail.com','archived','2026-10-03 07:03:14','2026-10-11 04:17:33','2026-10-11 04:17:33'),(6,'105852888','John',NULL,'Cenna','male','1998-04-13','097555','Ben','07955686','guillenpaul070@gmail.com','archived','2026-10-03 07:04:08','2026-10-11 04:17:35','2026-10-11 04:17:35'),(7,'789588','Jake',NULL,'Lesng','male','2017-03-09','sdf','sdf','sdf','guillenpaul070@gmail.com','archived','2026-10-07 12:36:17','2026-10-11 04:17:37','2026-10-11 04:17:37'),(11,'122232','Ronaldo',NULL,'Davenci','male','2013-05-20','954545','Merla Storw','954545','guillenpaul070@gmail.com','archived','2026-10-08 12:03:39','2026-10-11 04:17:38','2026-10-11 04:17:38'),(14,'098987','Derick',NULL,'Ramsey','male','2013-05-04','dfdf','sdf','sdf','guillenpaul070@gmail.com','archived','2026-10-09 09:00:54','2026-10-11 04:17:42','2026-10-11 04:17:42'),(15,'78944','Milo','Afro','Jack','male','1998-05-29','9898','Ben','89986','guillenpaul070@gmail.com','archived','2026-10-09 09:14:04','2026-10-11 04:17:43','2026-10-11 04:17:43'),(16,'20261589','Lino',NULL,'Butay','male','2018-03-11','78878','Jake','092656565','guillenpaul070@gmail.com','archived','2026-10-11 03:30:01','2026-10-11 04:17:45','2026-10-11 04:17:45'),(34,'DEMO-0001','Juan','Santos','Dela Cruz','male','2013-03-14','09170000001','Maria Dela Cruz','09170000101',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(35,'DEMO-0002','Maria','Reyes','Garcia','female','2013-05-22','09170000002','Jose Garcia','09170000102','guillenpaul070@gmail.com','active','2026-10-12 12:54:51','2026-10-12 12:59:14',NULL),(36,'DEMO-0003','Jose','Cruz','Ramos','male','2013-01-09','09170000003','Ana Ramos','09170000103','guillenpaul070@gmail.com','active','2026-10-12 12:54:51','2026-10-19 14:56:17',NULL),(37,'DEMO-0004','Ana','Lopez','Mendoza','female','2013-07-30','09170000004','Pedro Mendoza','09170000104',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(38,'DEMO-0005','Pedro','Bautista','Villanueva','male','2013-11-02','09170000005','Rosa Villanueva','09170000105',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(39,'DEMO-0006','Rosa','Aquino','Castillo','female','2013-09-18','09170000006','Luis Castillo','09170000106',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(40,'DEMO-0007','Luis','Navarro','Torres','male','2013-04-27','09170000007','Carmen Torres','09170000107',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(41,'DEMO-0008','Carmen','Flores','Domingo','female','2013-12-05','09170000008','Mario Domingo','09170000108',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(42,'DEMO-0009','Mario','Pascual','Soriano','male','2013-06-11','09170000009','Elena Soriano','09170000109',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(43,'DEMO-0010','Elena','Morales','Rivera','female','2013-02-25','09170000010','Ramon Rivera','09170000110',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(44,'DEMO-0011','Ramon','Diaz','Salazar','male','2013-08-08','09170000011','Lita Salazar','09170000111',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(45,'DEMO-0012','Lita','Gonzales','Fernandez','female','2013-10-16','09170000012','Carlos Fernandez','09170000112',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(46,'DEMO-0013','Carlos','Tan','Alvarez','male','2013-03-03','09170000013','Nena Alvarez','09170000113',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(47,'DEMO-0014','Nena','Perez','Valdez','female','2013-05-29','09170000014','Danilo Valdez','09170000114',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(48,'DEMO-0015','Danilo','Ocampo','Magno','male','2013-07-07','09170000015','Gina Magno','09170000115',NULL,'active','2026-10-12 12:54:51','2026-10-12 12:54:51',NULL),(49,'DEMO-0050','John',NULL,'Dela Cruz','male','2014-01-29','09361788944','Mary','09875879','guillenpaul070@gmail.com','active','2026-10-12 12:58:52','2026-10-12 12:58:52',NULL);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'Mathematics','MATH','2026-09-22 11:52:33','2026-09-22 11:52:33'),(3,'English','ENG','2026-09-22 11:52:33','2026-09-22 11:52:33'),(4,'Filipino','FIL','2026-10-08 12:43:29','2026-10-08 12:43:29');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teachers_user_id_unique` (`user_id`),
  UNIQUE KEY `teachers_employee_id_unique` (`employee_id`),
  CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (1,1,'DEMO-0001','09170000000','2026-09-22 11:52:33','2026-09-22 11:52:33'),(2,3,'DEMO-T2',NULL,'2026-10-03 09:36:28','2026-10-03 09:36:28');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `terms`
--

DROP TABLE IF EXISTS `terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `terms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` tinyint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_academic_year_id_sort_order_unique` (`academic_year_id`,`sort_order`),
  CONSTRAINT `terms_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms`
--

LOCK TABLES `terms` WRITE;
/*!40000 ALTER TABLE `terms` DISABLE KEYS */;
INSERT INTO `terms` VALUES (1,1,'Term 1',1,'2026-06-08','2026-10-16','active','2026-10-03 08:55:37','2026-10-03 08:55:37'),(2,1,'Term 2',2,'2026-10-19','2027-02-12','upcoming','2026-10-03 08:55:37','2026-10-03 08:55:37'),(3,1,'Term 3',3,'2027-02-15','2027-04-02','upcoming','2026-10-03 08:55:37','2026-10-03 08:55:37');
/*!40000 ALTER TABLE `terms` ENABLE KEYS */;
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
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('teacher','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'teacher',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Demo Teacher','teacher@demo.local',NULL,'$2y$12$0uCdUuE/I2NVuRm8yXONj.XiGPsZRocxSta/jjxefZK35EvaAA5Ea','teacher','kMcNBF758URIDPkI8sRuTpbm93kvgU7dKz9EXWo5wQvGLC7G9ciaF9kUMzrP','2026-09-22 11:52:33','2026-09-22 11:52:33'),(2,'Demo Admin','admin@demo.local',NULL,'$2y$12$10SnBE9yWAGmmKkEOrtHL..5SOyd2mg.NkHbar8NVapEUDPEhdj46','admin','I9D83AQLb7XBHdZLphu2RbXxJNdMxkFPjpuaaSTxoCDI1TgPw2v1idito5CQ','2026-09-22 11:52:33','2026-09-22 11:52:33'),(3,'Second Teacher','teacher2@demo.local',NULL,'$2y$12$pLZnd4SX269uwC4PYgo7su8SDJI1WUq2pnNL3TIhRGZZrJ8zJ4gvG','teacher','4EZwg4FMxeBgYJ0euShtPE7DyDI6ae1490oUH0fqb2440k9obcp9rnTfdSQT','2026-10-03 09:35:55','2026-10-03 09:35:55');
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

-- Dump completed on 2026-10-21 11:39:34
