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
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (2,4,2,'2026-10-03','present',NULL,1,'2026-10-03 06:30:17','2026-10-03 06:38:25'),(5,2,1,'2026-10-01','present',NULL,1,'2026-10-03 06:39:21','2026-10-03 06:39:35'),(12,2,1,'2026-10-02','excused',NULL,1,'2026-10-03 06:43:43','2026-10-03 06:43:43'),(40,6,1,'2026-10-03','late',NULL,1,'2026-10-03 07:18:31','2026-10-03 07:31:43'),(41,5,1,'2026-10-03','absent',NULL,1,'2026-10-03 07:18:31','2026-10-03 07:31:43'),(42,2,1,'2026-10-03','absent',NULL,1,'2026-10-03 07:18:31','2026-10-03 07:31:43'),(44,7,1,'2026-10-07','present',NULL,1,'2026-10-07 12:38:04','2026-10-10 04:54:26'),(45,5,1,'2026-10-07','late',NULL,1,'2026-10-07 12:38:04','2026-10-10 04:54:26'),(46,2,1,'2026-10-07','late',NULL,1,'2026-10-07 12:38:04','2026-10-10 04:54:26'),(47,7,2,'2026-10-07','excused',NULL,1,'2026-10-07 12:56:54','2026-10-07 12:56:54'),(48,7,1,'2026-10-05','present',NULL,1,'2026-10-07 22:28:11','2026-10-07 22:28:11'),(49,5,1,'2026-10-05','present',NULL,1,'2026-10-07 22:28:11','2026-10-07 22:28:11'),(50,2,1,'2026-10-05','present',NULL,1,'2026-10-07 22:28:11','2026-10-07 22:28:11'),(51,15,3,'2026-10-09','present',NULL,1,'2026-10-09 09:16:57','2026-10-09 09:16:57'),(52,11,2,'2026-10-09','late',NULL,1,'2026-10-09 09:17:06','2026-10-09 09:17:06'),(53,7,2,'2026-10-09','late',NULL,1,'2026-10-09 09:17:06','2026-10-09 09:17:06'),(54,5,2,'2026-10-09','late',NULL,1,'2026-10-09 09:17:06','2026-10-09 09:17:06'),(55,11,2,'2026-10-08','excused',NULL,1,'2026-10-09 09:17:12','2026-10-09 09:17:12'),(56,7,2,'2026-10-08','excused',NULL,1,'2026-10-09 09:17:12','2026-10-09 09:17:12'),(57,5,2,'2026-10-08','excused',NULL,1,'2026-10-09 09:17:12','2026-10-09 09:17:12'),(70,11,1,'2026-10-07','absent',NULL,1,'2026-10-10 04:54:26','2026-10-10 04:54:26'),(74,11,1,'2026-10-08','absent',NULL,1,'2026-10-10 04:54:30','2026-10-10 11:13:04'),(75,11,1,'2026-10-09','absent',NULL,1,'2026-10-10 04:54:35','2026-10-10 11:13:11'),(76,15,3,'2026-10-10','present',NULL,1,'2026-10-10 07:39:13','2026-10-10 07:39:13'),(77,7,1,'2026-10-08','absent',NULL,1,'2026-10-10 11:13:04','2026-10-10 11:13:04'),(79,7,1,'2026-10-09','present',NULL,1,'2026-10-10 11:13:11','2026-10-10 11:13:11'),(81,11,1,'2030-01-01','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(82,11,1,'2030-01-02','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(83,11,1,'2030-01-03','late',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(84,11,1,'2030-01-04','absent',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(85,11,1,'2030-01-05','absent',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(86,11,1,'2030-01-06','absent',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(87,7,1,'2030-01-01','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(88,7,1,'2030-01-02','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(89,7,1,'2030-01-03','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(90,7,1,'2030-01-04','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(91,7,1,'2030-01-05','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(92,7,1,'2030-01-06','excused','Medical',2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(93,5,1,'2030-01-02','present',NULL,2,'2026-10-10 11:16:55','2026-10-10 11:16:55'),(109,11,1,'2026-10-06','present',NULL,1,'2026-10-10 16:08:38','2026-10-10 16:08:38'),(110,11,1,'2026-10-10','present',NULL,1,'2026-10-10 16:13:00','2026-10-10 16:13:00'),(139,17,7,'2026-10-11','absent',NULL,1,'2026-10-11 04:29:11','2026-10-11 11:58:12'),(142,17,7,'2026-10-10','absent',NULL,1,'2026-10-11 11:53:12','2026-10-11 11:53:12'),(146,17,7,'2026-10-12','absent',NULL,1,'2026-10-11 23:48:00','2026-10-11 23:48:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_notifications`
--

LOCK TABLES `attendance_notifications` WRITE;
/*!40000 ALTER TABLE `attendance_notifications` DISABLE KEYS */;
INSERT INTO `attendance_notifications` VALUES (13,11,1,'2026-10-11','absent','sent',NULL,'2026-10-11 03:23:07','2026-10-11 03:23:05','2026-10-11 03:23:07'),(14,7,1,'2026-10-11','absent','sent',NULL,'2026-10-11 03:23:08','2026-10-11 03:23:07','2026-10-11 03:23:08'),(15,5,1,'2026-10-11','late','sent',NULL,'2026-10-11 03:23:09','2026-10-11 03:23:08','2026-10-11 03:23:09'),(16,2,1,'2026-10-11','late','sent',NULL,'2026-10-11 03:23:09','2026-10-11 03:23:09','2026-10-11 03:23:09'),(20,17,7,'2026-10-11','absent','sent',NULL,'2026-10-11 11:58:15','2026-10-11 11:58:12','2026-10-11 11:58:15'),(21,17,7,'2026-10-12','absent','sent',NULL,'2026-10-11 23:48:06','2026-10-11 23:48:01','2026-10-11 23:48:06');
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
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (77,1,'App\\Models\\Enrollment',24,'deleted','{\"student_id\": 11, \"class_assignment_id\": 3}',NULL,'192.168.10.1','2026-10-09 08:59:38'),(78,1,'App\\Models\\Enrollment',27,'created',NULL,'{\"student_id\": 14, \"class_assignment_id\": 3}','192.168.10.1','2026-10-09 09:00:54'),(79,1,'App\\Models\\Enrollment',27,'deleted','{\"student_id\": 14, \"class_assignment_id\": 3}',NULL,'192.168.10.1','2026-10-09 09:01:17'),(80,1,'App\\Models\\ClassAssignment',3,'exported',NULL,'{\"students\": 0}','192.168.10.1','2026-10-09 09:13:16'),(81,1,'App\\Models\\Student',15,'created',NULL,'{\"source\": \"csv_import\", \"student_id\": \"78944\", \"class_assignment_id\": 3}','192.168.10.1','2026-10-09 09:14:04'),(82,1,'App\\Models\\Enrollment',28,'created',NULL,'{\"student_id\": 15, \"class_assignment_id\": 3}','192.168.10.1','2026-10-09 09:14:04'),(83,1,'App\\Models\\Enrollment',29,'created',NULL,'{\"student_id\": 7, \"class_assignment_id\": 2}','192.168.10.1','2026-10-09 09:15:15'),(84,1,'App\\Models\\ClassAssignment',2,'exported',NULL,'{\"students\": 3}','192.168.10.1','2026-10-09 09:16:32'),(85,1,'App\\Models\\Enrollment',30,'created',NULL,'{\"student_id\": 16, \"class_assignment_id\": 3}','192.168.10.1','2026-10-11 03:30:01'),(86,1,'App\\Models\\Enrollment',31,'created',NULL,'{\"student_id\": 17, \"class_assignment_id\": 7}','192.168.10.1','2026-10-11 04:28:38'),(87,1,'App\\Models\\Enrollment',32,'created',NULL,'{\"student_id\": 18, \"class_assignment_id\": 8}','192.168.10.1','2026-10-12 09:29:06');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_assignments`
--

LOCK TABLES `class_assignments` WRITE;
/*!40000 ALTER TABLE `class_assignments` DISABLE KEYS */;
INSERT INTO `class_assignments` VALUES (1,1,1,1,1,'2026-09-22 11:52:33','2026-10-11 04:17:12','archived','2026-10-11 04:17:12'),(2,1,1,2,1,'2026-10-02 12:15:43','2026-10-11 04:17:07','archived','2026-10-11 04:17:07'),(3,1,4,3,1,'2026-10-08 12:44:07','2026-10-11 04:17:10','archived','2026-10-11 04:17:10'),(7,1,4,7,1,'2026-10-11 04:27:37','2026-10-12 02:50:56','archived','2026-10-12 02:50:56'),(8,1,4,8,1,'2026-10-12 09:28:14','2026-10-12 09:28:14','active',NULL);
/*!40000 ALTER TABLE `class_assignments` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
INSERT INTO `enrollments` VALUES (1,1,1,1,1,'2026-09-22 11:52:33','2026-09-22 11:52:33'),(2,2,1,1,1,'2026-09-22 11:52:33','2026-09-22 11:52:33'),(3,3,1,1,1,'2026-10-01 22:47:45','2026-10-01 22:47:45'),(5,5,1,1,1,'2026-10-03 07:03:14','2026-10-03 07:03:14'),(12,7,1,1,1,'2026-10-07 12:36:17','2026-10-07 12:36:17'),(21,5,2,2,1,'2026-10-08 11:56:21','2026-10-08 11:56:21'),(22,11,2,2,1,'2026-10-08 12:03:39','2026-10-08 12:03:39'),(23,11,1,1,1,'2026-10-08 12:18:27','2026-10-08 12:18:27'),(28,15,3,3,1,'2026-10-09 09:14:04','2026-10-09 09:14:04'),(29,7,2,2,1,'2026-10-09 09:15:15','2026-10-09 09:15:15'),(30,16,3,3,1,'2026-10-11 03:30:01','2026-10-11 03:30:01'),(31,17,7,7,1,'2026-10-11 04:28:38','2026-10-11 04:28:38'),(32,18,8,8,1,'2026-10-12 09:29:06','2026-10-12 09:29:06');
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_13_051953_create_personal_access_tokens_table',1),(5,'2026_09_13_060024_add_role_to_users_table',1),(6,'2026_09_13_060217_create_teachers_table',1),(7,'2026_09_21_094009_create_academic_years_table',1),(8,'2026_09_21_094211_create_grade_levels_table',1),(9,'2026_09_21_094256_create_subjects_table',1),(10,'2026_09_21_094334_create_sections_table',1),(11,'2026_09_21_100923_create_class_assignments_table',1),(12,'2026_09_21_102720_create_students_table',1),(13,'2026_09_21_103747_create_enrollments_table',1),(14,'2026_10_02_103750_add_status_and_soft_deletes_to_class_assignments',2),(15,'2026_10_03_050926_create_attendance_table',3),(16,'2026_10_03_084556_create_terms_table',4),(17,'2026_10_03_084559_create_grading_tables',4),(18,'2026_10_04_115605_drop_grading_tables',5),(19,'2026_10_07_102816_add_class_to_enrollments',6),(20,'2026_10_09_080553_create_student_grade_levels_table',7),(21,'2026_10_09_093256_create_monitoring_settings_table',8),(22,'2026_10_10_100000_add_guardian_email_and_notifications',9);
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
INSERT INTO `monitoring_settings` VALUES (4,1,80.00,3,5,3,'2026-10-11 02:34:55','2026-10-11 02:34:55',1,1,1);
/*!40000 ALTER TABLE `monitoring_settings` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sections`
--

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
INSERT INTO `sections` VALUES (1,'Mabini',1,1,1,'2026-09-22 11:52:33','2026-09-22 11:52:33'),(2,'Mahogany',1,1,NULL,'2026-10-02 12:15:43','2026-10-03 07:01:32'),(3,'Narra',2,1,NULL,'2026-10-08 12:44:07','2026-10-08 12:44:07'),(7,'Rizal',2,1,NULL,'2026-10-11 04:27:37','2026-10-11 04:27:37'),(8,'Wisdom',2,1,NULL,'2026-10-12 09:28:14','2026-10-12 09:28:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_grade_levels`
--

LOCK TABLES `student_grade_levels` WRITE;
/*!40000 ALTER TABLE `student_grade_levels` DISABLE KEYS */;
INSERT INTO `student_grade_levels` VALUES (1,1,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(2,2,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(3,3,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(4,5,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(5,7,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(6,11,1,1,'2026-10-09 08:21:42','2026-10-09 08:21:42'),(9,14,1,2,'2026-10-09 09:00:54','2026-10-09 09:00:54'),(10,15,1,2,'2026-10-09 09:14:04','2026-10-09 09:14:04'),(11,16,1,2,'2026-10-11 03:30:01','2026-10-11 03:30:01'),(12,17,1,2,'2026-10-11 04:28:38','2026-10-11 04:28:38'),(13,18,1,2,'2026-10-12 09:29:06','2026-10-12 09:29:06');
/*!40000 ALTER TABLE `student_grade_levels` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'2026-0001','Juan',NULL,'Dela Cruz','male','2013-01-04',NULL,NULL,NULL,'guillenpaul070@gmail.com','archived','2026-09-22 11:52:33','2026-09-23 08:24:14','2026-09-23 08:24:14'),(2,'2026-0002','Maria','Asp','Santos','female','2013-03-15',NULL,NULL,NULL,'guillenpaul070@gmail.com','archived','2026-09-22 11:52:33','2026-10-11 04:17:30','2026-10-11 04:17:30'),(3,'78988','paul','aspera','guillen','male','2026-03-12','dfsdf','dsfsdf','sdf','guillenpaul070@gmail.com','archived','2026-10-01 22:47:45','2026-10-01 22:48:36','2026-10-01 22:48:36'),(4,'7858975','Johny sinns',NULL,'sdfsdf','male','2007-03-03','566','565','dsf','guillenpaul070@gmail.com','archived','2026-10-03 02:47:47','2026-10-11 04:17:31','2026-10-11 04:17:31'),(5,'7895521','Peter',NULL,'Parker','male','2015-03-03','0989898998','Malou','098689868','guillenpaul070@gmail.com','archived','2026-10-03 07:03:14','2026-10-11 04:17:33','2026-10-11 04:17:33'),(6,'105852888','John',NULL,'Cenna','male','1998-04-13','097555','Ben','07955686','guillenpaul070@gmail.com','archived','2026-10-03 07:04:08','2026-10-11 04:17:35','2026-10-11 04:17:35'),(7,'789588','Jake',NULL,'Lesng','male','2017-03-09','sdf','sdf','sdf','guillenpaul070@gmail.com','archived','2026-10-07 12:36:17','2026-10-11 04:17:37','2026-10-11 04:17:37'),(11,'122232','Ronaldo',NULL,'Davenci','male','2013-05-20','954545','Merla Storw','954545','guillenpaul070@gmail.com','archived','2026-10-08 12:03:39','2026-10-11 04:17:38','2026-10-11 04:17:38'),(14,'098987','Derick',NULL,'Ramsey','male','2013-05-04','dfdf','sdf','sdf','guillenpaul070@gmail.com','archived','2026-10-09 09:00:54','2026-10-11 04:17:42','2026-10-11 04:17:42'),(15,'78944','Milo','Afro','Jack','male','1998-05-29','9898','Ben','89986','guillenpaul070@gmail.com','archived','2026-10-09 09:14:04','2026-10-11 04:17:43','2026-10-11 04:17:43'),(16,'20261589','Lino',NULL,'Butay','male','2018-03-11','78878','Jake','092656565','guillenpaul070@gmail.com','archived','2026-10-11 03:30:01','2026-10-11 04:17:45','2026-10-11 04:17:45'),(17,'2026002356','Peter',NULL,'Parker','male','2018-02-11','08988545','Maria','0936454545','guillenpaul070@gmail.com','active','2026-10-11 04:28:38','2026-10-11 04:28:38',NULL),(18,'20261158','Juan',NULL,'Dela Cruz','male','2018-03-12','0945','Lily','098445','guillenpaul070@gmail.com','active','2026-10-12 09:29:06','2026-10-12 09:29:06',NULL);
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
INSERT INTO `users` VALUES (1,'Demo Teacher','teacher@demo.local',NULL,'$2y$12$0uCdUuE/I2NVuRm8yXONj.XiGPsZRocxSta/jjxefZK35EvaAA5Ea','teacher','W2eiIgbvSOp2mgxPWXP9ixzqK8PDmBGmtPI3Lw8dj31ErWNKXWq9hK7tzyLK','2026-09-22 11:52:33','2026-09-22 11:52:33'),(2,'Demo Admin','admin@demo.local',NULL,'$2y$12$10SnBE9yWAGmmKkEOrtHL..5SOyd2mg.NkHbar8NVapEUDPEhdj46','admin','r5ccnmbHqP7OVIDqURUZmqdVUVT2QOLNpjlVrO4d9U4g9yXUkGxWFNGj2D7Q','2026-09-22 11:52:33','2026-09-22 11:52:33'),(3,'Second Teacher','teacher2@demo.local',NULL,'$2y$12$pLZnd4SX269uwC4PYgo7su8SDJI1WUq2pnNL3TIhRGZZrJ8zJ4gvG','teacher','4EZwg4FMxeBgYJ0euShtPE7DyDI6ae1490oUH0fqb2440k9obcp9rnTfdSQT','2026-10-03 09:35:55','2026-10-03 09:35:55');
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

-- Dump completed on 2026-10-12  9:30:08
