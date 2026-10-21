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
-- Table structure for table `grading_configurations`
--

DROP TABLE IF EXISTS `grading_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grading_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `grade_level_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `passing_grade` decimal(5,2) NOT NULL DEFAULT '75.00',
  `category_average_method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'total_points',
  `use_transmutation` tinyint(1) NOT NULL DEFAULT '0',
  `rounding_decimals` tinyint unsigned NOT NULL DEFAULT '2',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grading_configurations_grade_level_id_foreign` (`grade_level_id`),
  KEY `grading_configurations_subject_id_foreign` (`subject_id`),
  KEY `grading_configurations_term_id_foreign` (`term_id`),
  KEY `grading_configurations_created_by_foreign` (`created_by`),
  KEY `grading_configurations_updated_by_foreign` (`updated_by`),
  KEY `grading_config_lookup` (`academic_year_id`,`grade_level_id`,`subject_id`,`term_id`,`status`),
  CONSTRAINT `grading_configurations_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `grading_configurations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grading_configurations_grade_level_id_foreign` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `grading_configurations_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `grading_configurations_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `grading_configurations_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grading_configurations`
--

LOCK TABLES `grading_configurations` WRITE;
/*!40000 ALTER TABLE `grading_configurations` DISABLE KEYS */;
INSERT INTO `grading_configurations` VALUES (1,1,1,1,NULL,'Demo grading configuration','active',75.00,'total_points',0,2,NULL,2,'2026-10-03 08:55:39','2026-10-03 10:06:16'),(4,1,1,3,1,'English Literature - Term 1','active',75.00,'total_points',0,2,2,2,'2026-10-03 11:35:42','2026-10-03 11:36:06');
/*!40000 ALTER TABLE `grading_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grading_components`
--

DROP TABLE IF EXISTS `grading_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grading_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grading_configuration_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grading_components_grading_configuration_id_foreign` (`grading_configuration_id`),
  CONSTRAINT `grading_components_grading_configuration_id_foreign` FOREIGN KEY (`grading_configuration_id`) REFERENCES `grading_configurations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grading_components`
--

LOCK TABLES `grading_components` WRITE;
/*!40000 ALTER TABLE `grading_components` DISABLE KEYS */;
INSERT INTO `grading_components` VALUES (1,1,'Written Assessments','written',30.00,1,'2026-10-03 08:55:40','2026-10-03 08:55:40'),(2,1,'Performance Tasks','performance_task',30.00,2,'2026-10-03 08:55:40','2026-10-03 08:55:40'),(3,1,'Summative Assessments','summative',20.00,3,'2026-10-03 08:55:40','2026-10-03 08:55:40'),(4,1,'Term Examination','term_exam',20.00,4,'2026-10-03 08:55:40','2026-10-03 08:55:40'),(21,4,'Written Assessments','written',40.00,1,'2026-10-03 11:36:06','2026-10-03 11:36:06'),(22,4,'Performance Tasks','performance_task',40.00,2,'2026-10-03 11:36:06','2026-10-03 11:36:06'),(23,4,'Summative Assessments','summative',10.00,3,'2026-10-03 11:36:06','2026-10-03 11:36:06'),(24,4,'Term Examination','term_exam',10.00,4,'2026-10-03 11:36:06','2026-10-03 11:36:06');
/*!40000 ALTER TABLE `grading_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_assignment_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `grading_component_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `assessment_date` date DEFAULT NULL,
  `total_items` decimal(6,2) NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessments_term_id_foreign` (`term_id`),
  KEY `assessments_grading_component_id_foreign` (`grading_component_id`),
  KEY `assessments_created_by_foreign` (`created_by`),
  KEY `assessments_updated_by_foreign` (`updated_by`),
  KEY `assessments_class_assignment_id_term_id_index` (`class_assignment_id`,`term_id`),
  CONSTRAINT `assessments_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assessments_grading_component_id_foreign` FOREIGN KEY (`grading_component_id`) REFERENCES `grading_components` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessments_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessments`
--

LOCK TABLES `assessments` WRITE;
/*!40000 ALTER TABLE `assessments` DISABLE KEYS */;
INSERT INTO `assessments` VALUES (8,1,1,1,'Quiz 1',NULL,'2026-09-01',20.00,'active',2,2,'2026-10-03 11:45:55','2026-10-03 11:45:55',NULL),(9,1,1,2,'PT 1',NULL,'2026-09-05',30.00,'active',2,2,'2026-10-03 11:47:48','2026-10-03 11:47:48',NULL),(10,1,1,1,'Quiz 2',NULL,NULL,10.00,'active',2,2,'2026-10-03 11:58:31','2026-10-03 11:58:31',NULL);
/*!40000 ALTER TABLE `assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_scores`
--

DROP TABLE IF EXISTS `assessment_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_scores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `raw_score` decimal(6,2) DEFAULT NULL,
  `percentage_score` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_scores_assessment_id_student_id_unique` (`assessment_id`,`student_id`),
  KEY `assessment_scores_student_id_index` (`student_id`),
  CONSTRAINT `assessment_scores_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_scores_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_scores`
--

LOCK TABLES `assessment_scores` WRITE;
/*!40000 ALTER TABLE `assessment_scores` DISABLE KEYS */;
INSERT INTO `assessment_scores` VALUES (8,8,6,10.00,50.00,NULL,'2026-10-03 11:46:09','2026-10-03 11:46:09'),(9,8,5,10.00,50.00,NULL,'2026-10-03 11:46:09','2026-10-03 11:46:09'),(10,8,2,10.00,50.00,NULL,'2026-10-03 11:46:09','2026-10-03 11:46:09'),(11,9,6,10.00,33.33,NULL,'2026-10-03 11:48:12','2026-10-03 11:48:12'),(12,9,5,10.00,33.33,NULL,'2026-10-03 11:48:12','2026-10-03 11:48:12'),(13,9,2,10.00,33.33,NULL,'2026-10-03 11:48:12','2026-10-03 11:48:12'),(14,10,6,10.00,100.00,NULL,'2026-10-03 11:58:44','2026-10-03 11:58:44'),(15,10,5,10.00,100.00,NULL,'2026-10-03 11:58:44','2026-10-03 11:58:44'),(16,10,2,10.00,100.00,NULL,'2026-10-03 11:58:44','2026-10-03 11:58:44');
/*!40000 ALTER TABLE `assessment_scores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `term_grades`
--

DROP TABLE IF EXISTS `term_grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `term_grades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_assignment_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `grading_configuration_id` bigint unsigned NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `is_provisional` tinyint(1) NOT NULL DEFAULT '0',
  `breakdown` json DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `finalized_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `term_grades_unique` (`student_id`,`class_assignment_id`,`term_id`),
  KEY `term_grades_class_assignment_id_foreign` (`class_assignment_id`),
  KEY `term_grades_term_id_foreign` (`term_id`),
  KEY `term_grades_grading_configuration_id_foreign` (`grading_configuration_id`),
  KEY `term_grades_finalized_by_foreign` (`finalized_by`),
  CONSTRAINT `term_grades_class_assignment_id_foreign` FOREIGN KEY (`class_assignment_id`) REFERENCES `class_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `term_grades_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `term_grades_grading_configuration_id_foreign` FOREIGN KEY (`grading_configuration_id`) REFERENCES `grading_configurations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `term_grades_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `term_grades_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `term_grades`
--

LOCK TABLES `term_grades` WRITE;
/*!40000 ALTER TABLE `term_grades` DISABLE KEYS */;
/*!40000 ALTER TABLE `term_grades` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (17,2,'App\\Models\\GradingConfiguration',4,'created',NULL,'{\"name\": \"English Literature - Term 1\", \"status\": \"draft\", \"term_id\": 1, \"components\": [{\"name\": \"Written Assessments\", \"type\": \"written\", \"weight\": 40, \"sort_order\": 1}, {\"name\": \"Performance Tasks\", \"type\": \"performance_task\", \"weight\": 40, \"sort_order\": 2}, {\"name\": \"Summative Assessments\", \"type\": \"summative\", \"weight\": 10, \"sort_order\": 3}, {\"name\": \"Term Examination\", \"type\": \"term_exam\", \"weight\": 5, \"sort_order\": 4}], \"subject_id\": 3, \"passing_grade\": 75, \"grade_level_id\": 1, \"academic_year_id\": 1, \"rounding_decimals\": 2, \"use_transmutation\": false, \"category_average_method\": \"total_points\"}','192.168.10.1','2026-10-03 11:35:42'),(18,2,'App\\Models\\GradingConfiguration',4,'updated','{\"name\": \"English Literature - Term 1\", \"status\": \"draft\", \"term_id\": 1, \"components\": [{\"name\": \"Written Assessments\", \"type\": \"written\", \"weight\": 40, \"sort_order\": 1}, {\"name\": \"Performance Tasks\", \"type\": \"performance_task\", \"weight\": 40, \"sort_order\": 2}, {\"name\": \"Summative Assessments\", \"type\": \"summative\", \"weight\": 10, \"sort_order\": 3}, {\"name\": \"Term Examination\", \"type\": \"term_exam\", \"weight\": 5, \"sort_order\": 4}], \"subject_id\": 3, \"passing_grade\": 75, \"grade_level_id\": 1, \"academic_year_id\": 1, \"rounding_decimals\": 2, \"use_transmutation\": false, \"category_average_method\": \"total_points\"}','{\"name\": \"English Literature - Term 1\", \"status\": \"active\", \"term_id\": 1, \"components\": [{\"name\": \"Written Assessments\", \"type\": \"written\", \"weight\": 40, \"sort_order\": 1}, {\"name\": \"Performance Tasks\", \"type\": \"performance_task\", \"weight\": 40, \"sort_order\": 2}, {\"name\": \"Summative Assessments\", \"type\": \"summative\", \"weight\": 10, \"sort_order\": 3}, {\"name\": \"Term Examination\", \"type\": \"term_exam\", \"weight\": 10, \"sort_order\": 4}], \"subject_id\": 3, \"passing_grade\": 75, \"grade_level_id\": 1, \"academic_year_id\": 1, \"rounding_decimals\": 2, \"use_transmutation\": false, \"category_average_method\": \"total_points\"}','192.168.10.1','2026-10-03 11:36:06'),(19,2,'App\\Models\\GradingConfiguration',5,'created',NULL,'{\"name\": \"English Literature - Term 1 (copy)\", \"status\": \"draft\", \"term_id\": 1, \"components\": [{\"name\": \"Written Assessments\", \"type\": \"written\", \"weight\": 40, \"sort_order\": 1}, {\"name\": \"Performance Tasks\", \"type\": \"performance_task\", \"weight\": 40, \"sort_order\": 2}, {\"name\": \"Summative Assessments\", \"type\": \"summative\", \"weight\": 10, \"sort_order\": 3}, {\"name\": \"Term Examination\", \"type\": \"term_exam\", \"weight\": 10, \"sort_order\": 4}], \"subject_id\": 3, \"passing_grade\": 75, \"grade_level_id\": 1, \"academic_year_id\": 1, \"rounding_decimals\": 2, \"use_transmutation\": false, \"category_average_method\": \"total_points\"}','192.168.10.1','2026-10-03 11:36:29'),(20,2,'App\\Models\\GradingConfiguration',5,'deleted','{\"name\": \"English Literature - Term 1 (copy)\", \"status\": \"draft\", \"term_id\": 1, \"components\": [{\"name\": \"Written Assessments\", \"type\": \"written\", \"weight\": 40, \"sort_order\": 1}, {\"name\": \"Performance Tasks\", \"type\": \"performance_task\", \"weight\": 40, \"sort_order\": 2}, {\"name\": \"Summative Assessments\", \"type\": \"summative\", \"weight\": 10, \"sort_order\": 3}, {\"name\": \"Term Examination\", \"type\": \"term_exam\", \"weight\": 10, \"sort_order\": 4}], \"subject_id\": 3, \"passing_grade\": 75, \"grade_level_id\": 1, \"academic_year_id\": 1, \"rounding_decimals\": 2, \"use_transmutation\": false, \"category_average_method\": \"total_points\"}',NULL,'192.168.10.1','2026-10-03 11:36:42'),(21,2,'App\\Models\\Assessment',8,'created',NULL,'{\"title\": \"Quiz 1\", \"term_id\": 1, \"total_items\": 20, \"grading_component_id\": 1}','192.168.10.1','2026-10-03 11:45:55'),(22,2,'App\\Models\\AssessmentScore',8,'created',NULL,'{\"raw_score\": 10, \"student_id\": 6, \"assessment_id\": 8, \"percentage_score\": 50}','192.168.10.1','2026-10-03 11:46:09'),(23,2,'App\\Models\\AssessmentScore',9,'created',NULL,'{\"raw_score\": 10, \"student_id\": 5, \"assessment_id\": 8, \"percentage_score\": 50}','192.168.10.1','2026-10-03 11:46:09'),(24,2,'App\\Models\\AssessmentScore',10,'created',NULL,'{\"raw_score\": 10, \"student_id\": 2, \"assessment_id\": 8, \"percentage_score\": 50}','192.168.10.1','2026-10-03 11:46:09'),(25,2,'App\\Models\\Assessment',9,'created',NULL,'{\"title\": \"PT 1\", \"term_id\": 1, \"total_items\": 30, \"grading_component_id\": 2}','192.168.10.1','2026-10-03 11:47:48'),(26,2,'App\\Models\\AssessmentScore',11,'created',NULL,'{\"raw_score\": 10, \"student_id\": 6, \"assessment_id\": 9, \"percentage_score\": 33.33}','192.168.10.1','2026-10-03 11:48:12'),(27,2,'App\\Models\\AssessmentScore',12,'created',NULL,'{\"raw_score\": 10, \"student_id\": 5, \"assessment_id\": 9, \"percentage_score\": 33.33}','192.168.10.1','2026-10-03 11:48:12'),(28,2,'App\\Models\\AssessmentScore',13,'created',NULL,'{\"raw_score\": 10, \"student_id\": 2, \"assessment_id\": 9, \"percentage_score\": 33.33}','192.168.10.1','2026-10-03 11:48:12'),(29,2,'App\\Models\\Assessment',10,'created',NULL,'{\"title\": \"Quiz 2\", \"term_id\": 1, \"total_items\": 10, \"grading_component_id\": 1}','192.168.10.1','2026-10-03 11:58:31'),(30,2,'App\\Models\\AssessmentScore',14,'created',NULL,'{\"raw_score\": 10, \"student_id\": 6, \"assessment_id\": 10, \"percentage_score\": 100}','192.168.10.1','2026-10-03 11:58:44'),(31,2,'App\\Models\\AssessmentScore',15,'created',NULL,'{\"raw_score\": 10, \"student_id\": 5, \"assessment_id\": 10, \"percentage_score\": 100}','192.168.10.1','2026-10-03 11:58:44'),(32,2,'App\\Models\\AssessmentScore',16,'created',NULL,'{\"raw_score\": 10, \"student_id\": 2, \"assessment_id\": 10, \"percentage_score\": 100}','192.168.10.1','2026-10-03 11:58:45');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-04 11:50:28
