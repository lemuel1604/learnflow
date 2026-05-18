-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: learnflow_db
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
-- Table structure for table `academic_terms`
--

DROP TABLE IF EXISTS `academic_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_terms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(80) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_term` (`academic_year`,`semester`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_terms`
--

LOCK TABLES `academic_terms` WRITE;
/*!40000 ALTER TABLE `academic_terms` DISABLE KEYS */;
INSERT INTO `academic_terms` VALUES (1,'1st Semester AY 2025-2026','2025-2026','1st','2025-08-01','2025-12-31',0,'2026-05-07 09:04:06'),(2,'2nd Semester AY 2025-2026','2025-2026','2nd','2026-01-05','2026-05-31',1,'2026-05-07 09:04:06'),(3,'Summer Term AY 2025-2026','2025-2026','Summer','2026-06-02','2026-07-25',0,'2026-05-07 01:04:06'),(4,'1st Semester AY 2024-2025','2024-2025','1st','2024-08-05','2024-12-20',0,'2024-08-01 00:00:00'),(5,'2nd Semester AY 2024-2025','2024-2025','2nd','2025-01-06','2025-05-30',0,'2025-01-03 00:00:00');
/*!40000 ALTER TABLE `academic_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_reads`
--

DROP TABLE IF EXISTS `announcement_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_reads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ar` (`announcement_id`,`user_id`),
  KEY `idx_ar_user` (`user_id`),
  CONSTRAINT `fk_ar_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ar_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_reads`
--

LOCK TABLES `announcement_reads` WRITE;
/*!40000 ALTER TABLE `announcement_reads` DISABLE KEYS */;
INSERT INTO `announcement_reads` VALUES (1,1,37,'2026-05-01 01:00:00'),(2,1,38,'2026-05-01 01:05:00'),(3,1,39,'2026-05-01 01:10:00'),(4,1,40,'2026-05-01 01:30:00'),(5,1,41,'2026-05-01 02:00:00'),(6,1,42,'2026-05-01 02:15:00'),(7,2,37,'2026-05-04 02:00:00'),(8,2,38,'2026-05-04 02:05:00'),(9,2,39,'2026-05-04 02:10:00'),(10,2,44,'2026-05-04 03:00:00'),(11,2,45,'2026-05-04 03:15:00'),(12,3,43,'2026-05-05 03:00:00'),(13,3,37,'2026-05-05 03:05:00'),(14,3,38,'2026-05-05 03:10:00'),(15,3,52,'2026-05-05 04:00:00'),(16,4,47,'2026-05-04 04:00:00'),(17,4,48,'2026-05-04 04:05:00'),(18,4,49,'2026-05-04 04:10:00'),(19,4,50,'2026-05-04 04:15:00'),(20,7,47,'2026-05-07 00:00:00'),(21,7,48,'2026-05-07 00:05:00'),(22,7,49,'2026-05-07 00:10:00'),(23,8,55,'2026-04-30 03:00:00'),(24,8,56,'2026-04-30 03:05:00'),(25,12,55,'2026-05-10 01:00:00'),(26,12,56,'2026-05-10 01:10:00'),(27,19,37,'2026-05-11 01:00:00'),(28,19,38,'2026-05-11 01:05:00'),(29,19,47,'2026-05-11 01:10:00'),(30,19,61,'2026-05-11 01:15:00');
/*!40000 ALTER TABLE `announcement_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_targets`
--

DROP TABLE IF EXISTS `announcement_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_targets` (
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`announcement_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_targets`
--

LOCK TABLES `announcement_targets` WRITE;
/*!40000 ALTER TABLE `announcement_targets` DISABLE KEYS */;
INSERT INTO `announcement_targets` VALUES (20,37),(20,38),(20,39),(20,40),(20,41),(20,42),(20,43),(20,44),(20,45),(20,46),(20,47),(20,48),(20,49),(20,50),(20,51),(20,52),(20,53),(20,54),(20,55),(20,56),(20,61),(20,62),(20,63),(20,64),(20,65),(20,66),(20,67),(20,68),(20,69),(20,70);
/*!40000 ALTER TABLE `announcement_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(10) unsigned NOT NULL,
  `scope` enum('platform','department','section') NOT NULL,
  `scope_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `body` longtext NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_scope` (`scope`,`scope_id`),
  KEY `idx_published` (`published_at`),
  KEY `fk_ann_author` (`author_id`),
  CONSTRAINT `fk_ann_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,36,'section',1,'Lab 5 Posted - Exception Handling','Lab Activity 5 on Exception Handling has been posted. Please submit via the Assignments tab before May 10. Review Chapter 9 of the textbook as preparation.',1,'2026-05-01 08:00:00',NULL,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(2,36,'section',1,'Midterm Exam Coverage','The midterm will cover Chapters 1-6: Classes, Inheritance, Polymorphism, Interfaces, and Abstract Classes. Bring your student ID and a laptop for the coding portion.',0,'2026-05-04 09:00:00',NULL,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(3,36,'section',2,'Project 2 Requirements Updated','The requirements for Project 2 (Responsive Portfolio Site) have been updated. Check the Assignments section for the revised rubric. Deadline remains April 28.',1,'2026-05-05 10:00:00',NULL,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(4,36,'section',3,'Extra Credit Opportunity','Students who submit a bonus analysis of sorting algorithm complexities (Big-O) will receive 5 extra points on the finals. Submit by May 15.',0,'2026-05-04 11:00:00',NULL,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(5,36,'section',1,'OOP Lab Quiz 2 Grades Released','Grades for Lab Quiz 2 are now available. Average score was 83%. Please review the feedback in your submission portal.',0,'2026-04-09 08:00:00',NULL,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(6,36,'section',2,'Web Quiz 3 Results Available','Results for Web Programming Quiz 3 have been released. Class average was 81%. Well done! Students below 75 please see me during consultation hours.',0,'2026-04-21 09:00:00',NULL,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(7,36,'section',3,'Midterm Reminder - Sorting Quiz Live','The Sorting and Searching Quiz is now live and available until May 14. You have 20 minutes to complete it. Good luck!',1,'2026-05-07 07:00:00',NULL,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(8,36,'section',4,'Capstone Proposal Submission Reminder','Reminder: Capstone Proposal drafts are due May 5. Feedback will be given within 5 working days. Please follow the document template provided.',0,'2026-04-30 10:00:00',NULL,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(9,36,'section',1,'Lab Activity 6 Now Available','Lab Activity 6 on Java Collections Framework has been posted. Please review the ArrayList and HashMap documentation before starting. Due May 24.',1,'2026-05-12 08:00:00',NULL,'2026-05-12 00:00:00','2026-05-12 00:00:00'),(10,36,'section',2,'Project 3 Groups Announced','Project 3 groups have been finalized and posted in the Resources tab. Please coordinate with your group members immediately. Deadline is May 20.',1,'2026-05-02 09:00:00',NULL,'2026-05-02 01:00:00','2026-05-02 01:00:00'),(11,36,'section',3,'Problem Set 4 Released','Problem Set 4 covering Trees and Graphs is now available. Focus on BST operations and BFS/DFS traversal. Due May 15. No extensions allowed.',0,'2026-05-02 10:00:00',NULL,'2026-05-02 02:00:00','2026-05-02 02:00:00'),(12,36,'section',4,'Capstone Defense Schedule','Preliminary defense schedules will be released by May 20. Ensure Chapter 2 is submitted beforehand. Review format requirements in the Capstone Handbook.',1,'2026-05-10 08:00:00',NULL,'2026-05-10 00:00:00','2026-05-10 00:00:00'),(13,57,'section',6,'Lab 3 Released: Routing Protocol Config','Lab 3 on OSPF routing configuration is now available in Cisco Packet Tracer format. Download the starter file from the Modules section. Due April 23.',1,'2026-04-07 08:00:00',NULL,'2026-04-07 00:00:00','2026-04-07 00:00:00'),(14,57,'section',6,'Midterm Results Posted','Midterm exam results are now available. Class average was 79.2%. Students below 65 are advised to schedule a consultation. Strong performance overall!',0,'2026-04-14 09:00:00',NULL,'2026-04-14 01:00:00','2026-04-14 01:00:00'),(15,57,'section',7,'Sprint 2 Submission Guidelines Updated','Updated UML diagram requirements for Sprint 2 have been posted. Ensure all sequence diagrams follow the revised notation. See Resources for the updated rubric.',0,'2026-04-01 08:00:00',NULL,'2026-04-01 00:00:00','2026-04-01 00:00:00'),(16,59,'section',9,'SQL Lab 3 Available','SQL Lab 3 covering stored procedures, views, and triggers is now open. Use the provided hospital database schema as your base. Due May 5.',1,'2026-04-21 08:00:00',NULL,'2026-04-21 00:00:00','2026-04-21 00:00:00'),(17,59,'section',9,'SQL Lab 2 Grades Released','Grades for SQL Lab 2 are now posted. Class average was 85%. Detailed feedback available in the Grades tab. Common issue: missing HAVING clause in aggregation queries.',0,'2026-04-10 09:00:00',NULL,'2026-04-10 01:00:00','2026-04-10 01:00:00'),(18,60,'section',13,'Midterm Reviewer Posted','The midterm reviewer covering Chapters 1-4 (Number Theory, Logic, Statistics, and Financial Math) has been uploaded to the Modules section. Exam is on March 20.',1,'2026-03-13 08:00:00',NULL,'2026-03-13 00:00:00','2026-03-13 00:00:00'),(19,36,'platform',NULL,'End-of-Semester Reminder','Finals week is approaching! Please review your submission statuses in each course. Missing submissions will receive a grade of 0. Contact your instructors for any concerns.',1,'2026-05-11 08:00:00',NULL,'2026-05-11 00:00:00','2026-05-11 00:00:00'),(20,1,'platform',NULL,'Platform Maintenance - May 15','LearnFlow will undergo scheduled maintenance on May 15, 2026, from 1:00 AM to 5:00 AM. Please download any needed materials beforehand. We apologize for the inconvenience.',0,'2026-05-11 09:00:00','2026-05-16 00:00:00','2026-05-11 01:00:00','2026-05-11 01:00:00');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_attachments`
--

DROP TABLE IF EXISTS `assignment_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_attachments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` int(10) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_size_kb` int(10) unsigned DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aa_assignment` (`assignment_id`),
  CONSTRAINT `fk_aa_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_attachments`
--

LOCK TABLES `assignment_attachments` WRITE;
/*!40000 ALTER TABLE `assignment_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `instructions` longtext DEFAULT NULL,
  `assignment_type` enum('individual','group') NOT NULL DEFAULT 'individual',
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
  `passing_score` decimal(6,2) NOT NULL DEFAULT 60.00,
  `due_date` datetime NOT NULL,
  `allow_late` tinyint(1) NOT NULL DEFAULT 0,
  `late_penalty_pct` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_section_due` (`section_id`,`due_date`),
  CONSTRAINT `fk_asgn_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
INSERT INTO `assignments` VALUES (1,1,'Lab Activity 5: Exception Handling','Implement a Java program demonstrating try-catch-finally, custom exceptions, and multi-catch blocks. Submit your .java source files and a brief report explaining your exception strategy.','individual',100.00,75.00,'2026-05-10 23:59:00',1,0,'published','2026-05-08 10:54:34','2026-05-08 10:54:34'),(2,2,'Project 2: Responsive Portfolio Site','Build a fully responsive personal portfolio website using HTML5, CSS Grid/Flexbox, and vanilla JavaScript. Must include a hero section, projects gallery, and contact form.','individual',100.00,75.00,'2026-04-28 23:59:00',0,0,'published','2026-05-08 10:54:34','2026-05-08 10:54:34'),(3,3,'Problem Set 3: Sorting Algorithms','Implement Bubble Sort, Selection Sort, Merge Sort, and Quick Sort in Java. Include Big-O analysis for each and a comparison table of their time complexities.','individual',100.00,75.00,'2026-04-25 23:59:00',1,0,'published','2026-05-08 10:54:34','2026-05-08 10:54:34'),(4,4,'Capstone Proposal Draft','Submit a 5-8 page research proposal for your capstone project including problem statement, objectives, scope, methodology, and a preliminary review of related literature.','individual',100.00,75.00,'2026-05-05 23:59:00',1,0,'published','2026-05-08 10:54:34','2026-05-08 10:54:34'),(5,1,'Lab Activity 4: Inheritance and Polymorphism','Create a class hierarchy demonstrating inheritance, method overriding, and polymorphism. Implement at least 3 levels of inheritance with a real-world domain (e.g., shapes, animals, vehicles).','individual',100.00,75.00,'2026-04-20 23:59:00',1,0,'closed','2026-05-08 10:54:34','2026-05-08 10:54:34'),(6,2,'module 10','please pass on time','individual',100.00,60.00,'2026-05-12 00:00:00',1,0,'published','2026-05-11 09:27:19','2026-05-11 09:27:19'),(7,2,'Project 3: Full-Stack CRUD App','Build a full-stack CRUD web application using HTML, CSS, JavaScript, and a simple PHP/MySQL backend.','group',100.00,75.00,'2026-05-20 23:59:00',1,10,'published','2026-05-01 00:00:00','2026-05-01 00:00:00'),(8,3,'Problem Set 4: Trees and Graphs','Implement BST insert/delete/search and BFS/DFS graph traversal. Include time complexity analysis.','individual',100.00,75.00,'2026-05-15 23:59:00',1,5,'published','2026-05-01 00:00:00','2026-05-01 00:00:00'),(9,4,'Capstone Chapter 2 - RRL','Submit a 10-page Review of Related Literature with at least 15 recent references (APA format).','individual',100.00,75.00,'2026-05-20 23:59:00',1,0,'published','2026-04-15 00:00:00','2026-04-15 00:00:00'),(10,6,'Lab 1: Network Topology Design','Use Cisco Packet Tracer to design and configure a small office network with proper IP addressing.','individual',100.00,75.00,'2026-03-05 23:59:00',0,0,'closed','2026-02-20 00:00:00','2026-02-20 00:00:00'),(11,6,'Lab 2: Subnetting Worksheet','Complete the subnetting exercises for Class A, B, and C networks and provide your calculations.','individual',100.00,75.00,'2026-03-26 23:59:00',1,5,'closed','2026-03-10 00:00:00','2026-03-10 00:00:00'),(12,6,'Lab 3: Routing Protocol Configuration','Configure OSPF routing between three routers using Cisco IOS commands in Packet Tracer.','individual',100.00,75.00,'2026-04-23 23:59:00',1,5,'published','2026-04-07 00:00:00','2026-04-07 00:00:00'),(13,7,'Sprint 1 - Requirements Document','Produce a Software Requirements Specification (SRS) document for your chosen project following IEEE standards.','group',100.00,75.00,'2026-03-10 23:59:00',0,0,'closed','2026-02-24 00:00:00','2026-02-24 00:00:00'),(14,7,'Sprint 2 - System Design Document','Create UML class diagrams, sequence diagrams, and an ER diagram for your proposed system.','group',100.00,75.00,'2026-04-14 23:59:00',1,5,'published','2026-03-30 00:00:00','2026-03-30 00:00:00'),(15,9,'SQL Lab 1: DDL and DML Queries','Create tables with proper constraints and perform INSERT, UPDATE, DELETE, and SELECT operations.','individual',100.00,75.00,'2026-03-10 23:59:00',0,0,'closed','2026-02-24 00:00:00','2026-02-24 00:00:00'),(16,9,'SQL Lab 2: Joins and Subqueries','Write complex SQL queries involving INNER JOIN, LEFT JOIN, nested subqueries, and aggregate functions.','individual',100.00,75.00,'2026-04-07 23:59:00',1,5,'published','2026-03-24 00:00:00','2026-03-24 00:00:00'),(17,9,'SQL Lab 3: Stored Procedures and Views','Implement stored procedures, functions, and views. Include one trigger for audit logging.','individual',100.00,75.00,'2026-05-05 23:59:00',1,5,'published','2026-04-21 00:00:00','2026-04-21 00:00:00'),(18,15,'Lab Activity 1: Binary Conversion','Convert 20 decimal numbers to binary, octal, and hexadecimal. Show complete step-by-step solutions.','individual',50.00,30.00,'2026-03-05 23:59:00',1,0,'closed','2026-02-20 00:00:00','2026-02-20 00:00:00'),(19,15,'Lab Activity 2: Hardware Components','Identify and label the components of a motherboard diagram. Research the function of each component.','individual',50.00,30.00,'2026-04-02 23:59:00',0,0,'published','2026-03-17 00:00:00','2026-03-17 00:00:00'),(20,2,'Midterm Exam - Web Programming','Take-home midterm covering HTML5 semantics, CSS Grid/Flexbox layouts, and basic JavaScript DOM manipulation.','individual',100.00,75.00,'2026-03-28 23:59:00',0,0,'closed','2026-03-18 00:00:00','2026-03-18 00:00:00'),(21,2,'my assignment','do this','individual',100.00,60.00,'2026-05-12 00:00:00',1,0,'published','2026-05-12 03:56:20','2026-05-12 03:56:20');
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_assignments_after_insert` AFTER INSERT ON `assignments` FOR EACH ROW BEGIN

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

  VALUES (NULL, 'assignment_created', 'assignments', NEW.id,

          JSON_OBJECT('section_id', NEW.section_id, 'title', NEW.title));

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_assignments_after_update` AFTER UPDATE ON `assignments` FOR EACH ROW BEGIN

  IF OLD.status <> NEW.status OR OLD.due_date <> NEW.due_date THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NULL, 'assignment_updated', 'assignments', NEW.id,

            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status,

                        'old_due', OLD.due_date, 'new_due', NEW.due_date));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detail`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=536 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (189,37,'user_registered','users',37,'{\"email\": \"gabriel_ryza@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(190,38,'user_registered','users',38,'{\"email\": \"sarmiento_aric@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(191,39,'user_registered','users',39,'{\"email\": \"abalos_nicole@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(192,40,'user_registered','users',40,'{\"email\": \"antipolo_micah@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(193,41,'user_registered','users',41,'{\"email\": \"ordaniel_win@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(194,42,'user_registered','users',42,'{\"email\": \"delacruz_juan@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(195,43,'user_registered','users',43,'{\"email\": \"santos_maria@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(196,44,'user_registered','users',44,'{\"email\": \"bautista_carlo@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(197,45,'user_registered','users',45,'{\"email\": \"reyes_ana@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(198,46,'user_registered','users',46,'{\"email\": \"torres_mark@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(199,47,'user_registered','users',47,'{\"email\": \"cruz_rico@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(200,48,'user_registered','users',48,'{\"email\": \"park_lena@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(201,49,'user_registered','users',49,'{\"email\": \"bautista_mario@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(202,50,'user_registered','users',50,'{\"email\": \"santos_karl@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(203,51,'user_registered','users',51,'{\"email\": \"castro_ana@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(204,52,'user_registered','users',52,'{\"email\": \"bello_wren@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(205,53,'user_registered','users',53,'{\"email\": \"aguilar_yvonne@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(206,54,'user_registered','users',54,'{\"email\": \"navarro_arlo@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(207,55,'user_registered','users',55,'{\"email\": \"cruz_abby@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(208,56,'user_registered','users',56,'{\"email\": \"reyes_bruno@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-08 10:54:34'),(209,37,'enrollment_created','enrollments',1,'{\"student_id\": 37, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(210,38,'enrollment_created','enrollments',2,'{\"student_id\": 38, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(211,39,'enrollment_created','enrollments',3,'{\"student_id\": 39, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(212,40,'enrollment_created','enrollments',4,'{\"student_id\": 40, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(213,41,'enrollment_created','enrollments',5,'{\"student_id\": 41, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(214,42,'enrollment_created','enrollments',6,'{\"student_id\": 42, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(215,43,'enrollment_created','enrollments',7,'{\"student_id\": 43, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(216,44,'enrollment_created','enrollments',8,'{\"student_id\": 44, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(217,45,'enrollment_created','enrollments',9,'{\"student_id\": 45, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(218,46,'enrollment_created','enrollments',10,'{\"student_id\": 46, \"section_id\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(219,37,'enrollment_created','enrollments',11,'{\"student_id\": 37, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(220,38,'enrollment_created','enrollments',12,'{\"student_id\": 38, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(221,39,'enrollment_created','enrollments',13,'{\"student_id\": 39, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(222,43,'enrollment_created','enrollments',14,'{\"student_id\": 43, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(223,52,'enrollment_created','enrollments',15,'{\"student_id\": 52, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(224,53,'enrollment_created','enrollments',16,'{\"student_id\": 53, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(225,54,'enrollment_created','enrollments',17,'{\"student_id\": 54, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(226,44,'enrollment_created','enrollments',18,'{\"student_id\": 44, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(227,45,'enrollment_created','enrollments',19,'{\"student_id\": 45, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(228,41,'enrollment_created','enrollments',20,'{\"student_id\": 41, \"section_id\": 2}',NULL,NULL,'2026-05-08 10:54:34'),(229,47,'enrollment_created','enrollments',21,'{\"student_id\": 47, \"section_id\": 3}',NULL,NULL,'2026-05-08 10:54:34'),(230,48,'enrollment_created','enrollments',22,'{\"student_id\": 48, \"section_id\": 3}',NULL,NULL,'2026-05-08 10:54:34'),(231,49,'enrollment_created','enrollments',23,'{\"student_id\": 49, \"section_id\": 3}',NULL,NULL,'2026-05-08 10:54:34'),(232,50,'enrollment_created','enrollments',24,'{\"student_id\": 50, \"section_id\": 3}',NULL,NULL,'2026-05-08 10:54:34'),(233,51,'enrollment_created','enrollments',25,'{\"student_id\": 51, \"section_id\": 3}',NULL,NULL,'2026-05-08 10:54:34'),(234,42,'enrollment_created','enrollments',26,'{\"student_id\": 42, \"section_id\": 3}',NULL,NULL,'2026-05-08 10:54:34'),(235,46,'enrollment_created','enrollments',27,'{\"student_id\": 46, \"section_id\": 3}',NULL,NULL,'2026-05-08 10:54:34'),(236,40,'enrollment_created','enrollments',28,'{\"student_id\": 40, \"section_id\": 3}',NULL,NULL,'2026-05-08 10:54:34'),(237,55,'enrollment_created','enrollments',29,'{\"student_id\": 55, \"section_id\": 4}',NULL,NULL,'2026-05-08 10:54:34'),(238,56,'enrollment_created','enrollments',30,'{\"student_id\": 56, \"section_id\": 4}',NULL,NULL,'2026-05-08 10:54:34'),(239,48,'enrollment_created','enrollments',31,'{\"student_id\": 48, \"section_id\": 4}',NULL,NULL,'2026-05-08 10:54:34'),(240,37,'enrollment_created','enrollments',32,'{\"student_id\": 37, \"section_id\": 4}',NULL,NULL,'2026-05-08 10:54:34'),(241,38,'enrollment_created','enrollments',33,'{\"student_id\": 38, \"section_id\": 4}',NULL,NULL,'2026-05-08 10:54:34'),(242,NULL,'assignment_created','assignments',1,'{\"section_id\": 1, \"title\": \"Lab Activity 5: Exception Handling\"}',NULL,NULL,'2026-05-08 10:54:34'),(243,NULL,'assignment_created','assignments',2,'{\"section_id\": 2, \"title\": \"Project 2: Responsive Portfolio Site\"}',NULL,NULL,'2026-05-08 10:54:34'),(244,NULL,'assignment_created','assignments',3,'{\"section_id\": 3, \"title\": \"Problem Set 3: Sorting Algorithms\"}',NULL,NULL,'2026-05-08 10:54:34'),(245,NULL,'assignment_created','assignments',4,'{\"section_id\": 4, \"title\": \"Capstone Proposal Draft\"}',NULL,NULL,'2026-05-08 10:54:34'),(246,NULL,'assignment_created','assignments',5,'{\"section_id\": 1, \"title\": \"Lab Activity 4: Inheritance and Polymorphism\"}',NULL,NULL,'2026-05-08 10:54:34'),(247,37,'submission_created','submissions',1,'{\"assignment_id\": 1, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(248,38,'submission_created','submissions',2,'{\"assignment_id\": 1, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(249,39,'submission_created','submissions',3,'{\"assignment_id\": 1, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(250,40,'submission_created','submissions',4,'{\"assignment_id\": 1, \"is_late\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(251,42,'submission_created','submissions',5,'{\"assignment_id\": 1, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(252,37,'submission_created','submissions',6,'{\"assignment_id\": 2, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(253,38,'submission_created','submissions',7,'{\"assignment_id\": 2, \"is_late\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(254,39,'submission_created','submissions',8,'{\"assignment_id\": 2, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(255,52,'submission_created','submissions',9,'{\"assignment_id\": 2, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(256,53,'submission_created','submissions',10,'{\"assignment_id\": 2, \"is_late\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(257,47,'submission_created','submissions',11,'{\"assignment_id\": 3, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(258,49,'submission_created','submissions',12,'{\"assignment_id\": 3, \"is_late\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(259,48,'submission_created','submissions',13,'{\"assignment_id\": 3, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(260,50,'submission_created','submissions',14,'{\"assignment_id\": 3, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(261,51,'submission_created','submissions',15,'{\"assignment_id\": 3, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(262,55,'submission_created','submissions',16,'{\"assignment_id\": 4, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(263,56,'submission_created','submissions',17,'{\"assignment_id\": 4, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(264,48,'submission_created','submissions',18,'{\"assignment_id\": 4, \"is_late\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(265,39,'submission_created','submissions',19,'{\"assignment_id\": 5, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(266,40,'submission_created','submissions',20,'{\"assignment_id\": 5, \"is_late\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(267,42,'submission_created','submissions',21,'{\"assignment_id\": 5, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(268,43,'submission_created','submissions',22,'{\"assignment_id\": 5, \"is_late\": 0}',NULL,NULL,'2026-05-08 10:54:34'),(269,37,'quiz_attempt_started','quiz_attempts',1,'{\"quiz_id\": 1, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(270,38,'quiz_attempt_started','quiz_attempts',2,'{\"quiz_id\": 1, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(271,39,'quiz_attempt_started','quiz_attempts',3,'{\"quiz_id\": 1, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(272,52,'quiz_attempt_started','quiz_attempts',4,'{\"quiz_id\": 1, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(273,53,'quiz_attempt_started','quiz_attempts',5,'{\"quiz_id\": 1, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(274,54,'quiz_attempt_started','quiz_attempts',6,'{\"quiz_id\": 1, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(275,44,'quiz_attempt_started','quiz_attempts',7,'{\"quiz_id\": 1, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(276,45,'quiz_attempt_started','quiz_attempts',8,'{\"quiz_id\": 1, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(277,37,'quiz_attempt_started','quiz_attempts',9,'{\"quiz_id\": 2, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(278,38,'quiz_attempt_started','quiz_attempts',10,'{\"quiz_id\": 2, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(279,39,'quiz_attempt_started','quiz_attempts',11,'{\"quiz_id\": 2, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(280,40,'quiz_attempt_started','quiz_attempts',12,'{\"quiz_id\": 2, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(281,42,'quiz_attempt_started','quiz_attempts',13,'{\"quiz_id\": 2, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(282,41,'quiz_attempt_started','quiz_attempts',14,'{\"quiz_id\": 2, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(283,44,'quiz_attempt_started','quiz_attempts',15,'{\"quiz_id\": 2, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(284,45,'quiz_attempt_started','quiz_attempts',16,'{\"quiz_id\": 2, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(285,47,'quiz_attempt_started','quiz_attempts',17,'{\"quiz_id\": 3, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(286,48,'quiz_attempt_started','quiz_attempts',18,'{\"quiz_id\": 3, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(287,49,'quiz_attempt_started','quiz_attempts',19,'{\"quiz_id\": 3, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(288,50,'quiz_attempt_started','quiz_attempts',20,'{\"quiz_id\": 3, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(289,51,'quiz_attempt_started','quiz_attempts',21,'{\"quiz_id\": 3, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(290,37,'quiz_attempt_started','quiz_attempts',22,'{\"quiz_id\": 5, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(291,38,'quiz_attempt_started','quiz_attempts',23,'{\"quiz_id\": 5, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(292,39,'quiz_attempt_started','quiz_attempts',24,'{\"quiz_id\": 5, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(293,40,'quiz_attempt_started','quiz_attempts',25,'{\"quiz_id\": 5, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(294,42,'quiz_attempt_started','quiz_attempts',26,'{\"quiz_id\": 5, \"attempt_number\": 1}',NULL,NULL,'2026-05-08 10:54:34'),(295,36,'course_created','courses',5,'{\"code\": \"IT103\", \"title\": \"Advance Database Management\"}',NULL,NULL,'2026-05-08 10:55:22'),(296,36,'course_updated','courses',5,'{\"old_status\": \"published\", \"new_status\": \"archived\", \"old_title\": \"Advance Database Management\", \"new_title\": \"Advance Database Management\"}',NULL,NULL,'2026-05-08 10:56:30'),(297,NULL,'quiz_created','quizzes',1,'{\"section_id\": 1, \"title\": \"asndafjmdlmas\"}',NULL,NULL,'2026-05-08 10:57:24'),(298,NULL,'quiz_created','quizzes',2,'{\"section_id\": 1, \"title\": \"asndafjmdlmas\"}',NULL,NULL,'2026-05-08 10:57:31'),(299,1,'magic_link_login','users',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 06:26:34'),(300,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 06:29:00'),(301,1,'magic_link_login','users',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 06:29:27'),(302,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 06:30:55'),(303,36,'course_updated','courses',5,'{\"old_status\": \"archived\", \"new_status\": \"published\", \"old_title\": \"Advance Database Management\", \"new_title\": \"Advance Database Management\"}',NULL,NULL,'2026-05-11 06:32:25'),(304,36,'course_updated','courses',2,'{\"old_status\": \"published\", \"new_status\": \"archived\", \"old_title\": \"Web Programming\", \"new_title\": \"Web Programming\"}',NULL,NULL,'2026-05-11 06:32:32'),(305,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:07:30'),(306,37,'submission_created','submissions',23,'{\"assignment_id\": 4, \"is_late\": 0}',NULL,NULL,'2026-05-11 07:07:53'),(307,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:08:32'),(308,NULL,'quiz_created','quizzes',3,'{\"section_id\": 4, \"title\": \"quiz 2\"}',NULL,NULL,'2026-05-11 07:12:45'),(309,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:13:16'),(310,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:13:51'),(311,NULL,'quiz_created','quizzes',4,'{\"section_id\": 1, \"title\": \"quiz 2\"}',NULL,NULL,'2026-05-11 07:14:34'),(312,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:14:48'),(313,1,'magic_link_login','users',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:16:01'),(314,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:37:53'),(315,NULL,'quiz_created','quizzes',5,'{\"section_id\": 1, \"title\": \"quiz 2\"}',NULL,NULL,'2026-05-11 07:39:05'),(316,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:39:26'),(317,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:40:19'),(318,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:41:35'),(319,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:50:58'),(320,NULL,'quiz_created','quizzes',6,'{\"section_id\": 1, \"title\": \"TESTING\"}',NULL,NULL,'2026-05-11 07:51:40'),(321,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:51:57'),(322,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:57:51'),(323,NULL,'quiz_created','quizzes',7,'{\"section_id\": 1, \"title\": \"test\"}',NULL,NULL,'2026-05-11 07:58:34'),(324,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 07:58:58'),(325,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 08:04:50'),(326,NULL,'quiz_created','quizzes',8,'{\"section_id\": 1, \"title\": \"test\"}',NULL,NULL,'2026-05-11 08:05:16'),(327,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 08:05:31'),(328,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 08:35:13'),(329,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 08:36:00'),(330,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 08:43:35'),(331,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 08:44:16'),(332,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-11 08:45:53'),(333,NULL,'assignment_created','assignments',6,'{\"section_id\": 2, \"title\": \"module 10\"}',NULL,NULL,'2026-05-11 09:27:19'),(334,37,'submission_created','submissions',24,'{\"assignment_id\": 6, \"is_late\": 0}',NULL,NULL,'2026-05-11 09:33:47'),(335,36,'course_updated','courses',1,'{\"old_status\": \"published\", \"new_status\": \"archived\", \"old_title\": \"Object-Oriented Programming\", \"new_title\": \"Object-Oriented Programming\"}',NULL,NULL,'2026-05-11 09:42:24'),(336,36,'course_updated','courses',2,'{\"old_status\": \"archived\", \"new_status\": \"published\", \"old_title\": \"Web Programming\", \"new_title\": \"Web Programming\"}',NULL,NULL,'2026-05-11 09:43:33'),(337,36,'course_updated','courses',1,'{\"old_status\": \"archived\", \"new_status\": \"published\", \"old_title\": \"Object-Oriented Programming\", \"new_title\": \"Object-Oriented Programming\"}',NULL,NULL,'2026-05-11 10:48:02'),(338,36,'course_updated','courses',4,'{\"old_status\": \"published\", \"new_status\": \"archived\", \"old_title\": \"Capstone Project\", \"new_title\": \"Capstone Project\"}',NULL,NULL,'2026-05-11 10:48:15'),(339,36,'course_updated','courses',4,'{\"old_status\": \"archived\", \"new_status\": \"published\", \"old_title\": \"Capstone Project\", \"new_title\": \"Capstone Project\"}',NULL,NULL,'2026-05-11 11:08:05'),(340,36,'course_updated','courses',4,'{\"old_status\": \"published\", \"new_status\": \"archived\", \"old_title\": \"Capstone Project\", \"new_title\": \"Capstone Project\"}',NULL,NULL,'2026-05-11 11:08:15'),(341,57,'user_registered','users',57,'{\"email\": \"delos_reyes_mark@plpasig.edu.ph\", \"role\": \"instructor\"}',NULL,NULL,'2026-05-11 11:42:12'),(342,58,'user_registered','users',58,'{\"email\": \"lim_grace@plpasig.edu.ph\", \"role\": \"instructor\"}',NULL,NULL,'2026-05-11 11:42:12'),(343,59,'user_registered','users',59,'{\"email\": \"ramos_julius@plpasig.edu.ph\", \"role\": \"instructor\"}',NULL,NULL,'2026-05-11 11:42:12'),(344,60,'user_registered','users',60,'{\"email\": \"villanueva_rose@plpasig.edu.ph\", \"role\": \"instructor\"}',NULL,NULL,'2026-05-11 11:42:12'),(345,61,'user_registered','users',61,'{\"email\": \"dela_pena_josh@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(346,62,'user_registered','users',62,'{\"email\": \"salazar_claire@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(347,63,'user_registered','users',63,'{\"email\": \"mendoza_ryan@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(348,64,'user_registered','users',64,'{\"email\": \"hernandez_paula@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(349,65,'user_registered','users',65,'{\"email\": \"ramos_felix@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(350,66,'user_registered','users',66,'{\"email\": \"garcia_lea@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(351,67,'user_registered','users',67,'{\"email\": \"aquino_lance@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(352,68,'user_registered','users',68,'{\"email\": \"miranda_liza@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(353,69,'user_registered','users',69,'{\"email\": \"austria_ivan@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(354,70,'user_registered','users',70,'{\"email\": \"rojas_diana@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(355,71,'user_registered','users',71,'{\"email\": \"navarro_carl@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(356,72,'user_registered','users',72,'{\"email\": \"padilla_faye@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(357,73,'user_registered','users',73,'{\"email\": \"santos_leo@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(358,74,'user_registered','users',74,'{\"email\": \"enriquez_nina@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(359,75,'user_registered','users',75,'{\"email\": \"morales_edgar@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(360,76,'user_registered','users',76,'{\"email\": \"flores_anna@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(361,77,'user_registered','users',77,'{\"email\": \"reyes_joseph@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(362,78,'user_registered','users',78,'{\"email\": \"guerrero_trisha@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(363,79,'user_registered','users',79,'{\"email\": \"valdez_kevin@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(364,80,'user_registered','users',80,'{\"email\": \"ocampo_joanna@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(365,81,'user_registered','users',81,'{\"email\": \"dela_cruz_daniel@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(366,82,'user_registered','users',82,'{\"email\": \"bernardo_camille@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(367,83,'user_registered','users',83,'{\"email\": \"espiritu_alvin@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(368,84,'user_registered','users',84,'{\"email\": \"santiago_rina@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(369,85,'user_registered','users',85,'{\"email\": \"chua_ben@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(370,86,'user_registered','users',86,'{\"email\": \"tan_mia@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(371,87,'user_registered','users',87,'{\"email\": \"lim_peter@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(372,88,'user_registered','users',88,'{\"email\": \"go_patricia@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(373,89,'user_registered','users',89,'{\"email\": \"uy_dennis@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(374,90,'user_registered','users',90,'{\"email\": \"sy_rachel@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(375,91,'user_registered','users',91,'{\"email\": \"tiu_harold@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(376,92,'user_registered','users',92,'{\"email\": \"ong_carla@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(377,93,'user_registered','users',93,'{\"email\": \"kho_victor@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(378,94,'user_registered','users',94,'{\"email\": \"chan_sheila@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(379,95,'user_registered','users',95,'{\"email\": \"ang_jerome@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(380,96,'user_registered','users',96,'{\"email\": \"yap_elaine@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-11 11:42:12'),(381,61,'enrollment_created','enrollments',34,'{\"student_id\": 61, \"section_id\": 6}',NULL,NULL,'2026-05-11 11:42:12'),(382,62,'enrollment_created','enrollments',35,'{\"student_id\": 62, \"section_id\": 6}',NULL,NULL,'2026-05-11 11:42:12'),(383,63,'enrollment_created','enrollments',36,'{\"student_id\": 63, \"section_id\": 6}',NULL,NULL,'2026-05-11 11:42:12'),(384,64,'enrollment_created','enrollments',37,'{\"student_id\": 64, \"section_id\": 6}',NULL,NULL,'2026-05-11 11:42:12'),(385,65,'enrollment_created','enrollments',38,'{\"student_id\": 65, \"section_id\": 6}',NULL,NULL,'2026-05-11 11:42:12'),(386,83,'enrollment_created','enrollments',39,'{\"student_id\": 83, \"section_id\": 6}',NULL,NULL,'2026-05-11 11:42:12'),(387,84,'enrollment_created','enrollments',40,'{\"student_id\": 84, \"section_id\": 6}',NULL,NULL,'2026-05-11 11:42:12'),(388,87,'enrollment_created','enrollments',41,'{\"student_id\": 87, \"section_id\": 6}',NULL,NULL,'2026-05-11 11:42:12'),(389,73,'enrollment_created','enrollments',42,'{\"student_id\": 73, \"section_id\": 7}',NULL,NULL,'2026-05-11 11:42:12'),(390,74,'enrollment_created','enrollments',43,'{\"student_id\": 74, \"section_id\": 7}',NULL,NULL,'2026-05-11 11:42:12'),(391,89,'enrollment_created','enrollments',44,'{\"student_id\": 89, \"section_id\": 7}',NULL,NULL,'2026-05-11 11:42:12'),(392,95,'enrollment_created','enrollments',45,'{\"student_id\": 95, \"section_id\": 7}',NULL,NULL,'2026-05-11 11:42:12'),(393,64,'enrollment_created','enrollments',46,'{\"student_id\": 64, \"section_id\": 7}',NULL,NULL,'2026-05-11 11:42:12'),(394,65,'enrollment_created','enrollments',47,'{\"student_id\": 65, \"section_id\": 7}',NULL,NULL,'2026-05-11 11:42:12'),(395,69,'enrollment_created','enrollments',48,'{\"student_id\": 69, \"section_id\": 8}',NULL,NULL,'2026-05-11 11:42:12'),(396,70,'enrollment_created','enrollments',49,'{\"student_id\": 70, \"section_id\": 8}',NULL,NULL,'2026-05-11 11:42:12'),(397,81,'enrollment_created','enrollments',50,'{\"student_id\": 81, \"section_id\": 8}',NULL,NULL,'2026-05-11 11:42:12'),(398,82,'enrollment_created','enrollments',51,'{\"student_id\": 82, \"section_id\": 8}',NULL,NULL,'2026-05-11 11:42:12'),(399,85,'enrollment_created','enrollments',52,'{\"student_id\": 85, \"section_id\": 8}',NULL,NULL,'2026-05-11 11:42:12'),(400,86,'enrollment_created','enrollments',53,'{\"student_id\": 86, \"section_id\": 8}',NULL,NULL,'2026-05-11 11:42:12'),(401,61,'enrollment_created','enrollments',54,'{\"student_id\": 61, \"section_id\": 9}',NULL,NULL,'2026-05-11 11:42:12'),(402,73,'enrollment_created','enrollments',55,'{\"student_id\": 73, \"section_id\": 9}',NULL,NULL,'2026-05-11 11:42:12'),(403,83,'enrollment_created','enrollments',56,'{\"student_id\": 83, \"section_id\": 9}',NULL,NULL,'2026-05-11 11:42:12'),(404,90,'enrollment_created','enrollments',57,'{\"student_id\": 90, \"section_id\": 9}',NULL,NULL,'2026-05-11 11:42:12'),(405,92,'enrollment_created','enrollments',58,'{\"student_id\": 92, \"section_id\": 9}',NULL,NULL,'2026-05-11 11:42:12'),(406,70,'enrollment_created','enrollments',59,'{\"student_id\": 70, \"section_id\": 10}',NULL,NULL,'2026-05-11 11:42:12'),(407,71,'enrollment_created','enrollments',60,'{\"student_id\": 71, \"section_id\": 10}',NULL,NULL,'2026-05-11 11:42:12'),(408,72,'enrollment_created','enrollments',61,'{\"student_id\": 72, \"section_id\": 10}',NULL,NULL,'2026-05-11 11:42:12'),(409,74,'enrollment_created','enrollments',62,'{\"student_id\": 74, \"section_id\": 10}',NULL,NULL,'2026-05-11 11:42:12'),(410,95,'enrollment_created','enrollments',63,'{\"student_id\": 95, \"section_id\": 10}',NULL,NULL,'2026-05-11 11:42:12'),(411,76,'enrollment_created','enrollments',64,'{\"student_id\": 76, \"section_id\": 11}',NULL,NULL,'2026-05-11 11:42:12'),(412,77,'enrollment_created','enrollments',65,'{\"student_id\": 77, \"section_id\": 11}',NULL,NULL,'2026-05-11 11:42:12'),(413,91,'enrollment_created','enrollments',66,'{\"student_id\": 91, \"section_id\": 11}',NULL,NULL,'2026-05-11 11:42:12'),(414,92,'enrollment_created','enrollments',67,'{\"student_id\": 92, \"section_id\": 11}',NULL,NULL,'2026-05-11 11:42:12'),(415,77,'enrollment_created','enrollments',68,'{\"student_id\": 77, \"section_id\": 12}',NULL,NULL,'2026-05-11 11:42:12'),(416,78,'enrollment_created','enrollments',69,'{\"student_id\": 78, \"section_id\": 12}',NULL,NULL,'2026-05-11 11:42:12'),(417,91,'enrollment_created','enrollments',70,'{\"student_id\": 91, \"section_id\": 12}',NULL,NULL,'2026-05-11 11:42:12'),(418,93,'enrollment_created','enrollments',71,'{\"student_id\": 93, \"section_id\": 12}',NULL,NULL,'2026-05-11 11:42:12'),(419,66,'enrollment_created','enrollments',72,'{\"student_id\": 66, \"section_id\": 13}',NULL,NULL,'2026-05-11 11:42:12'),(420,67,'enrollment_created','enrollments',73,'{\"student_id\": 67, \"section_id\": 13}',NULL,NULL,'2026-05-11 11:42:12'),(421,68,'enrollment_created','enrollments',74,'{\"student_id\": 68, \"section_id\": 13}',NULL,NULL,'2026-05-11 11:42:12'),(422,79,'enrollment_created','enrollments',75,'{\"student_id\": 79, \"section_id\": 13}',NULL,NULL,'2026-05-11 11:42:12'),(423,80,'enrollment_created','enrollments',76,'{\"student_id\": 80, \"section_id\": 13}',NULL,NULL,'2026-05-11 11:42:12'),(424,93,'enrollment_created','enrollments',77,'{\"student_id\": 93, \"section_id\": 13}',NULL,NULL,'2026-05-11 11:42:12'),(425,94,'enrollment_created','enrollments',78,'{\"student_id\": 94, \"section_id\": 13}',NULL,NULL,'2026-05-11 11:42:12'),(426,66,'enrollment_created','enrollments',79,'{\"student_id\": 66, \"section_id\": 14}',NULL,NULL,'2026-05-11 11:42:12'),(427,67,'enrollment_created','enrollments',80,'{\"student_id\": 67, \"section_id\": 14}',NULL,NULL,'2026-05-11 11:42:12'),(428,79,'enrollment_created','enrollments',81,'{\"student_id\": 79, \"section_id\": 14}',NULL,NULL,'2026-05-11 11:42:12'),(429,80,'enrollment_created','enrollments',82,'{\"student_id\": 80, \"section_id\": 14}',NULL,NULL,'2026-05-11 11:42:12'),(430,94,'enrollment_created','enrollments',83,'{\"student_id\": 94, \"section_id\": 14}',NULL,NULL,'2026-05-11 11:42:12'),(431,66,'enrollment_created','enrollments',84,'{\"student_id\": 66, \"section_id\": 15}',NULL,NULL,'2026-05-11 11:42:12'),(432,67,'enrollment_created','enrollments',85,'{\"student_id\": 67, \"section_id\": 15}',NULL,NULL,'2026-05-11 11:42:12'),(433,68,'enrollment_created','enrollments',86,'{\"student_id\": 68, \"section_id\": 15}',NULL,NULL,'2026-05-11 11:42:12'),(434,79,'enrollment_created','enrollments',87,'{\"student_id\": 79, \"section_id\": 15}',NULL,NULL,'2026-05-11 11:42:12'),(435,87,'enrollment_created','enrollments',88,'{\"student_id\": 87, \"section_id\": 15}',NULL,NULL,'2026-05-11 11:42:12'),(436,88,'enrollment_created','enrollments',89,'{\"student_id\": 88, \"section_id\": 15}',NULL,NULL,'2026-05-11 11:42:12'),(437,NULL,'assignment_created','assignments',7,'{\"section_id\": 2, \"title\": \"Project 3: Full-Stack CRUD App\"}',NULL,NULL,'2026-05-11 11:42:12'),(438,NULL,'assignment_created','assignments',8,'{\"section_id\": 3, \"title\": \"Problem Set 4: Trees and Graphs\"}',NULL,NULL,'2026-05-11 11:42:12'),(439,NULL,'assignment_created','assignments',9,'{\"section_id\": 4, \"title\": \"Capstone Chapter 2 - RRL\"}',NULL,NULL,'2026-05-11 11:42:12'),(440,NULL,'assignment_created','assignments',10,'{\"section_id\": 6, \"title\": \"Lab 1: Network Topology Design\"}',NULL,NULL,'2026-05-11 11:42:12'),(441,NULL,'assignment_created','assignments',11,'{\"section_id\": 6, \"title\": \"Lab 2: Subnetting Worksheet\"}',NULL,NULL,'2026-05-11 11:42:12'),(442,NULL,'assignment_created','assignments',12,'{\"section_id\": 6, \"title\": \"Lab 3: Routing Protocol Configuration\"}',NULL,NULL,'2026-05-11 11:42:12'),(443,NULL,'assignment_created','assignments',13,'{\"section_id\": 7, \"title\": \"Sprint 1 - Requirements Document\"}',NULL,NULL,'2026-05-11 11:42:12'),(444,NULL,'assignment_created','assignments',14,'{\"section_id\": 7, \"title\": \"Sprint 2 - System Design Document\"}',NULL,NULL,'2026-05-11 11:42:12'),(445,NULL,'assignment_created','assignments',15,'{\"section_id\": 9, \"title\": \"SQL Lab 1: DDL and DML Queries\"}',NULL,NULL,'2026-05-11 11:42:12'),(446,NULL,'assignment_created','assignments',16,'{\"section_id\": 9, \"title\": \"SQL Lab 2: Joins and Subqueries\"}',NULL,NULL,'2026-05-11 11:42:12'),(447,NULL,'assignment_created','assignments',17,'{\"section_id\": 9, \"title\": \"SQL Lab 3: Stored Procedures and Views\"}',NULL,NULL,'2026-05-11 11:42:12'),(448,NULL,'assignment_created','assignments',18,'{\"section_id\": 15, \"title\": \"Lab Activity 1: Binary Conversion\"}',NULL,NULL,'2026-05-11 11:42:12'),(449,NULL,'assignment_created','assignments',19,'{\"section_id\": 15, \"title\": \"Lab Activity 2: Hardware Components\"}',NULL,NULL,'2026-05-11 11:42:12'),(450,NULL,'assignment_created','assignments',20,'{\"section_id\": 2, \"title\": \"Midterm Exam - Web Programming\"}',NULL,NULL,'2026-05-11 11:42:12'),(451,37,'quiz_attempt_started','quiz_attempts',27,'{\"quiz_id\": 9, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(452,38,'quiz_attempt_started','quiz_attempts',28,'{\"quiz_id\": 9, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(453,39,'quiz_attempt_started','quiz_attempts',29,'{\"quiz_id\": 9, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(454,40,'quiz_attempt_started','quiz_attempts',30,'{\"quiz_id\": 9, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(455,41,'quiz_attempt_started','quiz_attempts',31,'{\"quiz_id\": 9, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(456,42,'quiz_attempt_started','quiz_attempts',32,'{\"quiz_id\": 9, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(457,44,'quiz_attempt_started','quiz_attempts',33,'{\"quiz_id\": 9, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(458,37,'quiz_attempt_started','quiz_attempts',34,'{\"quiz_id\": 11, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(459,38,'quiz_attempt_started','quiz_attempts',35,'{\"quiz_id\": 11, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(460,39,'quiz_attempt_started','quiz_attempts',36,'{\"quiz_id\": 11, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(461,43,'quiz_attempt_started','quiz_attempts',37,'{\"quiz_id\": 11, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(462,52,'quiz_attempt_started','quiz_attempts',38,'{\"quiz_id\": 11, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(463,53,'quiz_attempt_started','quiz_attempts',39,'{\"quiz_id\": 11, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(464,47,'quiz_attempt_started','quiz_attempts',40,'{\"quiz_id\": 13, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(465,48,'quiz_attempt_started','quiz_attempts',41,'{\"quiz_id\": 13, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(466,49,'quiz_attempt_started','quiz_attempts',42,'{\"quiz_id\": 13, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(467,50,'quiz_attempt_started','quiz_attempts',43,'{\"quiz_id\": 13, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(468,51,'quiz_attempt_started','quiz_attempts',44,'{\"quiz_id\": 13, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(469,61,'quiz_attempt_started','quiz_attempts',45,'{\"quiz_id\": 15, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(470,62,'quiz_attempt_started','quiz_attempts',46,'{\"quiz_id\": 15, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(471,63,'quiz_attempt_started','quiz_attempts',47,'{\"quiz_id\": 15, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(472,65,'quiz_attempt_started','quiz_attempts',48,'{\"quiz_id\": 15, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(473,61,'quiz_attempt_started','quiz_attempts',49,'{\"quiz_id\": 18, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(474,73,'quiz_attempt_started','quiz_attempts',50,'{\"quiz_id\": 18, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(475,83,'quiz_attempt_started','quiz_attempts',51,'{\"quiz_id\": 18, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(476,90,'quiz_attempt_started','quiz_attempts',52,'{\"quiz_id\": 18, \"attempt_number\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(477,38,'submission_created','submissions',25,'{\"assignment_id\": 6, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(478,41,'submission_created','submissions',26,'{\"assignment_id\": 6, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(479,43,'submission_created','submissions',27,'{\"assignment_id\": 7, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(480,44,'submission_created','submissions',28,'{\"assignment_id\": 7, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(481,52,'submission_created','submissions',29,'{\"assignment_id\": 7, \"is_late\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(482,47,'submission_created','submissions',30,'{\"assignment_id\": 8, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(483,48,'submission_created','submissions',31,'{\"assignment_id\": 8, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(484,49,'submission_created','submissions',32,'{\"assignment_id\": 8, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(485,51,'submission_created','submissions',33,'{\"assignment_id\": 8, \"is_late\": 1}',NULL,NULL,'2026-05-11 11:42:12'),(486,55,'submission_created','submissions',34,'{\"assignment_id\": 9, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(487,56,'submission_created','submissions',35,'{\"assignment_id\": 9, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(488,48,'submission_created','submissions',36,'{\"assignment_id\": 9, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(489,61,'submission_created','submissions',37,'{\"assignment_id\": 10, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(490,62,'submission_created','submissions',38,'{\"assignment_id\": 10, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(491,63,'submission_created','submissions',39,'{\"assignment_id\": 10, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(492,64,'submission_created','submissions',40,'{\"assignment_id\": 10, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(493,61,'submission_created','submissions',41,'{\"assignment_id\": 15, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(494,73,'submission_created','submissions',42,'{\"assignment_id\": 15, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(495,83,'submission_created','submissions',43,'{\"assignment_id\": 15, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(496,61,'submission_created','submissions',44,'{\"assignment_id\": 16, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(497,83,'submission_created','submissions',45,'{\"assignment_id\": 16, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(498,66,'submission_created','submissions',46,'{\"assignment_id\": 18, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(499,67,'submission_created','submissions',47,'{\"assignment_id\": 18, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(500,68,'submission_created','submissions',48,'{\"assignment_id\": 18, \"is_late\": 0}',NULL,NULL,'2026-05-11 11:42:12'),(501,1,'magic_link_login','users',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-11 12:23:45'),(502,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-11 12:50:06'),(503,1,'magic_link_login','users',1,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-11 21:24:27'),(504,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-11 21:41:54'),(505,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-11 21:51:08'),(506,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-11 21:52:44'),(507,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-12 01:50:36'),(508,NULL,'assignment_created','assignments',21,'{\"section_id\": 2, \"title\": \"my assignment\"}',NULL,NULL,'2026-05-12 03:56:20'),(509,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-12 04:02:49'),(510,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-12 04:50:35'),(511,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-12 04:51:12'),(512,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-05-12 04:52:36'),(513,97,'user_registered','users',97,'{\"email\": \"martinez_aaron@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(514,98,'user_registered','users',98,'{\"email\": \"ramirez_bea@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(515,99,'user_registered','users',99,'{\"email\": \"gomez_carl@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(516,100,'user_registered','users',100,'{\"email\": \"navarro_diana@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(517,101,'user_registered','users',101,'{\"email\": \"perez_ethan@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(518,102,'user_registered','users',102,'{\"email\": \"aquino_faith@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(519,103,'user_registered','users',103,'{\"email\": \"cruz_gabriel@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(520,104,'user_registered','users',104,'{\"email\": \"santos_hannah@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(521,105,'user_registered','users',105,'{\"email\": \"reyes_ivan@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(522,106,'user_registered','users',106,'{\"email\": \"flores_jasmine@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(523,107,'user_registered','users',107,'{\"email\": \"torres_kevin@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(524,108,'user_registered','users',108,'{\"email\": \"mendoza_lara@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(525,109,'user_registered','users',109,'{\"email\": \"castillo_matthew@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(526,110,'user_registered','users',110,'{\"email\": \"garcia_nicole@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(527,111,'user_registered','users',111,'{\"email\": \"herrera_oscar@plpasig.edu.ph\", \"role\": \"student\"}',NULL,NULL,'2026-05-12 04:59:06'),(528,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-15 23:43:59'),(529,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-15 23:44:19'),(530,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-15 23:44:45'),(531,NULL,'quiz_created','quizzes',9,'{\"section_id\": 2, \"title\": \"test 2\"}',NULL,NULL,'2026-05-15 23:45:04'),(532,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-15 23:45:24'),(533,36,'magic_link_login','users',36,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-15 23:54:33'),(534,NULL,'quiz_created','quizzes',10,'{\"section_id\": 2, \"title\": \"lala\"}',NULL,NULL,'2026-05-15 23:54:59'),(535,37,'magic_link_login','users',37,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-15 23:55:11');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_tokens`
--

DROP TABLE IF EXISTS `auth_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `token_type` enum('email_verify','password_reset','magic_link') NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `login_email` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_at_user` (`user_id`),
  KEY `idx_at_login_email` (`login_email`),
  CONSTRAINT `fk_at_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_tokens`
--

LOCK TABLES `auth_tokens` WRITE;
/*!40000 ALTER TABLE `auth_tokens` DISABLE KEYS */;
INSERT INTO `auth_tokens` VALUES (43,1,'691d0238505ba316b552bf0134c456643abc71b78a5623ff4da529da22b443a4','password_reset','2026-05-04 10:40:46','2026-05-04 10:40:46','2026-05-04 10:40:42','duran_lemuel@plpasig.edu.ph'),(45,1,'e139bd389231afc703ecd0d936171171179a10511e7d3221614c9ecb6bf96a4c','password_reset','2026-05-04 11:45:11','2026-05-04 11:45:11','2026-05-04 11:45:05','duran_lemuel@plpasig.edu.ph'),(49,1,'2d45a69848a9f6924f43412aa747ba22d50a3e92d9930556f1fe5df199339de0','password_reset','2026-05-05 01:38:31','2026-05-05 01:38:31','2026-05-05 01:38:27','duran_lemuel@plpasig.edu.ph'),(53,1,'98eea4497222f25077d94f08c6fd31cc3c0dd5bacdc8ea3ef0360af979e5a754','password_reset','2026-05-05 01:53:11','2026-05-05 01:53:11','2026-05-05 01:53:08','duran_lemuel@plpasig.edu.ph'),(57,1,'162ad9c1cdef7382634fd202db6252dfd11091d059477edc02312f6dfdb54caa','password_reset','2026-05-05 02:06:13','2026-05-05 02:06:13','2026-05-05 02:06:10','duran_lemuel@plpasig.edu.ph'),(58,1,'b40f72b709288863424b259405300ec0a56a61a01ed57e36afd9d0f4492d58b7','password_reset','2026-05-05 02:07:51','2026-05-05 02:07:51','2026-05-05 02:07:47','duran_lemuel@plpasig.edu.ph'),(59,1,'14f6e120c3dc02c5c0939abe8b90d42b0768467ef6c99b5bd6a9a286d4ff1742','password_reset','2026-05-05 02:11:47','2026-05-05 02:11:47','2026-05-05 02:11:44','duran_lemuel@plpasig.edu.ph'),(60,1,'ff93e79e9d6e0267e1eeec86c8d261cfef66b63f8ad6c6697a687480a4655c82','password_reset','2026-05-05 02:26:18','2026-05-05 02:26:18','2026-05-05 02:26:13','duran_lemuel@plpasig.edu.ph'),(62,1,'a8633f9f857a9f0ddf93d26ee67e9315e269c18833bc871cde9d1626e31d28b1','password_reset','2026-05-05 02:45:36','2026-05-05 02:45:36','2026-05-05 02:45:33','duran_lemuel@plpasig.edu.ph'),(70,1,'738f7d71435c53b9e9a55de5edc8dc48da849439fd19d0cd63176687cd17f29e','password_reset','2026-05-11 06:26:34','2026-05-11 06:26:34','2026-05-11 06:26:28','duran_lemuel@plpasig.edu.ph'),(71,36,'f65460803dd919abf1b01e2da997e41fe5435271a28bb92aaa561c5a7730c1b7','password_reset','2026-05-11 06:29:00','2026-05-11 06:29:00','2026-05-11 06:28:50','santos_cath@plpasig.edu.ph'),(72,1,'a48f215b4468a189f179958eb4f5c1749f07beba8ade89902126ec6678bfd8e4','password_reset','2026-05-11 06:29:27','2026-05-11 06:29:27','2026-05-11 06:29:23','duran_lemuel@plpasig.edu.ph'),(73,36,'1be5e6aff34eb612ef99d3995e3761d2e405a3ac9a3d3d7639bddfe0254b52cc','password_reset','2026-05-11 06:30:55','2026-05-11 06:30:55','2026-05-11 06:30:50','santos_cath@plpasig.edu.ph'),(74,37,'4989b859895aeba0c579e47bb6900a7d1892fb10968f425a79edb67865cf0ff8','password_reset','2026-05-11 07:07:30','2026-05-11 07:07:30','2026-05-11 07:07:26','gabriel_ryza@plpasig.edu.ph'),(75,36,'a9091e921a598bc9a45179d6ac48c7d9a0bb4baa638111b07eb3b96251de9626','password_reset','2026-05-11 07:08:32','2026-05-11 07:08:32','2026-05-11 07:08:27','santos_cath@plpasig.edu.ph'),(76,37,'0afda62b4adbc2c332e9affece827691da2e97ae2e7f3194a92f2a18984c4609','password_reset','2026-05-11 07:13:16','2026-05-11 07:13:16','2026-05-11 07:13:06','gabriel_ryza@plpasig.edu.ph'),(77,36,'c5ad879067d50a5069b802a49594bfa965f2e5eba1e2241bdf0ef8352a2cc354','password_reset','2026-05-11 07:13:51','2026-05-11 07:13:51','2026-05-11 07:13:48','santos_cath@plpasig.edu.ph'),(78,37,'9d688eb0141c647ad75514138c29f43bff30c3fbd52c95887c450cf7b4f399ec','password_reset','2026-05-11 07:14:48','2026-05-11 07:14:48','2026-05-11 07:14:45','gabriel_ryza@plpasig.edu.ph'),(79,1,'ab74cd26300b6d2a92de561fc00cc219d8b42555a125cdeb15a360e27beb34f5','password_reset','2026-05-11 07:16:01','2026-05-11 07:16:01','2026-05-11 07:15:58','duran_lemuel@plpasig.edu.ph'),(80,36,'dc89dab8ba65182cfc5596752bbf257cc543349f379d92555be529aaa086ae0d','password_reset','2026-05-11 07:37:53','2026-05-11 07:37:53','2026-05-11 07:37:49','santos_cath@plpasig.edu.ph'),(81,37,'74e626407d8bbda4a7e4cfcc5f63d6ed044e341d783be6309f3796614861f2dc','password_reset','2026-05-11 07:39:26','2026-05-11 07:39:26','2026-05-11 07:39:23','gabriel_ryza@plpasig.edu.ph'),(82,36,'a6ec48f6cb4ca69f208cdf0ef1f3c090b9f21bed1c32cdc4ee8d0a96c5ab23f3','password_reset','2026-05-11 07:40:19','2026-05-11 07:40:19','2026-05-11 07:40:16','santos_cath@plpasig.edu.ph'),(83,37,'03523e9b82faf088fa245918587873f0db918481f1f3351299ce36c500421ae7','password_reset','2026-05-11 07:41:35','2026-05-11 07:41:35','2026-05-11 07:41:31','gabriel_ryza@plpasig.edu.ph'),(84,36,'917394e8b2096c52a842c7e910e68795373d54e617608e976be2829905f8dfae','password_reset','2026-05-11 07:50:58','2026-05-11 07:50:58','2026-05-11 07:50:53','santos_cath@plpasig.edu.ph'),(85,37,'1c1e5b0de1890980036eb7fa8d28f5da1a32aee1d0c5cf657bb606e8769e842f','password_reset','2026-05-11 07:51:57','2026-05-11 07:51:57','2026-05-11 07:51:54','gabriel_ryza@plpasig.edu.ph'),(86,36,'930edf5f0977bb74f44dcbbfb8dac3cc2f7053e319c412c5dac5abebb3510620','password_reset','2026-05-11 07:57:51','2026-05-11 07:57:51','2026-05-11 07:57:47','santos_cath@plpasig.edu.ph'),(87,37,'2edc43465e8cee83567293244c090325695321a765974c6cd5fb725d8d39fe74','password_reset','2026-05-11 07:58:58','2026-05-11 07:58:58','2026-05-11 07:58:55','gabriel_ryza@plpasig.edu.ph'),(88,36,'b560328d8cbae99427a28ba014d1e0b0ccc76c9ddc1bca3520c9870104fd051f','password_reset','2026-05-11 08:04:50','2026-05-11 08:04:50','2026-05-11 08:04:47','santos_cath@plpasig.edu.ph'),(89,37,'c20e7a0c7af32c6823493bf845dfaffaaa5b3424f903102ec8bb6830fa379ab6','password_reset','2026-05-11 08:05:31','2026-05-11 08:05:31','2026-05-11 08:05:27','gabriel_ryza@plpasig.edu.ph'),(90,36,'437c802e6d003089cb7741e87e4c622bb91ca4baf7191c82bec2ceecaba7ced9','password_reset','2026-05-11 08:35:13','2026-05-11 08:35:13','2026-05-11 08:35:09','santos_cath@plpasig.edu.ph'),(91,37,'8643a5d6854cc109a8e51f1f7cf0c4941a88abe102040e7f6d9dbb15d14772e4','password_reset','2026-05-11 08:36:00','2026-05-11 08:36:00','2026-05-11 08:35:56','gabriel_ryza@plpasig.edu.ph'),(92,36,'ea8a5b89eb54ae3bc89d7ceb5ce39435d5a528eb9d3edfc1c895ddf215a9ec0e','password_reset','2026-05-11 08:43:35','2026-05-11 08:43:35','2026-05-11 08:43:31','santos_cath@plpasig.edu.ph'),(93,37,'2d9ff8a38369284b7a5f54f5e271657738137c6dbde059c15d1a94b9d3461f3a','password_reset','2026-05-11 08:44:16','2026-05-11 08:44:16','2026-05-11 08:44:13','gabriel_ryza@plpasig.edu.ph'),(94,36,'27a34b57f4a1d2eff0221e386461efd7270ac3d1a7648f1ce1421574ddfc6d81','password_reset','2026-05-11 08:45:53','2026-05-11 08:45:53','2026-05-11 08:45:48','santos_cath@plpasig.edu.ph'),(95,1,'1e133bcaabdb150afe845d8aaa5d58c98afacac1c4cce23ea282d5c84a9f8682','password_reset','2026-05-11 12:23:45','2026-05-11 12:23:45','2026-05-11 12:23:40','duran_lemuel@plpasig.edu.ph'),(96,37,'b0f8b7f021689a8a1d7b2925037806c5a21435154a51c30b8135225b6c011147','password_reset','2026-05-11 12:50:06','2026-05-11 12:50:06','2026-05-11 12:50:03','gabriel_ryza@plpasig.edu.ph'),(97,1,'a5f437c592d93925666d02954c6137810869220fd6c241d978724142b1f29d2b','password_reset','2026-05-11 21:24:27','2026-05-11 21:24:27','2026-05-11 21:24:22','duran_lemuel@plpasig.edu.ph'),(107,36,'98d2757d186819d1fa7bfea310f93dd3d2234ff10ce357596058f1c527513497','password_reset','2026-05-11 21:41:54','2026-05-11 21:41:54','2026-05-11 21:41:49','santos_cath@plpasig.edu.ph'),(108,36,'0ae98276db5c34250a3952a83417dddd0a8736af21c04b5d050e31fc23be53b8','password_reset','2026-05-11 21:51:08','2026-05-11 21:51:08','2026-05-11 21:51:01','santos_cath@plpasig.edu.ph'),(109,37,'808d03da6c4cb21f94abd296dc90664cc4c45d7bbf66f43e4b4adb1db0cb5ae5','password_reset','2026-05-11 21:52:44','2026-05-11 21:52:44','2026-05-11 21:52:39','gabriel_ryza@plpasig.edu.ph'),(110,36,'69cc477081a535fd7c387b0c24a21c5288cb466ac4806e455d607ccf5b51cf1e','password_reset','2026-05-12 01:50:36','2026-05-12 01:50:36','2026-05-12 01:50:30','santos_cath@plpasig.edu.ph'),(111,37,'cadf95568e57b61670a084a18c7f73e31b4fc2b7cde8df88955702b1c2a6bc6e','password_reset','2026-05-12 04:02:49','2026-05-12 04:02:49','2026-05-12 04:02:45','gabriel_ryza@plpasig.edu.ph'),(112,37,'5b5d0d8494d6b4d23a0fa374e1f791f79631b8c6ccf41aee3e7325f9201315b4','password_reset','2026-05-12 04:50:35','2026-05-12 04:50:35','2026-05-12 04:50:29','gabriel_ryza@plpasig.edu.ph'),(113,36,'8236676d2c7438d530fe22a26fef14b8bbfccf90c95d27ffb44b8b13ec94c0f7','password_reset','2026-05-12 04:51:12','2026-05-12 04:51:12','2026-05-12 04:51:05','santos_cath@plpasig.edu.ph'),(114,37,'9beafceafffa086e8da45154dd6da29e68696d28d3b19f2850fd46853f66f3ef','password_reset','2026-05-12 04:52:36','2026-05-12 04:52:36','2026-05-12 04:52:32','gabriel_ryza@plpasig.edu.ph'),(115,37,'6b5dd17a9db86f1c381f24d043490d7048ee4941700a78c82b435788238e9bab','password_reset','2026-05-15 23:43:59','2026-05-15 23:43:59','2026-05-15 23:43:54','gabriel_ryza@plpasig.edu.ph'),(116,37,'a6fcc7fbe809065b677600ec046a88c700122a8032f26926b4709e5da1f611bf','password_reset','2026-05-15 23:44:19','2026-05-15 23:44:19','2026-05-15 23:44:16','gabriel_ryza@plpasig.edu.ph'),(117,36,'99765bdfccc4b2c2ce211d61779d56127eb0900afde836d6cc25fd36eedfebbd','password_reset','2026-05-15 23:44:45','2026-05-15 23:44:45','2026-05-15 23:44:42','santos_cath@plpasig.edu.ph'),(118,37,'ad443c39d2028078f2a65dc051afcaaa63545eaf7a9512bf2a9f85bf9391ef83','password_reset','2026-05-15 23:45:23','2026-05-15 23:45:23','2026-05-15 23:45:20','gabriel_ryza@plpasig.edu.ph'),(119,36,'1590b539f9705aae7e3cc965a9bd6e2fdb1ab739752e2e0a688bdac14f5baf99','password_reset','2026-05-15 23:54:33','2026-05-15 23:54:33','2026-05-15 23:54:31','santos_cath@plpasig.edu.ph'),(120,37,'11534bedb952a50b0457c9c2b522a9f322a5251b1a3aab836304295483dc63aa','password_reset','2026-05-15 23:55:11','2026-05-15 23:55:11','2026-05-15 23:55:09','gabriel_ryza@plpasig.edu.ph');
/*!40000 ALTER TABLE `auth_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_archives`
--

DROP TABLE IF EXISTS `course_archives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_archives` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `section_id` int(10) unsigned DEFAULT NULL,
  `action` enum('archived','restored') NOT NULL DEFAULT 'archived',
  `performed_by` int(10) unsigned NOT NULL COMMENT 'user_id of instructor',
  `reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ca_course` (`course_id`),
  KEY `idx_ca_section` (`section_id`),
  KEY `idx_ca_actor` (`performed_by`),
  CONSTRAINT `fk_ca_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ca_performer` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ca_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_archives`
--

LOCK TABLES `course_archives` WRITE;
/*!40000 ALTER TABLE `course_archives` DISABLE KEYS */;
INSERT INTO `course_archives` VALUES (1,5,5,'archived',36,'sadhfnsjfes','2026-05-08 10:56:30'),(2,5,5,'restored',36,NULL,'2026-05-11 06:32:25'),(3,2,2,'archived',36,'Archived by instructor','2026-05-11 06:32:32'),(4,1,1,'archived',36,'dausdausd','2026-05-11 09:42:24'),(5,2,2,'restored',36,NULL,'2026-05-11 09:43:33'),(6,1,1,'restored',36,NULL,'2026-05-11 10:48:02'),(7,4,4,'archived',36,'just because','2026-05-11 10:48:15'),(8,4,4,'restored',36,NULL,'2026-05-11 11:08:05'),(9,4,4,'archived',36,'just because','2026-05-11 11:08:15');
/*!40000 ALTER TABLE `course_archives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_post_files`
--

DROP TABLE IF EXISTS `course_post_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_post_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_size_kb` int(10) unsigned DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cpf_post` (`post_id`),
  CONSTRAINT `fk_cpf_post` FOREIGN KEY (`post_id`) REFERENCES `course_posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_post_files`
--

LOCK TABLES `course_post_files` WRITE;
/*!40000 ALTER TABLE `course_post_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_post_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_post_reads`
--

DROP TABLE IF EXISTS `course_post_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_post_reads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cpr` (`post_id`,`user_id`),
  KEY `idx_cpr_user` (`user_id`),
  CONSTRAINT `fk_cpr_post` FOREIGN KEY (`post_id`) REFERENCES `course_posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cpr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_post_reads`
--

LOCK TABLES `course_post_reads` WRITE;
/*!40000 ALTER TABLE `course_post_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_post_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_posts`
--

DROP TABLE IF EXISTS `course_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `post_type` enum('announcement','module') NOT NULL DEFAULT 'module',
  `title` varchar(255) NOT NULL,
  `body` longtext DEFAULT NULL COMMENT 'Rich-text or markdown content for the post',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cp_section` (`section_id`),
  KEY `idx_cp_author` (`author_id`),
  KEY `idx_cp_type` (`post_type`),
  KEY `idx_cp_published` (`published_at`),
  CONSTRAINT `fk_cp_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cp_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_posts`
--

LOCK TABLES `course_posts` WRITE;
/*!40000 ALTER TABLE `course_posts` DISABLE KEYS */;
INSERT INTO `course_posts` VALUES (1,1,36,'','OOP Cheat Sheet - Key Concepts Summary','Attached is a one-page summary of all key OOP concepts covered this semester: Encapsulation, Inheritance, Polymorphism, Abstraction, Interfaces, and Exception Handling. Use this as a reference during coding exercises.',0,1,'2026-01-20 00:00:00','2026-01-20 00:00:00','2026-01-20 00:00:00'),(2,1,36,'','Week 3-4 Lecture Slides: Inheritance','Lecture slides for Weeks 3 and 4 are now available. Topics covered: single/multi-level inheritance, the \"is-a\" relationship, method overriding, the super keyword, and constructor chaining.',0,1,'2026-01-27 00:00:00','2026-01-27 00:00:00','2026-01-27 00:00:00'),(3,1,36,'announcement','Consultation Hours This Week','I will be available for consultation in Room 104 every Tuesday 2:00-4:00 PM and Thursday 1:00-3:00 PM. Bring your code and specific questions. No walk-in on May 9 (Faculty Meeting).',0,1,'2026-05-04 23:00:00','2026-05-04 23:00:00','2026-05-04 23:00:00'),(4,2,36,'','Module 3 Lecture Video - JavaScript Events','The lecture video for Module 3 (JavaScript DOM and Events) is now uploaded. Watch before the Thursday session. Duration: 45 minutes. Focus on the difference between addEventListener and inline event handlers.',0,1,'2026-02-17 00:00:00','2026-02-17 00:00:00','2026-02-17 00:00:00'),(5,2,36,'','Responsive Design Portfolio Examples','Here are 5 examples of outstanding student portfolio sites from previous semesters. Study their mobile layouts, typography choices, and use of whitespace. These are the quality bar for Project 2.',1,1,'2026-04-10 00:00:00','2026-04-10 00:00:00','2026-04-10 00:00:00'),(6,3,36,'','Big-O Notation Reference Card','Quick reference for algorithm complexity: O(1) constant, O(log n) binary search, O(n) linear, O(n log n) merge sort, O(n?) bubble/insertion sort, O(2^n) exponential. Practice identifying these in code.',1,1,'2026-02-01 00:00:00','2026-02-01 00:00:00','2026-02-01 00:00:00'),(7,3,36,'','Unit 3 Supplementary Reading - Graph Algorithms','Supplementary reading on Dijkstra\'s shortest path algorithm and minimum spanning trees (Prim\'s and Kruskal\'s). This is not in the textbook but will appear in the final exam as bonus questions.',0,1,'2026-03-12 00:00:00','2026-03-12 00:00:00','2026-03-12 00:00:00'),(8,4,36,'','APA 7th Edition Citation Guide','All references in your capstone must follow APA 7th edition format. This guide covers journal articles, websites, and books. Pay attention to DOI formatting and author order. Use Zotero or Mendeley for reference management.',1,1,'2026-01-20 00:00:00','2026-01-20 00:00:00','2026-01-20 00:00:00'),(9,4,36,'announcement','Guest Speaker: Industry Researcher - May 14','We have a guest speaker next week: Dr. Paulo Buenaventura, a Senior Research Scientist at Accenture PH, will speak on \"AI-Powered Systems in Philippine Enterprise Settings\". Attendance is required and counts as a class activity.',1,1,'2026-05-09 00:00:00','2026-05-09 00:00:00','2026-05-09 00:00:00'),(10,6,57,'','Cisco Packet Tracer Lab Files - Labs 1-3','All Packet Tracer lab starter files (.pkt) are now available in this post. Download and save them to your working folder. Open with Cisco Packet Tracer 8.2 or higher. Older versions may have rendering issues.',1,1,'2026-01-14 00:00:00','2026-01-14 00:00:00','2026-01-14 00:00:00'),(11,6,57,'','Subnetting Quick Reference - IPv4','A one-page subnetting cheat sheet covering prefix lengths (/8 to /30), host count per subnet, and network/broadcast address formulas. Practice until you can subnet Class C in under 60 seconds.',0,1,'2026-02-05 00:00:00','2026-02-05 00:00:00','2026-02-05 00:00:00'),(12,7,57,'','Sprint 1 SRS Template (IEEE Standard)','Attached is the IEEE Software Requirements Specification template for Sprint 1. Fill in all sections: Introduction, Overall Description, Specific Requirements (functional and non-functional), and Appendices.',1,1,'2026-02-24 00:00:00','2026-02-24 00:00:00','2026-02-24 00:00:00'),(13,7,57,'','Recommended UML Tools for Sprint 2','Recommended free tools: draw.io (web-based, easiest), StarUML (desktop, feature-rich), PlantUML (text-based, great for version control). All are acceptable for submission. Export diagrams as PNG at 300dpi minimum.',0,1,'2026-03-30 00:00:00','2026-03-30 00:00:00','2026-03-30 00:00:00'),(14,9,59,'','SQL Lab Environment Setup Guide','Use MySQL 8.0 with MySQL Workbench for all SQL labs. XAMPP with MariaDB is also acceptable. This guide covers installation, creating your lab database, and connecting with Workbench. Use the provided schema scripts.',1,1,'2026-01-16 00:00:00','2026-01-16 00:00:00','2026-01-16 00:00:00'),(15,9,59,'','Normalization Worked Examples','Step-by-step normalization of a Hospital Appointment system from UNF to 3NF. Includes functional dependency identification, candidate key selection, and decomposition with lossless join verification.',0,1,'2026-02-27 00:00:00','2026-02-27 00:00:00','2026-02-27 00:00:00'),(16,13,60,'','Statistics Review: Measures of Central Tendency','Lecture notes and worked examples covering mean, median, and mode. Includes when to use each measure, effects of outliers, and grouped frequency distribution calculations. Includes 15 practice problems with answers.',0,1,'2026-02-10 00:00:00','2026-02-10 00:00:00','2026-02-10 00:00:00'),(17,13,60,'announcement','Midterm Exam Details','Midterm exam is on March 20, 2026, Room 201. Coverage: Number Theory (Chapters 1-2), Logic and Propositions (Chapter 3), and Descriptive Statistics (Chapter 4). Bring a scientific calculator and student ID. No formula sheets allowed.',1,1,'2026-03-12 23:00:00','2026-03-12 23:00:00','2026-03-12 23:00:00'),(18,15,36,'','Unit 2 Supplementary: Number System Converter','Download this spreadsheet tool that validates your binary/hexadecimal conversions. Enter the decimal number and check your answers. Use it to practice 10 conversions per day before the lab activity deadline.',0,1,'2026-02-06 00:00:00','2026-02-06 00:00:00','2026-02-06 00:00:00'),(19,15,36,'announcement','Welcome to IT 101!','Welcome everyone to Introduction to Computing! This is your foundation course for all IT/CS subjects. By the end of the semester, you will understand how computers work, how to think computationally, and write your first simple programs. Let\'s make this term great!',1,1,'2026-01-12 23:00:00','2026-01-12 23:00:00','2026-01-12 23:00:00'),(20,1,36,'announcement','Reminder: Finals Coverage and Format','The finals will be a 3-hour practical exam. You will be given a problem specification and must implement a Java solution demonstrating OOP principles. Bring your laptop fully charged. IDEs allowed: IntelliJ IDEA or Eclipse. No internet access during exam.',1,1,'2026-05-10 00:00:00','2026-05-10 00:00:00','2026-05-10 00:00:00');
/*!40000 ALTER TABLE `course_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_sections`
--

DROP TABLE IF EXISTS `course_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `instructor_id` int(10) unsigned NOT NULL,
  `section_code` varchar(30) NOT NULL,
  `room` varchar(80) DEFAULT NULL,
  `schedule` varchar(150) DEFAULT NULL,
  `max_students` smallint(5) unsigned NOT NULL DEFAULT 40,
  `status` enum('open','closed','cancelled') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_section` (`course_id`,`term_id`,`section_code`),
  KEY `idx_cs_term` (`term_id`),
  KEY `idx_cs_instructor` (`instructor_id`),
  CONSTRAINT `fk_cs_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cs_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cs_term` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_sections`
--

LOCK TABLES `course_sections` WRITE;
/*!40000 ALTER TABLE `course_sections` DISABLE KEYS */;
INSERT INTO `course_sections` VALUES (1,1,2,36,'BSIT-2A',NULL,NULL,40,'open','2026-05-07 09:04:06'),(2,2,2,36,'BSIT-2B',NULL,NULL,40,'open','2026-05-07 09:04:06'),(3,3,2,36,'BSCS-2A',NULL,NULL,40,'open','2026-05-07 09:04:06'),(4,4,2,36,'BSIT-4A',NULL,NULL,30,'','2026-05-07 09:04:06'),(5,5,2,36,'IT103-BSIT-2A',NULL,NULL,40,'open','2026-05-08 10:55:22'),(6,6,2,57,'BSIT-3A-IT204','Room 301','TTh 07:30-09:00',40,'open','2026-01-06 00:00:00'),(7,7,2,57,'BSCS-3A-IT305','Room 302','MWF 09:00-10:00',40,'open','2026-01-06 00:00:00'),(8,8,2,59,'BSIT-3A-IT208','Room 303','TTh 10:30-12:00',40,'open','2026-01-06 00:00:00'),(9,9,2,59,'BSIT-3B-IT310','Room 304','MWF 13:00-14:00',40,'open','2026-01-06 00:00:00'),(10,10,2,58,'BSCS-3B-IT315','Room 305','TTh 13:30-15:00',40,'open','2026-01-06 00:00:00'),(11,11,2,58,'BSIT-4A-IT320','Room 306','MWF 07:30-08:30',35,'open','2026-01-06 00:00:00'),(12,12,2,57,'BSCS-4A-IT412','Room 307','TTh 15:00-16:30',30,'open','2026-01-06 00:00:00'),(13,13,2,60,'BSIT-1A-GE001','Room 201','MWF 10:00-11:00',45,'open','2026-01-06 00:00:00'),(14,14,2,60,'BSIT-1B-GE005','Room 202','TTh 07:30-09:00',45,'open','2026-01-06 00:00:00'),(15,15,2,36,'BSIT-1A-IT101','Room 101','MWF 13:00-14:00',45,'open','2026-01-06 00:00:00');
/*!40000 ALTER TABLE `course_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `units` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `cover_image_url` varchar(500) DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(10) unsigned DEFAULT NULL,
  `archive_reason` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_code` (`code`),
  KEY `idx_course_dept` (`department_id`),
  KEY `idx_course_status` (`status`),
  KEY `fk_course_creator` (`created_by`),
  KEY `fk_courses_archived_by` (`archived_by`),
  CONSTRAINT `fk_course_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_course_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_courses_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'IT 106','Object-Oriented Programming',NULL,1,3,'published',NULL,36,'2026-05-07 09:04:06','2026-05-11 10:48:02',NULL,NULL,NULL),(2,'IT 301','Web Programming',NULL,1,3,'published',NULL,36,'2026-05-07 09:04:06','2026-05-11 09:43:33',NULL,NULL,NULL),(3,'IT 201','Data Structures and Algorithms',NULL,1,3,'published',NULL,36,'2026-05-07 09:04:06','2026-05-07 09:04:06',NULL,NULL,NULL),(4,'IT 411','Capstone Project',NULL,1,3,'archived',NULL,36,'2026-05-07 09:04:06','2026-05-11 11:08:15','2026-05-11 19:08:15',36,'just because'),(5,'IT103','Advance Database Management',NULL,NULL,3,'published',NULL,36,'2026-05-08 10:55:22','2026-05-11 06:32:25',NULL,NULL,NULL);
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_courses_after_insert` AFTER INSERT ON `courses` FOR EACH ROW BEGIN

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

  VALUES (NEW.created_by, 'course_created', 'courses', NEW.id,

          JSON_OBJECT('code', NEW.code, 'title', NEW.title));

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_courses_after_update` AFTER UPDATE ON `courses` FOR EACH ROW BEGIN

  IF OLD.status <> NEW.status OR OLD.title <> NEW.title THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NEW.created_by, 'course_updated', 'courses', NEW.id,

            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status,

                        'old_title',  OLD.title,  'new_title',  NEW.title));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_courses_after_delete` AFTER DELETE ON `courses` FOR EACH ROW BEGIN

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

  VALUES (NULL, 'course_deleted', 'courses', OLD.id,

          JSON_OBJECT('code', OLD.code, 'title', OLD.title));

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `head_user_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_code` (`code`),
  KEY `fk_dept_head` (`head_user_id`),
  CONSTRAINT `fk_dept_head` FOREIGN KEY (`head_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'CSS','College of Computer Studies','',NULL,'2026-05-03 20:05:33'),(2,'COED','College of Education','',NULL,'2026-05-03 20:05:33'),(3,'CBA','College of Business and Accountancy','',NULL,'2026-05-03 20:05:33'),(4,'CAS','College of Arts and Sciences','',NULL,'2026-05-03 20:05:33'),(5,'CHM','College of Hospitality Management','',NULL,'2026-05-04 09:43:02');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `status` enum('enrolled','dropped','completed','failed') NOT NULL DEFAULT 'enrolled',
  `final_grade` decimal(5,2) DEFAULT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment` (`student_id`,`section_id`),
  KEY `idx_enr_section` (`section_id`),
  CONSTRAINT `fk_enr_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_enr_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
INSERT INTO `enrollments` VALUES (1,37,1,'enrolled',NULL,'2026-01-10 00:00:00','2026-05-08 10:54:34'),(2,38,1,'enrolled',NULL,'2026-01-10 00:05:00','2026-05-08 10:54:34'),(3,39,1,'enrolled',NULL,'2026-01-10 00:10:00','2026-05-08 10:54:34'),(4,40,1,'enrolled',NULL,'2026-01-10 00:15:00','2026-05-08 10:54:34'),(5,41,1,'enrolled',NULL,'2026-01-10 00:20:00','2026-05-08 10:54:34'),(6,42,1,'enrolled',NULL,'2026-01-10 00:25:00','2026-05-08 10:54:34'),(7,43,1,'enrolled',NULL,'2026-01-10 00:30:00','2026-05-08 10:54:34'),(8,44,1,'enrolled',NULL,'2026-01-10 00:35:00','2026-05-08 10:54:34'),(9,45,1,'enrolled',NULL,'2026-01-10 00:40:00','2026-05-08 10:54:34'),(10,46,1,'enrolled',NULL,'2026-01-10 00:45:00','2026-05-08 10:54:34'),(11,37,2,'enrolled',NULL,'2026-01-10 01:00:00','2026-05-08 10:54:34'),(12,38,2,'enrolled',NULL,'2026-01-10 01:05:00','2026-05-08 10:54:34'),(13,39,2,'enrolled',NULL,'2026-01-10 01:10:00','2026-05-08 10:54:34'),(14,43,2,'enrolled',NULL,'2026-01-10 01:15:00','2026-05-08 10:54:34'),(15,52,2,'enrolled',NULL,'2026-01-10 01:20:00','2026-05-08 10:54:34'),(16,53,2,'enrolled',NULL,'2026-01-10 01:25:00','2026-05-08 10:54:34'),(17,54,2,'enrolled',NULL,'2026-01-10 01:30:00','2026-05-08 10:54:34'),(18,44,2,'enrolled',NULL,'2026-01-10 01:35:00','2026-05-08 10:54:34'),(19,45,2,'enrolled',NULL,'2026-01-10 01:40:00','2026-05-08 10:54:34'),(20,41,2,'enrolled',NULL,'2026-01-10 01:45:00','2026-05-08 10:54:34'),(21,47,3,'enrolled',NULL,'2026-01-10 02:00:00','2026-05-08 10:54:34'),(22,48,3,'enrolled',NULL,'2026-01-10 02:05:00','2026-05-08 10:54:34'),(23,49,3,'enrolled',NULL,'2026-01-10 02:10:00','2026-05-08 10:54:34'),(24,50,3,'enrolled',NULL,'2026-01-10 02:15:00','2026-05-08 10:54:34'),(25,51,3,'enrolled',NULL,'2026-01-10 02:20:00','2026-05-08 10:54:34'),(26,42,3,'enrolled',NULL,'2026-01-10 02:25:00','2026-05-08 10:54:34'),(27,46,3,'enrolled',NULL,'2026-01-10 02:30:00','2026-05-08 10:54:34'),(28,40,3,'enrolled',NULL,'2026-01-10 02:35:00','2026-05-08 10:54:34'),(29,55,4,'enrolled',NULL,'2026-01-10 03:00:00','2026-05-08 10:54:34'),(30,56,4,'enrolled',NULL,'2026-01-10 03:05:00','2026-05-08 10:54:34'),(31,48,4,'enrolled',NULL,'2026-01-10 03:10:00','2026-05-08 10:54:34'),(32,37,4,'enrolled',NULL,'2026-01-10 03:15:00','2026-05-08 10:54:34'),(33,38,4,'enrolled',NULL,'2026-01-10 03:20:00','2026-05-08 10:54:34'),(34,61,6,'enrolled',NULL,'2026-01-09 20:00:00','2026-01-09 20:00:00'),(35,62,6,'enrolled',NULL,'2026-01-09 20:05:00','2026-01-09 20:05:00'),(36,63,6,'enrolled',NULL,'2026-01-09 20:10:00','2026-01-09 20:10:00'),(37,64,6,'enrolled',NULL,'2026-01-09 20:15:00','2026-01-09 20:15:00'),(38,65,6,'enrolled',NULL,'2026-01-09 20:20:00','2026-01-09 20:20:00'),(39,83,6,'enrolled',NULL,'2026-01-09 20:25:00','2026-01-09 20:25:00'),(40,84,6,'enrolled',NULL,'2026-01-09 20:30:00','2026-01-09 20:30:00'),(41,87,6,'enrolled',NULL,'2026-01-09 20:35:00','2026-01-09 20:35:00'),(42,73,7,'enrolled',NULL,'2026-01-09 21:00:00','2026-01-09 21:00:00'),(43,74,7,'enrolled',NULL,'2026-01-09 21:05:00','2026-01-09 21:05:00'),(44,89,7,'enrolled',NULL,'2026-01-09 21:10:00','2026-01-09 21:10:00'),(45,95,7,'enrolled',NULL,'2026-01-09 21:15:00','2026-01-09 21:15:00'),(46,64,7,'enrolled',NULL,'2026-01-09 21:20:00','2026-01-09 21:20:00'),(47,65,7,'enrolled',NULL,'2026-01-09 21:25:00','2026-01-09 21:25:00'),(48,69,8,'enrolled',NULL,'2026-01-09 22:00:00','2026-01-09 22:00:00'),(49,70,8,'enrolled',NULL,'2026-01-09 22:05:00','2026-01-09 22:05:00'),(50,81,8,'enrolled',NULL,'2026-01-09 22:10:00','2026-01-09 22:10:00'),(51,82,8,'enrolled',NULL,'2026-01-09 22:15:00','2026-01-09 22:15:00'),(52,85,8,'enrolled',NULL,'2026-01-09 22:20:00','2026-01-09 22:20:00'),(53,86,8,'enrolled',NULL,'2026-01-09 22:25:00','2026-01-09 22:25:00'),(54,61,9,'enrolled',NULL,'2026-01-09 23:00:00','2026-01-09 23:00:00'),(55,73,9,'enrolled',NULL,'2026-01-09 23:05:00','2026-01-09 23:05:00'),(56,83,9,'enrolled',NULL,'2026-01-09 23:10:00','2026-01-09 23:10:00'),(57,90,9,'enrolled',NULL,'2026-01-09 23:15:00','2026-01-09 23:15:00'),(58,92,9,'enrolled',NULL,'2026-01-09 23:20:00','2026-01-09 23:20:00'),(59,70,10,'enrolled',NULL,'2026-01-10 00:00:00','2026-01-10 00:00:00'),(60,71,10,'enrolled',NULL,'2026-01-10 00:05:00','2026-01-10 00:05:00'),(61,72,10,'enrolled',NULL,'2026-01-10 00:10:00','2026-01-10 00:10:00'),(62,74,10,'enrolled',NULL,'2026-01-10 00:15:00','2026-01-10 00:15:00'),(63,95,10,'enrolled',NULL,'2026-01-10 00:20:00','2026-01-10 00:20:00'),(64,76,11,'enrolled',NULL,'2026-01-10 01:00:00','2026-01-10 01:00:00'),(65,77,11,'enrolled',NULL,'2026-01-10 01:05:00','2026-01-10 01:05:00'),(66,91,11,'enrolled',NULL,'2026-01-10 01:10:00','2026-01-10 01:10:00'),(67,92,11,'enrolled',NULL,'2026-01-10 01:15:00','2026-01-10 01:15:00'),(68,77,12,'enrolled',NULL,'2026-01-10 02:00:00','2026-01-10 02:00:00'),(69,78,12,'enrolled',NULL,'2026-01-10 02:05:00','2026-01-10 02:05:00'),(70,91,12,'enrolled',NULL,'2026-01-10 02:10:00','2026-01-10 02:10:00'),(71,93,12,'enrolled',NULL,'2026-01-10 02:15:00','2026-01-10 02:15:00'),(72,66,13,'enrolled',NULL,'2026-01-10 03:00:00','2026-01-10 03:00:00'),(73,67,13,'enrolled',NULL,'2026-01-10 03:05:00','2026-01-10 03:05:00'),(74,68,13,'enrolled',NULL,'2026-01-10 03:10:00','2026-01-10 03:10:00'),(75,79,13,'enrolled',NULL,'2026-01-10 03:15:00','2026-01-10 03:15:00'),(76,80,13,'enrolled',NULL,'2026-01-10 03:20:00','2026-01-10 03:20:00'),(77,93,13,'enrolled',NULL,'2026-01-10 03:25:00','2026-01-10 03:25:00'),(78,94,13,'enrolled',NULL,'2026-01-10 03:30:00','2026-01-10 03:30:00'),(79,66,14,'enrolled',NULL,'2026-01-10 04:00:00','2026-01-10 04:00:00'),(80,67,14,'enrolled',NULL,'2026-01-10 04:05:00','2026-01-10 04:05:00'),(81,79,14,'enrolled',NULL,'2026-01-10 04:10:00','2026-01-10 04:10:00'),(82,80,14,'enrolled',NULL,'2026-01-10 04:15:00','2026-01-10 04:15:00'),(83,94,14,'enrolled',NULL,'2026-01-10 04:20:00','2026-01-10 04:20:00'),(84,66,15,'enrolled',NULL,'2026-01-10 05:00:00','2026-01-10 05:00:00'),(85,67,15,'enrolled',NULL,'2026-01-10 05:05:00','2026-01-10 05:05:00'),(86,68,15,'enrolled',NULL,'2026-01-10 05:10:00','2026-01-10 05:10:00'),(87,79,15,'enrolled',NULL,'2026-01-10 05:15:00','2026-01-10 05:15:00'),(88,87,15,'enrolled',NULL,'2026-01-10 05:20:00','2026-01-10 05:20:00'),(89,88,15,'enrolled',NULL,'2026-01-10 05:25:00','2026-01-10 05:25:00');
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_enrollments_after_insert` AFTER INSERT ON `enrollments` FOR EACH ROW BEGIN

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

  VALUES (NEW.student_id, 'enrollment_created', 'enrollments', NEW.id,

          JSON_OBJECT('student_id', NEW.student_id, 'section_id', NEW.section_id));

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_enrollments_after_update` AFTER UPDATE ON `enrollments` FOR EACH ROW BEGIN

  IF OLD.status <> NEW.status OR OLD.final_grade <> NEW.final_grade THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NEW.student_id, 'enrollment_updated', 'enrollments', NEW.id,

            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status,

                        'old_grade',  OLD.final_grade, 'new_grade', NEW.final_grade));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `forum_replies`
--

DROP TABLE IF EXISTS `forum_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_replies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) unsigned NOT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `parent_reply_id` int(10) unsigned DEFAULT NULL,
  `body` longtext NOT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `upvotes` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_thread` (`thread_id`),
  KEY `idx_fr_parent` (`parent_reply_id`),
  KEY `fk_fr_author` (`author_id`),
  CONSTRAINT `fk_fr_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fr_parent` FOREIGN KEY (`parent_reply_id`) REFERENCES `forum_replies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fr_thread` FOREIGN KEY (`thread_id`) REFERENCES `forum_threads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_replies`
--

LOCK TABLES `forum_replies` WRITE;
/*!40000 ALTER TABLE `forum_replies` DISABLE KEYS */;
INSERT INTO `forum_replies` VALUES (2,1,40,NULL,'The Singleton pattern is used in Java\'s Runtime class - there\'s only one JVM runtime per application. For Factory, think of a PaymentProcessorFactory that returns either a CreditCardProcessor or a GCashProcessor based on input.',0,8,'2026-05-05 01:00:00','2026-05-05 01:00:00'),(3,1,36,NULL,'For Observer pattern, think of event listeners in JavaScript. When you add an event listener to a button click, the button (subject) notifies all listeners (observers) when clicked. Java\'s PropertyChangeListener is a classic example.',0,12,'2026-05-05 02:00:00','2026-05-05 02:00:00'),(4,2,37,NULL,'I\'ve found CSS Grid most effective for page-level layouts (header, sidebar, main, footer) and Flexbox for component-level layouts (navigation items, card collections). Using both together is very powerful.',0,10,'2026-05-06 01:00:00','2026-05-06 01:00:00'),(5,2,54,NULL,'Always design mobile-first - start with the smallest screen and add breakpoints upward. It\'s much easier than trying to shrink a desktop layout down. Also, use rem units instead of px for font sizes.',0,7,'2026-05-06 02:00:00','2026-05-06 02:00:00'),(6,3,36,NULL,'Recursion is cleaner for problems with naturally recursive structure like tree traversal or quicksort. Iteration is more memory-efficient for simple loops since recursion adds stack frames. For deep recursion, prefer iteration to avoid StackOverflowError.',0,15,'2026-05-04 00:00:00','2026-05-04 00:00:00'),(7,3,50,NULL,'I ran a benchmark for fibonacci - iterative was 10x faster than naive recursive for large inputs. But memoized recursion (dynamic programming) was comparable to iterative. So it depends on whether you cache results.',0,6,'2026-05-04 01:00:00','2026-05-04 01:00:00'),(8,5,39,NULL,'Check if your grid template columns are set with fr units or fixed pixel values. Fixed px won\'t shrink. Also make sure your media query is inside the stylesheet and not overridden by another rule with higher specificity.',0,9,'2026-05-06 06:00:00','2026-05-06 06:00:00'),(9,5,36,NULL,'Try adding `display: block` inside your mobile media query to force the grid to collapse to single column. Also paste your CSS here and I can take a look at what\'s conflicting.',0,5,'2026-05-07 00:00:00','2026-05-07 00:00:00'),(10,6,46,NULL,'My mental model: count how many times the main operation executes as input n grows. Single loop = O(n). Nested loops = O(n?). Halving the input each iteration (like binary search) = O(log n). Recursive calls that split input = O(n log n) often.',0,14,'2026-05-05 02:00:00','2026-05-05 02:00:00'),(12,9,36,NULL,'Yes, you may use a CSS framework for styling only. The JavaScript must remain vanilla. Using Tailwind or Bootstrap for classes is fine as long as you write your own JS for interactivity. Note this in your README.',0,18,'2026-05-03 06:00:00','2026-05-03 06:00:00'),(13,9,53,NULL,'Thanks for the clarification! I\'ll use Tailwind for responsiveness and write clean vanilla JS for the CRUD operations. Will mention it in the README as instructed.',0,3,'2026-05-03 07:00:00','2026-05-03 07:00:00'),(14,11,48,NULL,'Think of in-order as: LEFT \Z ROOT \Z RIGHT. The recursion unwinds like a call stack. The deepest left call returns first, then its parent prints itself, then goes right. Draw a small BST and trace each call manually - it clicks instantly.',0,11,'2026-04-20 06:00:00','2026-04-20 06:00:00'),(15,12,57,NULL,'The most common cause is a missing `ip helper-address` command on the router interface. If the DHCP server is on a different network segment than the clients, the router needs to forward DHCP broadcast requests. Add: `ip helper-address <DHCP_server_IP>` on the client-facing interface.',0,16,'2026-03-15 06:00:00','2026-03-15 06:00:00'),(16,12,63,NULL,'That was it! I added the helper-address command and the PCs started receiving proper IPs from the pool. Thank you so much!',0,5,'2026-03-15 08:00:00','2026-03-15 08:00:00'),(17,14,57,NULL,'For a Library Management System with clear, stable requirements, Waterfall is defensible. However, Agile (Scrum) lets you deliver usable features in sprints and adapt when stakeholder needs change. For academic purposes, Scrum is strongly recommended as it teaches iteration.',0,9,'2026-02-10 06:00:00','2026-02-10 06:00:00'),(18,15,59,NULL,'A VIEW is essentially a saved SELECT query - good for simplifying complex joins you query frequently. A Stored Procedure can contain full logic: INSERT, UPDATE, loops, conditionals. Use VIEWs for read-only abstractions and stored procedures for business logic that modifies data.',0,13,'2026-03-20 06:00:00','2026-03-20 06:00:00'),(19,17,58,NULL,'Figma is the industry standard and free for education. Balsamiq is great for low-fidelity wireframes quickly. For this course, Figma is preferred since you can create both wireframes and high-fidelity prototypes in one tool. Either is acceptable but Figma is recommended.',0,8,'2026-03-10 06:00:00','2026-03-10 06:00:00'),(20,18,60,NULL,'Example: Average salaries in a company where the CEO earns 10M and 100 employees earn 25K. The mean would be skewed high by the CEO\'s salary and not represent most employees. The median (middle value) better represents what a \"typical\" employee earns. Always check for outliers first.',0,12,'2026-02-25 06:00:00','2026-02-25 06:00:00'),(21,19,36,NULL,'Binary is fundamental at the hardware level - all transistors operate as binary switches (on/off = 1/0). Machine code, ASCII/Unicode encoding, image pixels (RGB values), network MAC addresses, and even file permissions in Linux use binary. It\'s literally the language of computers.',0,17,'2026-02-08 06:00:00','2026-02-08 06:00:00'),(22,21,36,NULL,'hi',0,0,'2026-05-12 04:52:19','2026-05-12 04:52:19');
/*!40000 ALTER TABLE `forum_replies` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_forum_replies_after_update` AFTER UPDATE ON `forum_replies` FOR EACH ROW BEGIN

  IF OLD.is_flagged <> NEW.is_flagged THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NULL,

            IF(NEW.is_flagged = 1, 'reply_flagged', 'reply_unflagged'),

            'forum_replies', NEW.id,

            JSON_OBJECT('thread_id', NEW.thread_id));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `forum_threads`
--

DROP TABLE IF EXISTS `forum_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_threads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `forum_id` int(10) unsigned NOT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `title` varchar(300) NOT NULL,
  `body` longtext NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_forum_created` (`forum_id`,`created_at`),
  KEY `fk_ft_author` (`author_id`),
  CONSTRAINT `fk_ft_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ft_forum` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_threads`
--

LOCK TABLES `forum_threads` WRITE;
/*!40000 ALTER TABLE `forum_threads` DISABLE KEYS */;
INSERT INTO `forum_threads` VALUES (1,1,42,'Design Patterns in OOP - Real World Examples','I\'ve been reading about design patterns but I\'m struggling to connect them to real-world scenarios. Can anyone share concrete examples of the Factory, Observer, or Singleton patterns in actual applications?',0,0,0,45,'2026-05-05 01:30:00','2026-05-08 10:54:34'),(2,2,36,'Best Practices for Responsive Web Design','Let\'s discuss best practices for building responsive layouts. What techniques, frameworks, or CSS features have you found most effective in your projects?',1,0,0,62,'2026-05-06 02:00:00','2026-05-08 10:54:34'),(3,3,47,'Recursion vs Iteration - Which is Better?','We covered both recursion and iteration in class. When is it better to use one over the other? Is there always a performance difference?',0,0,0,58,'2026-05-04 06:00:00','2026-05-08 10:54:34'),(4,1,36,'Midterm Exam Clarifications & FAQ','Please post all your clarifications about the midterm exam here. I\'ll answer each one. Topics covered: Inheritance, Polymorphism, Interfaces, and Abstract Classes.',1,0,0,120,'2026-05-02 00:00:00','2026-05-08 10:54:34'),(5,2,37,'CSS Grid not working on mobile - help!','I\'m building my portfolio project and my CSS grid layout breaks on screens below 480px. I\'ve tried adding a media query but it doesn\'t seem to override the desktop layout. Any advice?',0,0,0,33,'2026-05-06 06:30:00','2026-05-08 10:54:34'),(6,3,48,'Understanding Big-O Notation Practically','I understand the theory of Big-O but I\'m struggling to apply it when analyzing my own code. What mental model do you use to estimate the time complexity of a loop or recursive function?',0,0,0,41,'2026-05-05 08:00:00','2026-05-08 10:54:34'),(8,1,36,'Study Guide: OOP Finals Coverage','Here is the final exam coverage: OOP Principles, Inheritance, Polymorphism, Interfaces, Exception Handling, and Collections. Focus on applied problems not just definitions.',1,0,0,210,'2026-04-30 23:00:00','2026-04-30 23:00:00'),(9,2,53,'Can we use a CSS framework for Project 3?','The instructions say to use vanilla JS but it doesn\'t mention CSS frameworks. Can we use Tailwind or Bootstrap for styling the front-end?',0,0,0,55,'2026-05-03 01:00:00','2026-05-03 01:00:00'),(10,2,36,'Important: Project 3 Final Rubric','The final rubric for Project 3 is now set. 40pts functionality, 30pts UI/UX, 20pts code quality, 10pts documentation. Use the attached template for the README file.',1,0,0,195,'2026-05-04 00:00:00','2026-05-04 00:00:00'),(11,3,50,'Confused about recursive tree traversal','I understand how to traverse iteratively but recursive in-order traversal is confusing me. When exactly does the recursion \"unwind\"? A diagram would help.',0,0,0,62,'2026-04-20 02:00:00','2026-04-20 02:00:00'),(12,6,63,'Packet Tracer: DHCP not assigning IPs','I configured a DHCP server in Packet Tracer but the PCs are getting APIPA addresses (169.254.x.x) instead of addresses from the pool. What am I missing?',0,0,0,47,'2026-03-15 01:00:00','2026-03-15 01:00:00'),(13,6,57,'Lab 3 Tips and Common Mistakes','For Lab 3 OSPF config, make sure you declare the correct wildcard mask and enable OSPF on the correct interfaces. A common mistake is using subnet mask instead of wildcard.',1,0,0,130,'2026-04-07 23:00:00','2026-04-07 23:00:00'),(14,7,73,'Choosing between Agile and Waterfall for Sprint 1','Our group is debating which SDLC to use for our project. Our project is a Library Management System. Which methodology fits better and why?',0,0,0,44,'2026-02-10 02:00:00','2026-02-10 02:00:00'),(15,8,83,'Difference between VIEW and stored procedure?','Both seem to encapsulate SQL logic. When do you use a VIEW versus a stored procedure? Are there performance implications?',0,0,0,71,'2026-03-20 01:00:00','2026-03-20 01:00:00'),(16,8,59,'SQL Lab 3 Reminder and Tips','For Lab 3, focus on AFTER INSERT triggers for audit logs and make sure your stored procedures handle NULL inputs gracefully. Use DELIMITER correctly in your script.',1,0,0,155,'2026-04-21 23:00:00','2026-04-21 23:00:00'),(17,9,76,'What tools to use for UX wireframing?','We were asked to create wireframes for our HCI project. The instructor mentioned Figma but is Balsamiq or Adobe XD acceptable? Any recommendations?',0,0,0,39,'2026-03-10 02:00:00','2026-03-10 02:00:00'),(18,10,93,'Mean vs Median - when to use which?','In our statistics module, the professor said to use median for skewed data. Can someone explain with a real-world example? I\'m still confused.',0,0,0,33,'2026-02-25 03:00:00','2026-02-25 03:00:00'),(19,11,66,'Is binary still used in modern computing?','We learned about binary in Unit 1. I\'m curious - where is binary actually used in modern hardware and software? I know CPUs use it but can someone elaborate?',0,0,0,52,'2026-02-08 01:00:00','2026-02-08 01:00:00'),(20,11,36,'Finals Review: Key Concepts from Unit 1 and 2','For the finals, focus on: computer history timeline, binary/hex conversions, and the fetch-decode-execute cycle. Practice all conversion problems from the worksheets.',1,0,0,180,'2026-05-07 23:00:00','2026-05-07 23:00:00'),(21,1,36,'test discussion','a',0,0,0,1,'2026-05-12 04:52:06','2026-05-12 04:52:19');
/*!40000 ALTER TABLE `forum_threads` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_forum_threads_after_update` AFTER UPDATE ON `forum_threads` FOR EACH ROW BEGIN

  IF OLD.is_flagged <> NEW.is_flagged THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NULL,

            IF(NEW.is_flagged = 1, 'thread_flagged', 'thread_unflagged'),

            'forum_threads', NEW.id,

            JSON_OBJECT('forum_id', NEW.forum_id, 'title', NEW.title));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `forums`
--

DROP TABLE IF EXISTS `forums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forums` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_forum_section` (`section_id`),
  CONSTRAINT `fk_forum_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forums`
--

LOCK TABLES `forums` WRITE;
/*!40000 ALTER TABLE `forums` DISABLE KEYS */;
INSERT INTO `forums` VALUES (1,1,'IT 106 OOP General Discussion','Ask questions and discuss topics related to Object-Oriented Programming.',0,'2026-05-08 10:54:34'),(2,2,'IT 301 Web Programming Discussion','Discussion board for Web Programming topics and project help.',0,'2026-05-08 10:54:34'),(3,3,'IT 201 Data Structures Discussion','Discuss algorithms, data structures, and problem-solving approaches.',0,'2026-05-08 10:54:34'),(4,4,'IT 411 Capstone Forum','Research discussions, proposal feedback, and capstone project updates.',0,'2026-05-08 10:54:34'),(6,6,'IT 204 Computer Networks Discussion','Ask questions about networking concepts, labs, and configurations.',0,'2026-01-06 00:00:00'),(7,7,'IT 305 Software Engineering Discussion','Discussion on SDLC models, UML, agile practices, and project management.',0,'2026-01-06 00:00:00'),(8,9,'IT 310 Database Systems Discussion','SQL queries, normalization, and database design questions here.',0,'2026-01-06 00:00:00'),(9,11,'IT 320 HCI Discussion','Discuss UI/UX principles, usability testing, and design tools like Figma.',0,'2026-01-06 00:00:00'),(10,13,'GE 001 Math in the Modern World','Q&A for mathematics topics, statistics problems, and concept clarifications.',0,'2026-01-06 00:00:00'),(11,15,'IT 101 Introduction to Computing','General Q&A for beginners. No question is too basic here!',0,'2026-01-06 00:00:00');
/*!40000 ALTER TABLE `forums` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_components`
--

DROP TABLE IF EXISTS `grade_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_components` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `weight_pct` decimal(5,2) NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_gc_section` (`section_id`),
  CONSTRAINT `fk_gc_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_components`
--

LOCK TABLES `grade_components` WRITE;
/*!40000 ALTER TABLE `grade_components` DISABLE KEYS */;
INSERT INTO `grade_components` VALUES (1,1,'Quizzes',20.00,1),(2,1,'Laboratory',30.00,2),(3,1,'Midterm Exam',25.00,3),(4,1,'Finals Exam',25.00,4),(5,2,'Quizzes',20.00,1),(6,2,'Projects',40.00,2),(7,2,'Midterm Exam',20.00,3),(8,2,'Finals Exam',20.00,4),(9,3,'Quizzes',25.00,1),(10,3,'Problem Sets',35.00,2),(11,3,'Midterm Exam',20.00,3),(12,3,'Finals Exam',20.00,4),(13,4,'Submissions',40.00,1),(14,4,'Oral Defense',30.00,2),(15,4,'Written Report',30.00,3),(16,6,'Quizzes',20.00,1),(17,6,'Laboratory',40.00,2),(18,6,'Midterm Exam',20.00,3),(19,6,'Finals Exam',20.00,4),(20,7,'Quizzes',15.00,1),(21,7,'Sprint Output',45.00,2),(22,7,'Midterm Exam',20.00,3),(23,7,'Finals Exam',20.00,4),(24,9,'Quizzes',20.00,1),(25,9,'Laboratory',40.00,2),(26,9,'Midterm Exam',20.00,3),(27,9,'Finals Exam',20.00,4),(28,15,'Quizzes',20.00,1),(29,15,'Lab Activities',30.00,2),(30,15,'Midterm Exam',25.00,3),(31,15,'Finals Exam',25.00,4);
/*!40000 ALTER TABLE `grade_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gradebook_items`
--

DROP TABLE IF EXISTS `gradebook_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gradebook_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `component_id` int(10) unsigned NOT NULL,
  `item_type` enum('assignment','quiz') NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
  PRIMARY KEY (`id`),
  KEY `idx_gi_section` (`section_id`),
  KEY `idx_gi_component` (`component_id`),
  CONSTRAINT `fk_gi_component` FOREIGN KEY (`component_id`) REFERENCES `grade_components` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_gi_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gradebook_items`
--

LOCK TABLES `gradebook_items` WRITE;
/*!40000 ALTER TABLE `gradebook_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `gradebook_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instructor_profiles`
--

DROP TABLE IF EXISTS `instructor_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instructor_profiles` (
  `user_id` int(10) unsigned NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `specialization` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_employee_id` (`employee_id`),
  KEY `fk_ip_dept` (`department_id`),
  CONSTRAINT `fk_ip_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ip_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instructor_profiles`
--

LOCK TABLES `instructor_profiles` WRITE;
/*!40000 ALTER TABLE `instructor_profiles` DISABLE KEYS */;
INSERT INTO `instructor_profiles` VALUES (36,'EMP-2024-036',1,'Instructor I',NULL);
/*!40000 ALTER TABLE `instructor_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lesson_progress`
--

DROP TABLE IF EXISTS `lesson_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lesson_progress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `lesson_id` int(10) unsigned NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `time_spent_sec` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lp` (`student_id`,`lesson_id`),
  KEY `idx_lp_lesson` (`lesson_id`),
  CONSTRAINT `fk_lp_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lp_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lesson_progress`
--

LOCK TABLES `lesson_progress` WRITE;
/*!40000 ALTER TABLE `lesson_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `lesson_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lessons`
--

DROP TABLE IF EXISTS `lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lessons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `lesson_type` enum('reading','video','audio','slide','link','scorm') NOT NULL DEFAULT 'reading',
  `content` longtext DEFAULT NULL,
  `resource_url` varchar(500) DEFAULT NULL,
  `duration_min` smallint(5) unsigned DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_module_order` (`module_id`,`sort_order`),
  CONSTRAINT `fk_les_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lessons`
--

LOCK TABLES `lessons` WRITE;
/*!40000 ALTER TABLE `lessons` DISABLE KEYS */;
INSERT INTO `lessons` VALUES (1,1,'Introduction to Java Classes','reading','Core concepts of Java class definition, fields, and methods.',NULL,NULL,1,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(2,1,'Lab Demo: Your First Java Class (Video)','video','Walkthrough of creating a basic Student class.',NULL,NULL,2,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(3,2,'Inheritance in Java - Slides','slide','PowerPoint covering extends keyword and super() calls.',NULL,NULL,1,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(4,2,'Polymorphism Examples','reading','Text guide on method overriding vs overloading.',NULL,NULL,2,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(5,3,'Interfaces vs Abstract Classes','reading','When to use interface vs abstract class with real examples.',NULL,NULL,1,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(6,3,'Lab: Shape Hierarchy Implementation','reading','Step-by-step guide to implementing a shape class hierarchy.',NULL,NULL,2,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(7,4,'Exception Handling - Slides','slide','PowerPoint deck covering try-catch-finally patterns.',NULL,NULL,1,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(8,4,'Custom Exception Workshop (Video)','video','Live coding session creating custom exception classes.',NULL,NULL,2,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(9,5,'HTML5 Semantic Tags Reference','reading','Complete guide to header, nav, section, article, aside, footer.',NULL,NULL,1,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(10,5,'CSS Variables and Custom Properties','reading','How to use :root variables to build consistent design systems.',NULL,NULL,2,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(11,6,'CSS Grid Complete Guide','reading','Comprehensive guide to grid-template-columns, rows, and areas.',NULL,NULL,1,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(12,6,'Flexbox vs Grid Decision Cheatsheet','reading','When to use Flexbox vs Grid - practical decision guide.',NULL,NULL,2,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(13,7,'Arrays in Java - Detailed Notes','reading','Single and multidimensional arrays, ArrayList, and time complexities.',NULL,NULL,1,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(14,7,'Linked List Implementation Guide','reading','Singly linked list from scratch with insert, delete, search.',NULL,NULL,2,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(15,8,'Research Problem Formulation','reading','How to identify, frame, and articulate a research problem.',NULL,NULL,1,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(16,8,'Literature Review Writing Guide','reading','How to structure a literature review for a capstone proposal.',NULL,NULL,2,1,'2026-05-08 10:54:34','2026-05-08 10:54:34'),(17,9,'mod_1_9_1778240312.pdf','slide',NULL,'uploads/modules/mod_2_9_1778481112.pdf',NULL,1,1,'2026-05-11 06:31:52','2026-05-11 06:31:52'),(18,10,'mod_1_9_1778240312.pdf','slide',NULL,'uploads/modules/mod_5_10_1778489191.pdf',NULL,1,1,'2026-05-11 08:46:31','2026-05-11 08:46:31'),(19,11,'12 - IT104 - IPT1.pptx','slide',NULL,'uploads/modules/mod_1_11_1778492384.pptx',NULL,1,1,'2026-05-11 09:39:44','2026-05-11 09:39:44');
/*!40000 ALTER TABLE `lessons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media_files`
--

DROP TABLE IF EXISTS `media_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uploader_id` int(10) unsigned NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size_kb` int(10) unsigned NOT NULL DEFAULT 0,
  `file_path` varchar(500) NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stored_name` (`stored_name`),
  KEY `idx_uploader` (`uploader_id`),
  CONSTRAINT `fk_mf_uploader` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media_files`
--

LOCK TABLES `media_files` WRITE;
/*!40000 ALTER TABLE `media_files` DISABLE KEYS */;
INSERT INTO `media_files` VALUES (1,36,'mod_1_9_1778240312.pdf','mod_2_9_1778481112.pdf','application/pdf',310,'uploads/modules/mod_2_9_1778481112.pdf',1,'2026-05-11 06:31:52'),(2,36,'dicebg.jpg','avatar_36_1778481213.jpg','image/jpeg',27,'uploads/avatars/avatar_36_1778481213.jpg',1,'2026-05-11 06:33:33'),(3,36,'mod_1_9_1778240312.pdf','mod_5_10_1778489191.pdf','application/pdf',310,'uploads/modules/mod_5_10_1778489191.pdf',1,'2026-05-11 08:46:31'),(4,36,'12 - IT104 - IPT1.pptx','mod_1_11_1778492384.pptx','application/vnd.openxmlformats-officedocument.presentationml.presentation',10415,'uploads/modules/mod_1_11_1778492384.pptx',1,'2026-05-11 09:39:44'),(5,36,'toro inoue classroom.jpg','avatar_36_1778497754.jpg','image/jpeg',59,'uploads/avatars/avatar_36_1778497754.jpg',1,'2026-05-11 11:09:14');
/*!40000 ALTER TABLE `media_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_section_order` (`section_id`,`sort_order`),
  CONSTRAINT `fk_mod_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (1,1,'Week 1-2: Introduction to OOP','Covers classes, objects, attributes, methods, and basic OOP principles.',1,1,'2026-01-13 00:00:00','2026-05-08 10:54:34','2026-05-08 10:54:34'),(2,1,'Week 3-4: Inheritance and Polymorphism','Deep dive into inheritance hierarchies, method overriding, and polymorphism.',2,1,'2026-01-27 00:00:00','2026-05-08 10:54:34','2026-05-08 10:54:34'),(3,1,'Week 5-6: Interfaces and Abstract Classes','Understanding contracts, abstract methods, and default implementations.',3,1,'2026-02-10 00:00:00','2026-05-08 10:54:34','2026-05-08 10:54:34'),(4,1,'Week 7-8: Exception Handling','Covers try-catch-finally, checked/unchecked exceptions, and custom exceptions.',4,1,'2026-02-24 00:00:00','2026-05-08 10:54:34','2026-05-08 10:54:34'),(5,2,'Module 1: HTML5 & CSS3 Fundamentals','Semantic HTML, CSS specificity, the box model, and CSS variables.',1,1,'2026-01-13 01:00:00','2026-05-08 10:54:34','2026-05-08 10:54:34'),(6,2,'Module 2: Flexbox and CSS Grid','Modern layout techniques for responsive and complex page designs.',2,1,'2026-02-03 01:00:00','2026-05-08 10:54:34','2026-05-08 10:54:34'),(7,3,'Unit 1: Arrays and Linked Lists','Data structure fundamentals: arrays, singly/doubly linked lists.',1,1,'2026-01-14 02:00:00','2026-05-08 10:54:34','2026-05-08 10:54:34'),(8,4,'Chapter 1: Research Methodology','Introduction to research methods, problem identification, and proposal writing.',1,1,'2026-01-15 03:00:00','2026-05-08 10:54:34','2026-05-08 10:54:34'),(9,2,'introduction','hello testing',3,1,'2026-05-11 06:31:52','2026-05-11 06:31:52','2026-05-11 06:31:52'),(10,5,'s','s',1,1,'2026-05-11 08:46:31','2026-05-11 08:46:31','2026-05-11 08:46:31'),(11,1,'Week 6','my module',5,1,'2026-05-11 09:39:44','2026-05-11 09:39:44','2026-05-11 09:39:44'),(12,1,'Week 11-12: File I/O and Serialization','Reading/writing files, ObjectInputStream/OutputStream, and data persistence.',6,1,'2026-03-23 16:00:00','2026-03-23 00:00:00','2026-03-23 00:00:00'),(13,2,'Module 3: JavaScript Fundamentals','Variables, functions, DOM manipulation, events, and ES6+ features.',3,1,'2026-02-16 17:00:00','2026-02-16 00:00:00','2026-02-16 00:00:00'),(14,2,'Module 4: AJAX and REST APIs','Fetch API, Axios, JSON handling, and consuming RESTful web services.',4,1,'2026-03-09 17:00:00','2026-03-09 00:00:00','2026-03-09 00:00:00'),(15,3,'Unit 2: Stacks and Queues','Stack and queue ADTs, applications in expression evaluation and BFS/DFS.',2,1,'2026-01-27 18:00:00','2026-01-27 00:00:00','2026-01-27 00:00:00'),(16,3,'Unit 3: Trees and Graphs','Binary trees, BSTs, AVL trees, graph representations, and traversals.',3,1,'2026-02-17 18:00:00','2026-02-17 00:00:00','2026-02-17 00:00:00'),(17,3,'Unit 4: Sorting and Searching','Bubble, selection, merge, quick sort, and binary search with complexity analysis.',4,1,'2026-03-10 18:00:00','2026-03-10 00:00:00','2026-03-10 00:00:00'),(18,4,'Chapter 2: Review of Related Literature','Strategies for finding, evaluating, and synthesizing academic literature.',2,1,'2026-01-28 19:00:00','2026-01-28 00:00:00','2026-01-28 00:00:00'),(19,4,'Chapter 3: System Design','Architecture diagrams, ER diagrams, flowcharts, and prototyping methods.',3,1,'2026-02-18 19:00:00','2026-02-18 00:00:00','2026-02-18 00:00:00'),(20,6,'Module 1: OSI and TCP/IP Models','Seven-layer OSI model, TCP/IP stack, encapsulation, and protocol comparison.',1,1,'2026-01-13 20:00:00','2026-01-13 00:00:00','2026-01-13 00:00:00'),(21,6,'Module 2: IP Addressing and Subnetting','IPv4/IPv6 addressing, CIDR notation, subnetting calculations, and VLSM.',2,1,'2026-02-03 20:00:00','2026-02-03 00:00:00','2026-02-03 00:00:00'),(22,6,'Module 3: Routing and Switching','Static routing, dynamic routing protocols (OSPF, RIP), and VLANs.',3,1,'2026-02-24 20:00:00','2026-02-24 00:00:00','2026-02-24 00:00:00'),(23,7,'Week 1-2: SDLC Models','Waterfall, Agile, Scrum, and DevOps methodologies compared.',1,1,'2026-01-14 21:00:00','2026-01-14 00:00:00','2026-01-14 00:00:00'),(24,7,'Week 3-4: Requirements Engineering','Functional/non-functional requirements, use case diagrams, and user stories.',2,1,'2026-01-28 21:00:00','2026-01-28 00:00:00','2026-01-28 00:00:00'),(25,7,'Week 5-6: System Design and UML','Class diagrams, sequence diagrams, activity diagrams, and design patterns.',3,1,'2026-02-11 21:00:00','2026-02-11 00:00:00','2026-02-11 00:00:00'),(26,9,'Module 1: Relational Model and SQL','Tables, primary/foreign keys, DDL, DML, and basic SELECT queries.',1,1,'2026-01-15 22:00:00','2026-01-15 00:00:00','2026-01-15 00:00:00'),(27,9,'Module 2: Advanced SQL','Joins, subqueries, aggregate functions, views, stored procedures, and triggers.',2,1,'2026-02-05 22:00:00','2026-02-05 00:00:00','2026-02-05 00:00:00'),(28,9,'Module 3: Database Normalization','1NF, 2NF, 3NF, BCNF, and denormalization trade-offs with real-world case studies.',3,1,'2026-02-26 22:00:00','2026-02-26 00:00:00','2026-02-26 00:00:00'),(29,15,'Unit 1: History of Computing','Evolution from vacuum tubes to modern microprocessors, key inventors and milestones.',1,1,'2026-01-15 23:00:00','2026-01-15 00:00:00','2026-01-15 00:00:00'),(30,15,'Unit 2: Binary and Number Systems','Binary, octal, hexadecimal conversions and arithmetic operations.',2,1,'2026-02-05 23:00:00','2026-02-05 00:00:00','2026-02-05 00:00:00');
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_id` int(10) unsigned NOT NULL,
  `sender_id` int(10) unsigned DEFAULT NULL,
  `notification_type` enum('new_announcement','new_assignment','assignment_graded','quiz_available','submission_received','new_reply','enrollment','grade_released','system_alert','new_module') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recipient_read` (`recipient_id`,`is_read`),
  KEY `idx_notif_created` (`created_at`),
  KEY `idx_notif_sender` (`sender_id`),
  CONSTRAINT `fk_notif_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notif_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,55,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:12:45'),(2,56,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:12:45'),(3,48,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:12:45'),(4,37,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:12:45'),(5,38,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:12:45'),(6,37,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(7,38,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(8,39,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(9,40,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(10,41,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(11,42,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(12,43,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(13,44,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(14,45,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(15,46,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:14:34'),(16,37,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(17,38,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(18,39,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(19,40,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(20,41,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(21,42,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(22,43,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(23,44,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(24,45,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(25,46,NULL,'quiz_available','New Quiz: quiz 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:39:05'),(26,37,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(27,38,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(28,39,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(29,40,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(30,41,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(31,42,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(32,43,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(33,44,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(34,45,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(35,46,NULL,'quiz_available','New Quiz: TESTING','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:51:40'),(36,37,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(37,38,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(38,39,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(39,40,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(40,41,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(41,42,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(42,43,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(43,44,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(44,45,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(45,46,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 07:58:34'),(46,37,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(47,38,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(48,39,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(49,40,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(50,41,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(51,42,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(52,43,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(53,44,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(54,45,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(55,46,NULL,'quiz_available','New Quiz: test','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 08:05:16'),(56,37,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(57,38,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(58,39,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(59,43,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(60,52,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(61,53,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(62,54,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(63,44,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(64,45,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(65,41,NULL,'new_assignment','New Assignment: module 10','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-11 09:27:19'),(66,38,36,'assignment_graded','Lab 5 Graded - Score: 96/100','Your Lab Activity 5 submission has been graded. Score: 96/100. Check feedback in the Assignments tab.','submissions',2,1,'2026-05-10 01:00:00','2026-05-08 18:00:00'),(67,40,36,'assignment_graded','Lab 5 Graded - Score: 72/100','Your Lab Activity 5 submission has been graded. Score: 72/100. Late penalty was applied.','submissions',4,1,'2026-05-11 02:00:00','2026-05-10 22:00:00'),(68,42,36,'assignment_graded','Lab 5 Graded - Score: 89/100','Your Lab Activity 5 submission has been graded. Score: 89/100.','submissions',5,0,NULL,'2026-05-09 17:00:00'),(69,47,36,'assignment_graded','Problem Set 3 Graded - Score: 84/100','Your Problem Set 3 submission has been graded. Score: 84/100.','submissions',11,1,'2026-04-27 00:00:00','2026-04-25 17:00:00'),(70,48,36,'assignment_graded','Problem Set 3 Graded - Score: 91/100','Your Problem Set 3 submission has been graded. Score: 91/100. Excellent work!','submissions',13,1,'2026-04-27 01:00:00','2026-04-25 18:00:00'),(71,37,36,'new_assignment','New Assignment: Lab Activity 6','A new assignment has been posted in IT 106 OOP. Due: May 24, 2026.','assignments',6,0,NULL,'2026-05-12 00:00:00'),(72,38,36,'new_assignment','New Assignment: Lab Activity 6','A new assignment has been posted in IT 106 OOP. Due: May 24, 2026.','assignments',6,0,NULL,'2026-05-12 00:00:00'),(73,41,36,'new_assignment','New Assignment: Lab Activity 6','A new assignment has been posted in IT 106 OOP. Due: May 24, 2026.','assignments',6,0,NULL,'2026-05-12 00:00:00'),(74,61,57,'new_assignment','New Assignment: Lab 3 Routing Config','A new assignment has been posted in IT 204 Computer Networks. Due: April 23, 2026.','assignments',12,1,'2026-04-08 02:00:00','2026-04-07 00:00:00'),(75,62,57,'new_assignment','New Assignment: Lab 3 Routing Config','A new assignment has been posted in IT 204 Computer Networks. Due: April 23, 2026.','assignments',12,1,'2026-04-08 03:00:00','2026-04-07 00:00:00'),(76,37,36,'new_announcement','New Announcement: Lab Activity 6 Now Available','A new announcement has been posted in IT 106 OOP.','announcements',9,0,NULL,'2026-05-12 00:00:00'),(77,38,36,'new_announcement','New Announcement: Lab Activity 6 Now Available','A new announcement has been posted in IT 106 OOP.','announcements',9,0,NULL,'2026-05-12 00:00:00'),(78,55,36,'new_announcement','New Announcement: Capstone Defense Schedule','A new announcement has been posted in IT 411 Capstone.','announcements',12,1,'2026-05-11 01:00:00','2026-05-10 00:00:00'),(79,56,36,'new_announcement','New Announcement: Capstone Defense Schedule','A new announcement has been posted in IT 411 Capstone.','announcements',12,0,NULL,'2026-05-10 00:00:00'),(80,37,36,'new_module','New Module: Week 9-10 Collections and Generics','A new module has been published in IT 106 OOP.','modules',11,1,'2026-03-11 00:00:00','2026-03-10 00:00:00'),(81,38,36,'new_module','New Module: Week 9-10 Collections and Generics','A new module has been published in IT 106 OOP.','modules',11,0,NULL,'2026-03-10 00:00:00'),(82,61,57,'new_module','New Module: Module 3 Routing and Switching','A new module has been published in IT 204 Computer Networks.','modules',22,1,'2026-02-26 02:00:00','2026-02-25 00:00:00'),(83,36,37,'submission_received','Submission: Ryza Marie Gabriel - Lab 5','A new submission has been received for Lab Activity 5.','submissions',1,1,'2026-05-09 00:00:00','2026-05-08 22:15:00'),(84,36,39,'submission_received','Submission: Nicole Abalos - Lab 5','A new submission has been received for Lab Activity 5.','submissions',3,1,'2026-05-09 00:00:00','2026-05-08 16:30:00'),(85,57,61,'submission_received','Submission: Josh Dela Pena - Lab 1','A new submission has been received for Lab 1: Network Topology.','submissions',NULL,1,'2026-03-05 01:00:00','2026-03-04 06:00:00'),(86,36,37,'new_reply','New Reply in: Best Practices for Responsive Web Design','Ryza Marie Gabriel replied to a thread in IT 301 Web Programming Discussion.','forum_threads',2,1,'2026-05-07 01:00:00','2026-05-06 01:00:00'),(87,42,36,'new_reply','New Reply in: CSS Grid not working on mobile','Instructor replied to your thread.','forum_threads',5,1,'2026-05-07 02:00:00','2026-05-07 00:00:00'),(88,37,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,1,NULL,'2026-05-12 03:56:20'),(89,38,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-12 03:56:20'),(90,39,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-12 03:56:20'),(91,43,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-12 03:56:20'),(92,52,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-12 03:56:20'),(93,53,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-12 03:56:20'),(94,54,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-12 03:56:20'),(95,44,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-12 03:56:20'),(96,45,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-12 03:56:20'),(97,41,NULL,'new_assignment','New Assignment: my assignment','A new assignment has been posted in your course.',NULL,NULL,0,NULL,'2026-05-12 03:56:20'),(98,37,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(99,38,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(100,39,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(101,43,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(102,52,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(103,53,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(104,54,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(105,44,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(106,45,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(107,41,NULL,'quiz_available','New Quiz: test 2','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:45:04'),(108,37,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59'),(109,38,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59'),(110,39,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59'),(111,43,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59'),(112,52,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59'),(113,53,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59'),(114,54,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59'),(115,44,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59'),(116,45,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59'),(117,41,NULL,'quiz_available','New Quiz: lala','A new quiz has been posted in your course.',NULL,NULL,0,NULL,'2026-05-15 23:54:59');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`),
  KEY `idx_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_settings`
--

LOCK TABLES `platform_settings` WRITE;
/*!40000 ALTER TABLE `platform_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_platform_settings_after_upd` AFTER UPDATE ON `platform_settings` FOR EACH ROW BEGIN

  IF OLD.setting_value <> NEW.setting_value THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NULL, 'platform_setting_changed', 'platform_settings', NEW.id,

            JSON_OBJECT('key', NEW.setting_key,

                        'old_value', OLD.setting_value,

                        'new_value', NEW.setting_value));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `profile_pictures`
--

DROP TABLE IF EXISTS `profile_pictures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_pictures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL COMMENT 'Server path or cloud URL',
  `file_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(10) unsigned DEFAULT NULL COMMENT 'Bytes',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = current avatar',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pp_user` (`user_id`),
  KEY `idx_pp_active` (`user_id`,`is_active`),
  CONSTRAINT `fk_pp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profile_pictures`
--

LOCK TABLES `profile_pictures` WRITE;
/*!40000 ALTER TABLE `profile_pictures` DISABLE KEYS */;
/*!40000 ALTER TABLE `profile_pictures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `college` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_program_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'BSCS-ST','BS in Computer Science (Specialization in Software Technology)','College of Computer Studies','2026-05-05 03:02:46'),(2,'BSIT-TP','BS in Information Technology (Specialization in Technopreneurship)','College of Computer Studies','2026-05-05 03:02:46'),(3,'BEEd','Bachelor in Elementary Education','College of Education','2026-05-05 03:02:46'),(4,'BSEd-ENG','Bachelor in Secondary Education (Major in English)','College of Education','2026-05-05 03:02:46'),(5,'BSEd-FIL','Bachelor in Secondary Education (Major in Filipino)','College of Education','2026-05-05 03:02:46'),(6,'BSEd-MATH','Bachelor in Secondary Education (Major in Mathematics)','College of Education','2026-05-05 03:02:46'),(7,'BSA','BS in Accountancy','College of Business and Accountancy','2026-05-05 03:02:46'),(8,'BSBA-MM','BS in Business Administration (Major in Marketing Management)','College of Business and Accountancy','2026-05-05 03:02:46'),(9,'BSBA-HRDM','BS in Business Administration (Major in Human Resource Development Management)','College of Business and Accountancy','2026-05-05 03:02:46'),(10,'BSEntrep','BS in Entrepreneurship','College of Business and Accountancy','2026-05-05 03:02:46'),(11,'BSEcE','BS in Electronics Engineering','College of Engineering','2026-05-05 03:02:46'),(12,'BSN','BS in Nursing','College of Nursing','2026-05-05 03:02:46'),(13,'BPA','Bachelor of Public Administration','College of Public Administration','2026-05-05 03:02:46');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_choices`
--

DROP TABLE IF EXISTS `question_choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_choices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` int(10) unsigned NOT NULL,
  `choice_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_qc_question` (`question_id`),
  CONSTRAINT `fk_qc_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_choices`
--

LOCK TABLES `question_choices` WRITE;
/*!40000 ALTER TABLE `question_choices` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_choices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quiz_id` int(10) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay','matching') NOT NULL,
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `explanation` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quiz_order` (`quiz_id`,`sort_order`),
  CONSTRAINT `fk_q_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_answers`
--

DROP TABLE IF EXISTS `quiz_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_answers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attempt_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned NOT NULL,
  `selected_choice` int(10) unsigned DEFAULT NULL,
  `text_answer` text DEFAULT NULL,
  `points_earned` decimal(5,2) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `instructor_note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_answer` (`attempt_id`,`question_id`),
  KEY `idx_ans_question` (`question_id`),
  KEY `idx_ans_choice` (`selected_choice`),
  CONSTRAINT `fk_ans_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ans_choice` FOREIGN KEY (`selected_choice`) REFERENCES `question_choices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ans_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_answers`
--

LOCK TABLES `quiz_answers` WRITE;
/*!40000 ALTER TABLE `quiz_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_attempts`
--

DROP TABLE IF EXISTS `quiz_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quiz_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `attempt_number` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `time_taken_sec` int(10) unsigned DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `is_passed` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attempt` (`quiz_id`,`student_id`,`attempt_number`),
  KEY `idx_qa_student` (`student_id`),
  CONSTRAINT `fk_qa_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_qa_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_attempts`
--

LOCK TABLES `quiz_attempts` WRITE;
/*!40000 ALTER TABLE `quiz_attempts` DISABLE KEYS */;
INSERT INTO `quiz_attempts` VALUES (1,1,37,1,'2026-04-20 00:05:00','2026-04-20 00:29:00',1440,92.00,1,'graded'),(2,1,38,1,'2026-04-20 00:10:00','2026-04-20 00:38:00',1680,88.00,1,'graded'),(3,1,39,1,'2026-04-20 00:08:00','2026-04-20 00:38:00',1800,75.00,1,'graded'),(4,1,52,1,'2026-04-20 00:12:00','2026-04-20 00:34:00',1320,61.00,0,'graded'),(5,1,53,1,'2026-04-20 00:07:00','2026-04-20 00:34:00',1620,84.00,1,'graded'),(6,1,54,1,'2026-04-20 00:09:00','2026-04-20 00:34:00',1500,79.00,1,'graded'),(7,1,44,1,'2026-04-20 00:06:00','2026-04-20 00:25:00',1140,95.00,1,'graded'),(8,1,45,1,'2026-04-20 00:11:00','2026-04-20 00:37:00',1560,88.00,1,'graded'),(9,2,37,1,'2026-04-15 00:05:00','2026-04-15 01:43:00',3480,84.00,1,'graded'),(10,2,38,1,'2026-04-15 00:03:00','2026-04-15 01:18:00',3300,91.00,1,'graded'),(11,2,39,1,'2026-04-15 00:04:00','2026-04-15 02:04:00',3600,78.00,1,'graded'),(12,2,40,1,'2026-04-15 00:06:00','2026-04-15 01:58:00',3120,62.00,0,'graded'),(13,2,42,1,'2026-04-15 00:02:00','2026-04-15 01:26:00',3240,73.00,0,'graded'),(14,2,41,1,'2026-04-15 00:07:00','2026-04-15 02:07:00',3600,55.00,0,'graded'),(15,2,44,1,'2026-04-15 00:04:00','2026-04-15 01:32:00',2880,88.00,1,'graded'),(16,2,45,1,'2026-04-15 00:01:00','2026-04-15 01:07:00',3060,96.00,1,'graded'),(17,3,47,1,'2026-05-07 00:15:00','2026-05-07 00:33:00',1080,90.00,1,'graded'),(18,3,48,1,'2026-05-07 00:10:00','2026-05-07 00:25:00',900,95.00,1,'graded'),(19,3,49,1,'2026-05-07 00:20:00','2026-05-07 00:40:00',1200,82.00,1,'graded'),(20,3,50,1,'2026-05-07 00:18:00','2026-05-07 00:37:00',1140,76.00,1,'graded'),(21,3,51,1,'2026-05-07 00:14:00','2026-05-07 00:31:00',1020,88.00,1,'graded'),(22,5,37,1,'2026-04-08 00:05:00','2026-04-08 00:27:00',1320,88.00,1,'graded'),(23,5,38,1,'2026-04-08 00:03:00','2026-04-08 00:23:00',1200,94.00,1,'graded'),(24,5,39,1,'2026-04-08 00:04:00','2026-04-08 00:28:00',1440,81.00,1,'graded'),(25,5,40,1,'2026-04-08 00:06:00','2026-04-08 00:31:00',1500,67.00,0,'graded'),(26,5,42,1,'2026-04-08 00:02:00','2026-04-08 00:25:00',1380,76.00,1,'graded'),(27,9,37,1,'2026-02-09 23:05:00','2026-02-09 23:22:00',1020,88.00,1,'graded'),(28,9,38,1,'2026-02-09 23:08:00','2026-02-09 23:27:00',1140,92.00,1,'graded'),(29,9,39,1,'2026-02-09 23:06:00','2026-02-09 23:26:00',1200,76.00,1,'graded'),(30,9,40,1,'2026-02-09 23:10:00','2026-02-09 23:32:00',1320,68.00,1,'graded'),(31,9,41,1,'2026-02-09 23:03:00','2026-02-09 23:21:00',1080,84.00,1,'graded'),(32,9,42,1,'2026-02-09 23:07:00','2026-02-09 23:28:00',1260,60.00,1,'graded'),(33,9,44,1,'2026-02-09 23:09:00','2026-02-09 23:24:00',900,96.00,1,'graded'),(34,11,37,1,'2026-02-02 23:05:00','2026-02-02 23:26:00',1260,90.00,1,'graded'),(35,11,38,1,'2026-02-02 23:08:00','2026-02-02 23:30:00',1320,86.00,1,'graded'),(36,11,39,1,'2026-02-02 23:06:00','2026-02-02 23:27:00',1260,74.00,1,'graded'),(37,11,43,1,'2026-02-02 23:10:00','2026-02-02 23:32:00',1320,80.00,1,'graded'),(38,11,52,1,'2026-02-02 23:03:00','2026-02-02 23:24:00',1260,58.00,0,'graded'),(39,11,53,1,'2026-02-02 23:07:00','2026-02-02 23:25:00',1080,92.00,1,'graded'),(40,13,47,1,'2026-01-27 23:05:00','2026-01-27 23:22:00',1020,94.00,1,'graded'),(41,13,48,1,'2026-01-27 23:08:00','2026-01-27 23:25:00',1020,98.00,1,'graded'),(42,13,49,1,'2026-01-27 23:06:00','2026-01-27 23:26:00',1200,80.00,1,'graded'),(43,13,50,1,'2026-01-27 23:10:00','2026-01-27 23:30:00',1200,72.00,1,'graded'),(44,13,51,1,'2026-01-27 23:03:00','2026-01-27 23:20:00',1020,88.00,1,'graded'),(45,15,61,1,'2026-01-27 23:05:00','2026-01-27 23:22:00',1020,90.00,1,'graded'),(46,15,62,1,'2026-01-27 23:08:00','2026-01-27 23:25:00',1020,86.00,1,'graded'),(47,15,63,1,'2026-01-27 23:06:00','2026-01-27 23:24:00',1080,78.00,1,'graded'),(48,15,65,1,'2026-01-27 23:10:00','2026-01-27 23:28:00',1080,64.00,1,'graded'),(49,18,61,1,'2026-02-05 23:05:00','2026-02-05 23:22:00',1020,88.00,1,'graded'),(50,18,73,1,'2026-02-05 23:08:00','2026-02-05 23:25:00',1020,92.00,1,'graded'),(51,18,83,1,'2026-02-05 23:06:00','2026-02-05 23:26:00',1200,76.00,1,'graded'),(52,18,90,1,'2026-02-05 23:10:00','2026-02-05 23:30:00',1200,60.00,1,'graded');
/*!40000 ALTER TABLE `quiz_attempts` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_quiz_attempts_after_insert` AFTER INSERT ON `quiz_attempts` FOR EACH ROW BEGIN

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

  VALUES (NEW.student_id, 'quiz_attempt_started', 'quiz_attempts', NEW.id,

          JSON_OBJECT('quiz_id', NEW.quiz_id, 'attempt_number', NEW.attempt_number));

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_quiz_attempts_after_update` AFTER UPDATE ON `quiz_attempts` FOR EACH ROW BEGIN

  IF OLD.status <> NEW.status AND NEW.status = 'submitted' THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NEW.student_id, 'quiz_attempt_submitted', 'quiz_attempts', NEW.id,

            JSON_OBJECT('quiz_id', NEW.quiz_id, 'score', NEW.score,

                        'is_passed', NEW.is_passed));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quiz_id` int(10) unsigned NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') NOT NULL DEFAULT 'multiple_choice',
  `question_text` text NOT NULL,
  `choices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of choice strings for MC/TF' CHECK (json_valid(`choices`)),
  `correct_answer` varchar(500) DEFAULT NULL COMMENT 'Index (MC/TF) or text (short_answer)',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 1,
  `order_num` smallint(5) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_qq_quiz` (`quiz_id`),
  CONSTRAINT `fk_qq_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_questions`
--

LOCK TABLES `quiz_questions` WRITE;
/*!40000 ALTER TABLE `quiz_questions` DISABLE KEYS */;
INSERT INTO `quiz_questions` VALUES (1,1,'true_false','adbakhdand','[\"\",\"\",\"\",\"\"]','0',1.00,1,1,'2026-05-08 10:57:24'),(2,2,'true_false','adbakhdand','[\"\",\"\",\"\",\"\"]','0',1.00,1,1,'2026-05-08 10:57:31'),(3,3,'multiple_choice','q','[\"qq\",\"q\",\"qqq\",\"qqqq\"]','0',1.00,1,1,'2026-05-11 07:12:45'),(4,3,'true_false','qqq','[\"\",\"\",\"\",\"\"]','0',1.00,2,2,'2026-05-11 07:12:45'),(5,3,'short_answer','qqq','[\"\",\"\",\"\",\"\"]','0',1.00,3,3,'2026-05-11 07:12:45'),(6,4,'true_false','a','[\"\",\"\",\"\",\"\"]','0',1.00,1,1,'2026-05-11 07:14:34'),(7,4,'multiple_choice','a','[\"a\",\"a\",\"a\",\"a\"]','0',1.00,2,2,'2026-05-11 07:14:34'),(8,4,'short_answer','a','[\"\",\"\",\"\",\"\"]','0',1.00,3,3,'2026-05-11 07:14:34'),(9,5,'multiple_choice','1','[\"1\",\"1\",\"1\",\"1\"]','0',1.00,1,1,'2026-05-11 07:39:05'),(10,5,'true_false','1','[\"\",\"\",\"\",\"\"]','0',1.00,2,2,'2026-05-11 07:39:05'),(11,5,'short_answer','1','[\"\",\"\",\"\",\"\"]','0',1.00,3,3,'2026-05-11 07:39:05'),(12,6,'multiple_choice','Q','[\"Q\",\"Q\",\"Q\",\"Q\"]','0',1.00,1,1,'2026-05-11 07:51:40'),(13,6,'true_false','Q','[\"\",\"\",\"\",\"\"]','0',1.00,2,2,'2026-05-11 07:51:40'),(14,6,'short_answer','Q','[\"\",\"\",\"\",\"\"]','0',1.00,3,3,'2026-05-11 07:51:40'),(15,7,'true_false','s','[\"\",\"\",\"\",\"\"]','0',1.00,1,1,'2026-05-11 07:58:34'),(16,8,'true_false','s','[\"\",\"\",\"\",\"\"]','0',1.00,1,1,'2026-05-11 08:05:16'),(17,9,'multiple_choice','Which keyword is used to create an object in Java?','[\"new\",\"create\",\"object\",\"instantiate\"]','0',2.00,1,1,'2026-02-09 00:00:00'),(18,9,'true_false','Encapsulation is achieved by making class attributes public.','[\"True\",\"False\"]','1',2.00,2,2,'2026-02-09 00:00:00'),(19,9,'multiple_choice','Which access modifier restricts access to the same class only?','[\"public\",\"protected\",\"private\",\"default\"]','2',2.00,3,3,'2026-02-09 00:00:00'),(20,9,'true_false','A constructor must always have a return type.','[\"True\",\"False\"]','1',2.00,4,4,'2026-02-09 00:00:00'),(21,9,'short_answer','What is the purpose of the \"this\" keyword in Java?','[]','Refers to the current object instance',2.00,5,5,'2026-02-09 00:00:00'),(22,11,'multiple_choice','Which HTML5 tag is used for navigation links?','[\"<nav>\",\"<link>\",\"<menu>\",\"<header>\"]','0',2.00,1,1,'2026-02-02 00:00:00'),(23,11,'true_false','The CSS specificity of an id selector is higher than a class.','[\"True\",\"False\"]','0',2.00,2,2,'2026-02-02 00:00:00'),(24,11,'multiple_choice','Which CSS property sets the space INSIDE an element border?','[\"margin\",\"padding\",\"border\",\"spacing\"]','1',2.00,3,3,'2026-02-02 00:00:00'),(25,11,'short_answer','What does CSS stand for?','[]','Cascading Style Sheets',2.00,4,4,'2026-02-02 00:00:00'),(26,13,'multiple_choice','What is the time complexity of accessing an element in an array by index?','[\"O(n)\",\"O(log n)\",\"O(1)\",\"O(n^2)\"]','2',2.00,1,1,'2026-01-27 00:00:00'),(27,13,'true_false','A linked list allows O(1) random access by index.','[\"True\",\"False\"]','1',2.00,2,2,'2026-01-27 00:00:00'),(28,13,'multiple_choice','Which pointer does the last node in a singly linked list point to?','[\"head\",\"null\",\"tail\",\"itself\"]','1',2.00,3,3,'2026-01-27 00:00:00'),(29,13,'short_answer','What is the advantage of a doubly linked list over a singly linked list?','[]','Traversal in both directions',2.00,4,4,'2026-01-27 00:00:00'),(30,15,'multiple_choice','Which OSI layer is responsible for end-to-end communication?','[\"Network\",\"Transport\",\"Session\",\"Application\"]','1',2.00,1,1,'2026-01-27 00:00:00'),(31,15,'multiple_choice','HTTP operates at which OSI layer?','[\"Layer 3\",\"Layer 4\",\"Layer 7\",\"Layer 2\"]','2',2.00,2,2,'2026-01-27 00:00:00'),(32,15,'true_false','The Physical layer converts data to binary signals.','[\"True\",\"False\"]','0',2.00,3,3,'2026-01-27 00:00:00'),(33,15,'short_answer','What protocol is used at the Data Link layer for local network addressing?','[]','MAC (Media Access Control)',2.00,4,4,'2026-01-27 00:00:00'),(34,18,'multiple_choice','Which SQL command is used to retrieve data from a table?','[\"INSERT\",\"SELECT\",\"UPDATE\",\"DELETE\"]','1',2.00,1,1,'2026-02-05 00:00:00'),(35,18,'true_false','A PRIMARY KEY can contain NULL values.','[\"True\",\"False\"]','1',2.00,2,2,'2026-02-05 00:00:00'),(36,18,'multiple_choice','Which clause is used to filter rows in a SELECT statement?','[\"ORDER BY\",\"GROUP BY\",\"WHERE\",\"HAVING\"]','2',2.00,3,3,'2026-02-05 00:00:00'),(37,18,'short_answer','What SQL statement is used to remove a table and all its data permanently?','[]','DROP TABLE',2.00,4,4,'2026-02-05 00:00:00'),(38,9,'true_false','hi','[\"True\",\"False\"]','1',1.00,1,1,'2026-05-15 23:45:04'),(39,10,'short_answer','a','[]','a',1.00,1,1,'2026-05-15 23:54:59'),(40,10,'true_false','a','[\"True\",\"False\"]','0',1.00,2,1,'2026-05-15 23:54:59');
/*!40000 ALTER TABLE `quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quizzes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `quiz_type` enum('quiz','midterm','final','activity') NOT NULL DEFAULT 'quiz',
  `time_limit_min` smallint(5) unsigned DEFAULT NULL,
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
  `passing_score` decimal(6,2) NOT NULL DEFAULT 60.00,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 0,
  `shuffle_choices` tinyint(1) NOT NULL DEFAULT 0,
  `show_answers_after` tinyint(1) NOT NULL DEFAULT 1,
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quiz_section` (`section_id`),
  CONSTRAINT `fk_quiz_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizzes`
--

LOCK TABLES `quizzes` WRITE;
/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
INSERT INTO `quizzes` VALUES (1,1,'asndafjmdlmas',NULL,NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'draft','2026-05-08 10:57:24','2026-05-08 10:57:24'),(2,1,'asndafjmdlmas',NULL,NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'published','2026-05-08 10:57:31','2026-05-08 10:57:31'),(3,4,'quiz 2',NULL,NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'published','2026-05-11 07:12:45','2026-05-11 07:12:45'),(4,1,'quiz 2',NULL,NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'published','2026-05-11 07:14:34','2026-05-11 07:14:34'),(5,1,'quiz 2',NULL,NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'published','2026-05-11 07:39:05','2026-05-11 07:39:05'),(6,1,'TESTING',NULL,NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'published','2026-05-11 07:51:40','2026-05-11 07:51:40'),(7,1,'test',NULL,NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'published','2026-05-11 07:58:34','2026-05-11 07:58:34'),(8,1,'test',NULL,NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'published','2026-05-11 08:05:16','2026-05-11 08:05:16'),(9,2,'test 2',NULL,NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'published','2026-05-15 23:45:04','2026-05-15 23:45:04'),(10,2,'lala','html',NULL,NULL,'quiz',10,1,100.00,60.00,0,0,1,NULL,NULL,'published','2026-05-15 23:54:59','2026-05-15 23:54:59');
/*!40000 ALTER TABLE `quizzes` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_quizzes_after_insert` AFTER INSERT ON `quizzes` FOR EACH ROW BEGIN

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

  VALUES (NULL, 'quiz_created', 'quizzes', NEW.id,

          JSON_OBJECT('section_id', NEW.section_id, 'title', NEW.title));

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_quizzes_after_update` AFTER UPDATE ON `quizzes` FOR EACH ROW BEGIN

  IF OLD.status <> NEW.status THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NULL, 'quiz_status_changed', 'quizzes', NEW.id,

            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `student_grades`
--

DROP TABLE IF EXISTS `student_grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_grades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(10) unsigned NOT NULL,
  `component_id` int(10) unsigned NOT NULL,
  `computed_score` decimal(5,2) DEFAULT NULL,
  `override_score` decimal(5,2) DEFAULT NULL,
  `override_note` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sg` (`enrollment_id`,`component_id`),
  KEY `idx_sg_component` (`component_id`),
  CONSTRAINT `fk_sg_component` FOREIGN KEY (`component_id`) REFERENCES `grade_components` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sg_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_grades`
--

LOCK TABLES `student_grades` WRITE;
/*!40000 ALTER TABLE `student_grades` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_grades` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_student_grades_after_update` AFTER UPDATE ON `student_grades` FOR EACH ROW BEGIN

  IF (OLD.override_score IS NULL AND NEW.override_score IS NOT NULL)

     OR (OLD.override_score <> NEW.override_score) THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NULL, 'grade_override', 'student_grades', NEW.id,

            JSON_OBJECT('enrollment_id', NEW.enrollment_id,

                        'component_id',  NEW.component_id,

                        'old_override',  OLD.override_score,

                        'new_override',  NEW.override_score,

                        'note',          NEW.override_note));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profiles` (
  `user_id` int(10) unsigned NOT NULL,
  `student_id` varchar(30) NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_level` tinyint(3) unsigned DEFAULT NULL,
  `section` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_student_id` (`student_id`),
  CONSTRAINT `fk_sp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_profiles`
--

LOCK TABLES `student_profiles` WRITE;
/*!40000 ALTER TABLE `student_profiles` DISABLE KEYS */;
INSERT INTO `student_profiles` VALUES (37,'2024-00037','BSIT-TP',2,'BSIT-2A'),(38,'2024-00038','BSIT-TP',2,'BSIT-2A'),(39,'2024-00039','BSIT-TP',2,'BSIT-2A'),(40,'2024-00040','BSIT-TP',2,'BSIT-2A'),(41,'2024-00041','BSIT-TP',2,'BSIT-2A'),(42,'2024-00042','BSIT-TP',2,'BSIT-2A'),(43,'2024-00043','BSIT-TP',2,'BSIT-2B'),(44,'2024-00044','BSIT-TP',2,'BSIT-2B'),(45,'2024-00045','BSIT-TP',2,'BSIT-2B'),(46,'2024-00046','BSCS-ST',2,'BSCS-2A'),(47,'2024-00047','BSCS-ST',2,'BSCS-2A'),(48,'2024-00048','BSCS-ST',2,'BSCS-2A'),(49,'2024-00049','BSCS-ST',2,'BSCS-2A'),(50,'2024-00050','BSCS-ST',2,'BSCS-2A'),(51,'2024-00051','BSCS-ST',2,'BSCS-2A'),(52,'2024-00052','BSIT-TP',2,'BSIT-2B'),(53,'2024-00053','BSIT-TP',2,'BSIT-2B'),(54,'2024-00054','BSIT-TP',2,'BSIT-2B'),(55,'2024-00055','BSCS-ST',4,'BSIT-4A'),(56,'2024-00056','BSCS-ST',4,'BSIT-4A'),(61,'2024-00061','BSIT-TP',3,'BSIT-3A'),(62,'2024-00062','BSIT-TP',3,'BSIT-3A'),(63,'2024-00063','BSIT-TP',3,'BSIT-3A'),(64,'2024-00064','BSCS-ST',3,'BSCS-3A'),(65,'2024-00065','BSCS-ST',3,'BSCS-3A'),(66,'2024-00066','BSCS-ST',1,'BSCS-1A'),(67,'2024-00067','BSIT-TP',1,'BSIT-1A'),(68,'2024-00068','BSIT-TP',1,'BSIT-1A'),(69,'2024-00069','BSCS-ST',2,'BSCS-2B'),(70,'2024-00070','BSCS-ST',2,'BSCS-2B'),(71,'2024-00071','BSIT-TP',2,'BSIT-2C'),(72,'2024-00072','BSIT-TP',2,'BSIT-2C'),(73,'2024-00073','BSCS-ST',3,'BSCS-3B'),(74,'2024-00074','BSCS-ST',3,'BSCS-3B'),(75,'2024-00075','BSIT-TP',4,'BSIT-4B'),(76,'2024-00076','BSIT-TP',4,'BSIT-4B'),(77,'2024-00077','BSCS-ST',4,'BSCS-4A'),(78,'2024-00078','BSCS-ST',4,'BSCS-4A'),(79,'2024-00079','BSIT-TP',1,'BSIT-1B'),(80,'2024-00080','BSIT-TP',1,'BSIT-1B'),(81,'2024-00081','BSCS-ST',2,'BSCS-2C'),(82,'2024-00082','BSCS-ST',2,'BSCS-2C'),(83,'2024-00083','BSIT-TP',3,'BSIT-3B'),(84,'2024-00084','BSIT-TP',3,'BSIT-3B'),(85,'2024-00085','BSCS-ST',2,'BSCS-2A'),(86,'2024-00086','BSCS-ST',2,'BSCS-2A'),(87,'2024-00087','BSIT-TP',2,'BSIT-2A'),(88,'2024-00088','BSIT-TP',2,'BSIT-2B'),(89,'2024-00089','BSCS-ST',3,'BSCS-3A'),(90,'2024-00090','BSIT-TP',3,'BSIT-3A'),(91,'2024-00091','BSCS-ST',4,'BSCS-4A'),(92,'2024-00092','BSIT-TP',4,'BSIT-4A'),(93,'2024-00093','BSCS-ST',1,'BSCS-1A'),(94,'2024-00094','BSIT-TP',1,'BSIT-1A'),(95,'2024-00095','BSCS-ST',3,'BSCS-3B'),(96,'2024-00096','BSIT-TP',3,'BSIT-3B');
/*!40000 ALTER TABLE `student_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submission_files`
--

DROP TABLE IF EXISTS `submission_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submission_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` int(10) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_size_kb` int(10) unsigned DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sf_submission` (`submission_id`),
  CONSTRAINT `fk_sf_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submission_files`
--

LOCK TABLES `submission_files` WRITE;
/*!40000 ALTER TABLE `submission_files` DISABLE KEYS */;
INSERT INTO `submission_files` VALUES (1,1,'Lab5_Gabriel.zip','uploads/submissions/Lab5_Gabriel.zip',240,'2026-05-08 10:54:34'),(2,2,'Lab5_Sarmiento.zip','uploads/submissions/Lab5_Sarmiento.zip',218,'2026-05-08 10:54:34'),(3,3,'Lab5_Abalos.zip','uploads/submissions/Lab5_Abalos.zip',195,'2026-05-08 10:54:34'),(4,4,'Lab5_Antipolo.zip','uploads/submissions/Lab5_Antipolo.zip',187,'2026-05-08 10:54:34'),(5,5,'Lab5_Cruz.zip','uploads/submissions/Lab5_Cruz.zip',210,'2026-05-08 10:54:34'),(6,6,'Project2_Gabriel.zip','uploads/submissions/Project2_Gabriel.zip',1840,'2026-05-08 10:54:34'),(7,7,'Project2_Sarmiento.zip','uploads/submissions/Project2_Sarmiento.zip',1620,'2026-05-08 10:54:34'),(8,8,'Project2_Abalos.zip','uploads/submissions/Project2_Abalos.zip',1755,'2026-05-08 10:54:34'),(9,9,'Project2_Bello.zip','uploads/submissions/Project2_Bello.zip',1480,'2026-05-08 10:54:34'),(10,10,'Project2_Aguilar.zip','uploads/submissions/Project2_Aguilar.zip',1510,'2026-05-08 10:54:34'),(11,11,'PS3_Cruz.pdf','uploads/submissions/PS3_Cruz.pdf',320,'2026-05-08 10:54:34'),(12,12,'PS3_Bautista.pdf','uploads/submissions/PS3_Bautista.pdf',298,'2026-05-08 10:54:34'),(13,13,'PS3_Park.pdf','uploads/submissions/PS3_Park.pdf',345,'2026-05-08 10:54:34'),(14,14,'PS3_Santos.pdf','uploads/submissions/PS3_Santos.pdf',310,'2026-05-08 10:54:34'),(15,16,'Capstone_Cruz.docx','uploads/submissions/Capstone_Cruz.docx',890,'2026-05-08 10:54:34'),(16,17,'Capstone_Reyes.docx','uploads/submissions/Capstone_Reyes.docx',920,'2026-05-08 10:54:34'),(17,19,'Lab4_Abalos.zip','uploads/submissions/Lab4_Abalos.zip',188,'2026-05-08 10:54:34'),(18,20,'Lab4_Antipolo.zip','uploads/submissions/Lab4_Antipolo.zip',175,'2026-05-08 10:54:34'),(19,21,'Lab4_Cruz.zip','uploads/submissions/Lab4_Cruz.zip',192,'2026-05-08 10:54:34'),(20,22,'Lab4_Santos.zip','uploads/submissions/Lab4_Santos.zip',180,'2026-05-08 10:54:34');
/*!40000 ALTER TABLE `submission_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `content` longtext DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `score` decimal(6,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_by` int(10) unsigned DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `status` enum('submitted','graded','returned','resubmitted') NOT NULL DEFAULT 'submitted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_submission` (`assignment_id`,`student_id`),
  KEY `idx_sub_student` (`student_id`),
  KEY `idx_sub_graded` (`graded_by`),
  CONSTRAINT `fk_sub_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sub_grader` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
INSERT INTO `submissions` VALUES (1,1,37,'Submitted via file upload','2026-05-09 06:15:00',0,NULL,NULL,NULL,NULL,'submitted'),(2,1,38,'Submitted via file upload','2026-05-08 14:00:00',0,96.00,'Excellent exception handling and clean code structure.',36,'2026-05-09 02:00:00','graded'),(3,1,39,'Submitted via file upload','2026-05-09 00:30:00',0,NULL,NULL,NULL,NULL,'submitted'),(4,1,40,'Submitted via file upload','2026-05-11 01:00:00',1,72.00,'Late submission. Good effort but missed multi-catch blocks.',36,'2026-05-11 06:00:00','graded'),(5,1,42,'Submitted via file upload','2026-05-09 05:00:00',0,89.00,'Well-structured. Minor issue with finally block logic.',36,'2026-05-10 01:00:00','graded'),(6,2,37,'Submitted via file upload','2026-04-27 15:59:00',0,92.00,'Great responsive layout. Excellent mobile breakpoints.',36,'2026-04-29 02:00:00','graded'),(7,2,38,'Submitted via file upload','2026-04-28 00:00:00',1,80.00,'Late by a few hours. Good overall design but missing contact form validation.',36,'2026-04-29 04:00:00','graded'),(8,2,39,'Submitted via file upload','2026-04-27 09:00:00',0,95.00,'Outstanding design sense. Clean and accessible.',36,'2026-04-29 03:00:00','graded'),(9,2,52,'Submitted via file upload','2026-04-27 12:00:00',0,NULL,NULL,NULL,NULL,'submitted'),(10,2,53,'Submitted via file upload','2026-04-28 03:00:00',1,NULL,NULL,NULL,NULL,'submitted'),(11,3,47,'Submitted via file upload','2026-04-24 09:00:00',0,84.00,'Correct Big-O analysis. Missed optimizing bubble sort.',36,'2026-04-26 01:00:00','graded'),(12,3,49,'Submitted via file upload','2026-04-26 01:00:00',1,70.00,'2 days late. Deducted 10pts. Solutions mostly correct.',36,'2026-04-27 00:00:00','graded'),(13,3,48,'Submitted via file upload','2026-04-24 06:00:00',0,91.00,'Perfect Big-O table. Code is clean and well-commented.',36,'2026-04-26 02:00:00','graded'),(14,3,50,'Submitted via file upload','2026-04-24 08:00:00',0,78.00,'Good work overall. Quick sort implementation had a bug.',36,'2026-04-26 03:00:00','graded'),(15,3,51,'Submitted via file upload','2026-04-24 10:00:00',0,NULL,NULL,NULL,NULL,'submitted'),(16,4,55,'Submitted via file upload','2026-05-04 07:00:00',0,NULL,NULL,NULL,NULL,'submitted'),(17,4,56,'Submitted via file upload','2026-05-04 10:00:00',0,NULL,NULL,NULL,NULL,'submitted'),(18,4,48,'Submitted via file upload','2026-05-05 02:00:00',1,NULL,NULL,NULL,NULL,'submitted'),(19,5,39,'Submitted via file upload','2026-04-22 00:00:00',0,88.00,'Good use of super(), minor issue with method overriding.',36,'2026-04-23 01:00:00','graded'),(20,5,40,'Submitted via file upload','2026-04-20 07:45:00',1,75.00,'Submitted 2 days late. Deducted 10pts.',36,'2026-04-22 02:00:00','graded'),(21,5,42,'Submitted via file upload','2026-04-18 08:00:00',0,82.00,'Good design. Abstract class could be more generic.',36,'2026-04-20 00:00:00','graded'),(22,5,43,'Submitted via file upload','2026-04-19 03:00:00',0,79.00,'Solid implementation. Polymorphism example needs refinement.',36,'2026-04-20 01:00:00','graded'),(23,4,37,'my assignments','2026-05-11 09:26:11',0,NULL,NULL,NULL,NULL,'submitted'),(24,6,37,'','2026-05-11 09:33:47',0,NULL,NULL,NULL,NULL,'submitted'),(25,6,38,'Submitted via file upload','2026-05-23 01:00:00',0,NULL,NULL,NULL,NULL,'submitted'),(26,6,41,'Submitted via file upload','2026-05-24 14:00:00',0,NULL,NULL,NULL,NULL,'submitted'),(27,7,43,'Submitted via file upload','2026-05-19 07:00:00',0,87.00,'Functional CRUD. UI could be polished further but meets all requirements.',36,'2026-05-20 02:00:00','graded'),(28,7,44,'Submitted via file upload','2026-05-19 12:00:00',0,91.00,'Excellent implementation. Clean code structure and responsive design.',36,'2026-05-20 03:00:00','graded'),(29,7,52,'Submitted via file upload','2026-05-20 15:30:00',1,78.00,'Late by 30 mins. Good effort. Minor backend validation issues.',36,'2026-05-21 01:00:00','graded'),(30,8,47,'Submitted via file upload','2026-05-14 02:00:00',0,88.00,'Good BST implementation. BFS output was correct. DFS had a minor bug in visited tracking.',36,'2026-05-15 01:00:00','graded'),(31,8,48,'Submitted via file upload','2026-05-13 06:00:00',0,95.00,'Excellent work. Graph adjacency list well-structured and all traversals correct.',36,'2026-05-15 02:00:00','graded'),(32,8,49,'Submitted via file upload','2026-05-14 14:00:00',0,82.00,'BST delete function was incomplete. BFS/DFS correct.',36,'2026-05-15 03:00:00','graded'),(33,8,51,'Submitted via file upload','2026-05-14 17:00:00',1,71.00,'Late by 2 hours. Penalty applied. Solutions mostly correct but lacking complexity analysis.',36,'2026-05-16 01:00:00','graded'),(34,9,55,'Submitted via file upload','2026-05-19 02:00:00',0,88.00,'Well-written RRL with 17 references. Synthesis could be stronger.',36,'2026-05-21 00:00:00','graded'),(35,9,56,'Submitted via file upload','2026-05-18 06:00:00',0,92.00,'Excellent literature review. References are recent and well-cited.',36,'2026-05-21 01:00:00','graded'),(36,9,48,'Submitted via file upload','2026-05-20 14:00:00',0,NULL,NULL,NULL,NULL,'submitted'),(37,10,61,'Submitted via file upload','2026-03-04 06:00:00',0,90.00,'Correct topology. Proper IP addressing and device configuration.',57,'2026-03-06 01:00:00','graded'),(38,10,62,'Submitted via file upload','2026-03-05 02:00:00',0,85.00,'Good design. DHCP configuration had a misconfigured pool range.',57,'2026-03-06 02:00:00','graded'),(39,10,63,'Submitted via file upload','2026-03-04 12:00:00',0,88.00,'Network functioned correctly. Documentation was clear.',57,'2026-03-06 03:00:00','graded'),(40,10,64,'Submitted via file upload','2026-03-05 00:00:00',0,76.00,'Topology is correct but missing VLAN segmentation as required.',57,'2026-03-06 04:00:00','graded'),(41,15,61,'Submitted via file upload','2026-03-09 07:00:00',0,92.00,'All tables created correctly. Constraints properly defined.',59,'2026-03-11 00:00:00','graded'),(42,15,73,'Submitted via file upload','2026-03-10 02:00:00',0,88.00,'Good work. One foreign key constraint was incorrect.',59,'2026-03-11 01:00:00','graded'),(43,15,83,'Submitted via file upload','2026-03-09 12:00:00',0,95.00,'Excellent. Clean SQL script with comments. All constraints correct.',59,'2026-03-11 02:00:00','graded'),(44,16,61,'Submitted via file upload','2026-04-06 06:00:00',0,NULL,NULL,NULL,NULL,'submitted'),(45,16,83,'Submitted via file upload','2026-04-07 00:00:00',0,NULL,NULL,NULL,NULL,'submitted'),(46,18,66,'Submitted via file upload','2026-03-04 06:00:00',0,45.00,'Good effort. Two hexadecimal conversions had arithmetic errors.',36,'2026-03-06 00:00:00','graded'),(47,18,67,'Submitted via file upload','2026-03-04 08:00:00',0,48.00,'Almost perfect. One binary conversion had a carry-over error.',36,'2026-03-06 01:00:00','graded'),(48,18,68,'Submitted via file upload','2026-03-05 01:00:00',0,50.00,'Perfect score. All conversions correct and clearly showed steps.',36,'2026-03-06 02:00:00','graded');
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_submissions_after_insert` AFTER INSERT ON `submissions` FOR EACH ROW BEGIN

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

  VALUES (NEW.student_id, 'submission_created', 'submissions', NEW.id,

          JSON_OBJECT('assignment_id', NEW.assignment_id, 'is_late', NEW.is_late));

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_submissions_after_update` AFTER UPDATE ON `submissions` FOR EACH ROW BEGIN

  IF OLD.status <> NEW.status THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NEW.graded_by, 'submission_graded', 'submissions', NEW.id,

            JSON_OBJECT('student_id', NEW.student_id, 'score', NEW.score,

                        'old_status', OLD.status, 'new_status', NEW.status));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_profiles` (
  `user_id` int(10) unsigned NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `display_name` varchar(180) GENERATED ALWAYS AS (concat(`first_name`,' ',`last_name`)) STORED,
  `phone` varchar(30) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_up_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
INSERT INTO `user_profiles` VALUES (1,'LEMUEL','DURAN',NULL,'LEMUEL DURAN',NULL,NULL,NULL),(36,'Catherine','Santos',NULL,'Catherine Santos',NULL,'uploads/avatars/avatar_36_1778497754.jpg',NULL),(37,'Ryza Marie','Gabriel',NULL,'Ryza Marie Gabriel',NULL,NULL,'Dean\'s list student passionate about software engineering.'),(38,'Aricelle','Sarmiento',NULL,'Aricelle Sarmiento',NULL,NULL,'Web developer enthusiast and front-end hobbyist.'),(39,'Nicole','Abalos',NULL,'Nicole Abalos',NULL,NULL,'Aspiring software developer with a love for clean code.'),(40,'Micah Lorraine','Antipolo',NULL,'Micah Lorraine Antipolo',NULL,NULL,'Interested in AI and machine learning.'),(41,'Win Heart','Ordaniel',NULL,'Win Heart Ordaniel',NULL,NULL,'Loves game development and creative coding.'),(42,'Juan','Dela Cruz',NULL,'Juan Dela Cruz',NULL,NULL,'Passionate about back-end development and databases.'),(43,'Maria','Santos',NULL,'Maria Santos',NULL,NULL,'UI/UX enthusiast and graphic design hobbyist.'),(44,'Carlo','Bautista',NULL,'Carlo Bautista',NULL,NULL,'Network engineering and cybersecurity student.'),(45,'Ana','Reyes',NULL,'Ana Reyes',NULL,NULL,'Robotics and IoT enthusiast.'),(46,'Mark','Torres',NULL,'Mark Torres',NULL,NULL,'Enjoys competitive programming and algorithms.'),(47,'Rico','Cruz',NULL,'Rico Cruz',NULL,NULL,'Algorithm enthusiast and coding contest participant.'),(48,'Lena','Park',NULL,'Lena Park',NULL,NULL,'Strong interest in data science and analytics.'),(49,'Mario','Bautista',NULL,'Mario Bautista',NULL,NULL,'Enjoys full-stack web development projects.'),(50,'Karl','Santos',NULL,'Karl Santos',NULL,NULL,'Passionate about system architecture and DevOps.'),(51,'Ana','Castro',NULL,'Ana Castro',NULL,NULL,'Mobile app developer with React Native experience.'),(52,'Wren','Bello',NULL,'Wren Bello',NULL,NULL,'Creative coder interested in digital media.'),(53,'Yvonne','Aguilar',NULL,'Yvonne Aguilar',NULL,NULL,'Focuses on responsive design and accessibility.'),(54,'Arlo','Navarro',NULL,'Arlo Navarro',NULL,NULL,'Interested in cloud computing and microservices.'),(55,'Abby','Cruz',NULL,'Abby Cruz',NULL,NULL,'Capstone researcher studying e-health platforms.'),(56,'Bruno','Reyes',NULL,'Bruno Reyes',NULL,NULL,'Innovator in fintech and mobile banking solutions.'),(57,'Mark','Delos Reyes','Antonio','Mark Delos Reyes','09171234567',NULL,'Associate Professor in Software Engineering with 10 years of industry experience.'),(58,'Grace','Lim','Marie','Grace Lim','09181234568',NULL,'Full-stack developer turned educator. Passionate about front-end frameworks and UI/UX.'),(59,'Julius','Ramos','Cruz','Julius Ramos','09191234569',NULL,'Database specialist and backend developer. Teaches advanced database and system design.'),(60,'Rose','Villanueva','Dizon','Rose Villanueva','09201234570',NULL,'Research-oriented faculty member specializing in capstone mentoring and thesis writing.'),(61,'Josh','Dela Pena','Miguel','Josh Dela Pena',NULL,NULL,'BSIT junior. Interested in game development and mobile apps.'),(62,'Claire','Salazar','Ann','Claire Salazar',NULL,NULL,'Aspiring front-end developer. Loves React and design systems.'),(63,'Ryan','Mendoza','Gabriel','Ryan Mendoza',NULL,NULL,'Backend-focused student. Enjoys building REST APIs with Laravel.'),(64,'Paula','Hernandez','Grace','Paula Hernandez',NULL,NULL,'Data enthusiast. Exploring machine learning and data visualization.'),(65,'Felix','Ramos','Jose','Felix Ramos',NULL,NULL,'Cybersecurity student passionate about ethical hacking and network security.'),(66,'Lea','Garcia','Santos','Lea Garcia',NULL,NULL,'Creative coder with a flair for UI design and Figma prototyping.'),(67,'Lance','Aquino','Paul','Lance Aquino',NULL,NULL,'Systems programmer who enjoys low-level computing and OS internals.'),(68,'Liza','Miranda','Rose','Liza Miranda',NULL,NULL,'Aspiring project manager. Combines IT skills with communication and teamwork.'),(69,'Ivan','Austria','Luis','Ivan Austria',NULL,NULL,'Algorithm geek and competitive programmer. Regular contestant in regional hackathons.'),(70,'Diana','Rojas','Mae','Diana Rojas',NULL,NULL,'Mobile developer experimenting with Flutter and cross-platform apps.'),(71,'Carl','Navarro','James','Carl Navarro',NULL,NULL,'Network engineer aspirant. Interested in Cisco and cloud networking.'),(72,'Faye','Padilla','Christine','Faye Padilla',NULL,NULL,'Web developer with strong CSS and animation skills.'),(73,'Leo','Santos','Arthur','Leo Santos',NULL,NULL,'Enjoys DevOps and container technologies like Docker and Kubernetes.'),(74,'Nina','Enriquez','Victoria','Nina Enriquez',NULL,NULL,'Data analytics student with SQL and Power BI skills.'),(75,'Edgar','Morales','Santiago','Edgar Morales',NULL,NULL,'Embedded systems hobbyist. Works on Arduino and Raspberry Pi projects.'),(76,'Anna','Flores','Maria','Anna Flores',NULL,NULL,'Interested in UX research and human-computer interaction.'),(77,'Joseph','Reyes','David','Joseph Reyes',NULL,NULL,'Full-stack developer in training. Loves MERN stack projects.'),(78,'Trisha','Guerrero','Lyn','Trisha Guerrero',NULL,NULL,'Cloud computing enthusiast pursuing AWS certifications.'),(79,'Kevin','Valdez','Patrick','Kevin Valdez',NULL,NULL,'Blockchain researcher exploring decentralized applications.'),(80,'Joanna','Ocampo','Faith','Joanna Ocampo',NULL,NULL,'AI enthusiast focusing on natural language processing.'),(81,'Daniel','Dela Cruz','Emmanuel','Daniel Dela Cruz',NULL,NULL,'Software tester interested in automation with Selenium and Cypress.'),(82,'Camille','Bernardo','Rose','Camille Bernardo',NULL,NULL,'Graphic design and UI/UX student bridging art and technology.'),(83,'Alvin','Espiritu','Mark','Alvin Espiritu',NULL,NULL,'Linux power user and open-source contributor.'),(84,'Rina','Santiago','Claire','Rina Santiago',NULL,NULL,'Interested in fintech and mobile payment systems.'),(85,'Ben','Chua','Eric','Ben Chua',NULL,NULL,'Math-oriented programmer. Strong in discrete math and algorithms.'),(86,'Mia','Tan','Joy','Mia Tan',NULL,NULL,'Healthcare informatics student combining IT and medical knowledge.'),(87,'Peter','Lim','John','Peter Lim',NULL,NULL,'IoT developer working on smart home and automation projects.'),(88,'Patricia','Go','Anne','Patricia Go',NULL,NULL,'E-commerce developer with Shopify and WooCommerce experience.'),(89,'Dennis','Uy','Rafael','Dennis Uy',NULL,NULL,'Cybersecurity and digital forensics enthusiast.'),(90,'Rachel','Sy','Marie','Rachel Sy',NULL,NULL,'Frontend developer passionate about accessibility and web standards.'),(91,'Harold','Tiu','James','Harold Tiu',NULL,NULL,'Game developer using Unity for 2D and 3D projects.'),(92,'Carla','Ong','Frances','Carla Ong',NULL,NULL,'Data science student learning Python and pandas for analytics.'),(93,'Victor','Kho','George','Victor Kho',NULL,NULL,'Systems analyst with strong business process modeling skills.'),(94,'Sheila','Chan','Luz','Sheila Chan',NULL,NULL,'Software project coordinator learning agile and Scrum methodologies.'),(95,'Jerome','Ang','Michael','Jerome Ang',NULL,NULL,'Network security researcher studying penetration testing frameworks.'),(96,'Elaine','Yap','Grace','Elaine Yap',NULL,NULL,'Digital marketing and IT student exploring growth hacking and analytics.');
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `payload` text DEFAULT NULL COMMENT 'JSON: user, role, email, dept, instructor_name, etc.',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session_token` (`session_token`),
  KEY `idx_us_user` (`user_id`),
  KEY `idx_us_expires` (`expires_at`),
  CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','instructor','student') NOT NULL,
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `theme_pref` enum('light','dark','system') NOT NULL DEFAULT 'system',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'duran_lemuel@plpasig.edu.ph','$2y$10$Y...','admin','active',1,'system','2026-05-11 21:24:27','2026-05-04 10:40:21','2026-05-11 21:24:27'),(36,'santos_cath@plpasig.edu.ph','$2y$10$placeholder_hash_replace_me_000000000000000000000000000','instructor','active',1,'system','2026-05-15 23:54:33','2026-05-07 09:04:06','2026-05-15 23:54:33'),(37,'gabriel_ryza@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000037','student','active',1,'system','2026-05-15 23:55:11','2026-04-30 16:00:00','2026-05-15 23:55:11'),(38,'sarmiento_aric@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000038','student','active',1,'system','2026-05-07 00:05:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(39,'abalos_nicole@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000039','student','active',1,'system','2026-05-06 06:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(40,'antipolo_micah@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000040','student','active',1,'system','2026-05-05 01:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(41,'ordaniel_win@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000041','student','active',1,'system','2026-05-04 02:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(42,'delacruz_juan@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000042','student','active',1,'system','2026-05-06 23:30:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(43,'santos_maria@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000043','student','active',1,'system','2026-05-06 03:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(44,'bautista_carlo@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000044','student','active',1,'system','2026-05-06 01:30:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(45,'reyes_ana@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000045','student','active',1,'system','2026-05-05 05:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(46,'torres_mark@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000046','student','active',1,'system','2026-05-05 07:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(47,'cruz_rico@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000047','student','active',1,'system','2026-05-06 22:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(48,'park_lena@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000048','student','active',1,'system','2026-05-06 23:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(49,'bautista_mario@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000049','student','active',1,'system','2026-05-04 04:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(50,'santos_karl@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000050','student','active',1,'system','2026-05-06 00:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(51,'castro_ana@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000051','student','active',1,'system','2026-05-06 02:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(52,'bello_wren@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000052','student','active',1,'system','2026-05-05 08:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(53,'aguilar_yvonne@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000053','student','active',1,'system','2026-05-05 06:30:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(54,'navarro_arlo@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000054','student','active',1,'system','2026-05-06 04:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(55,'cruz_abby@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000055','student','active',1,'system','2026-05-07 01:00:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(56,'reyes_bruno@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000056','student','active',1,'system','2026-05-06 03:30:00','2026-04-30 16:00:00','2026-04-30 16:00:00'),(57,'delos_reyes_mark@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000057','instructor','active',1,'system','2026-05-10 00:00:00','2026-05-01 00:00:00','2026-05-10 00:00:00'),(58,'lim_grace@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000058','instructor','active',1,'light','2026-05-10 23:30:00','2026-05-01 00:00:00','2026-05-10 23:30:00'),(59,'ramos_julius@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000059','instructor','active',1,'dark','2026-05-09 02:00:00','2026-05-01 00:00:00','2026-05-09 02:00:00'),(60,'villanueva_rose@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000060','instructor','active',1,'system','2026-05-08 01:00:00','2026-05-01 00:00:00','2026-05-08 01:00:00'),(61,'dela_pena_josh@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000061','student','active',1,'system','2026-05-09 23:00:00','2026-01-08 00:00:00','2026-05-09 23:00:00'),(62,'salazar_claire@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000062','student','active',1,'light','2026-05-09 00:00:00','2026-01-08 00:00:00','2026-05-09 00:00:00'),(63,'mendoza_ryan@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000063','student','active',1,'system','2026-05-07 22:30:00','2026-01-08 00:00:00','2026-05-07 22:30:00'),(64,'hernandez_paula@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000064','student','active',1,'dark','2026-05-07 01:00:00','2026-01-08 00:00:00','2026-05-07 01:00:00'),(65,'ramos_felix@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000065','student','active',1,'system','2026-05-10 21:00:00','2026-01-08 00:00:00','2026-05-10 21:00:00'),(66,'garcia_lea@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000066','student','active',1,'system','2026-05-10 02:00:00','2026-01-08 00:00:00','2026-05-10 02:00:00'),(67,'aquino_lance@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000067','student','active',1,'light','2026-05-09 03:00:00','2026-01-08 00:00:00','2026-05-09 03:00:00'),(68,'miranda_liza@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000068','student','active',1,'system','2026-05-07 23:30:00','2026-01-08 00:00:00','2026-05-07 23:30:00'),(69,'austria_ivan@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000069','student','active',1,'system','2026-05-07 00:00:00','2026-01-08 00:00:00','2026-05-07 00:00:00'),(70,'rojas_diana@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000070','student','active',1,'dark','2026-05-06 01:00:00','2026-01-08 00:00:00','2026-05-06 01:00:00'),(71,'navarro_carl@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000071','student','active',1,'system','2026-05-05 02:00:00','2026-01-08 00:00:00','2026-05-05 02:00:00'),(72,'padilla_faye@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000072','student','active',1,'light','2026-05-03 23:00:00','2026-01-08 00:00:00','2026-05-03 23:00:00'),(73,'santos_leo@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000073','student','active',1,'system','2026-05-03 00:00:00','2026-01-08 00:00:00','2026-05-03 00:00:00'),(74,'enriquez_nina@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000074','student','active',1,'system','2026-05-02 01:00:00','2026-01-08 00:00:00','2026-05-02 01:00:00'),(75,'morales_edgar@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000075','student','active',1,'dark','2026-05-01 02:00:00','2026-01-08 00:00:00','2026-05-01 02:00:00'),(76,'flores_anna@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000076','student','active',1,'system','2026-04-30 00:00:00','2026-01-08 00:00:00','2026-04-30 00:00:00'),(77,'reyes_joseph@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000077','student','active',1,'system','2026-04-29 01:00:00','2026-01-08 00:00:00','2026-04-29 01:00:00'),(78,'guerrero_trisha@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000078','student','active',1,'light','2026-04-28 02:00:00','2026-01-08 00:00:00','2026-04-28 02:00:00'),(79,'valdez_kevin@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000079','student','active',1,'system','2026-04-26 23:00:00','2026-01-08 00:00:00','2026-04-26 23:00:00'),(80,'ocampo_joanna@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000080','student','active',1,'system','2026-04-26 00:00:00','2026-01-08 00:00:00','2026-04-26 00:00:00'),(81,'dela_cruz_daniel@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000081','student','active',1,'dark','2026-05-10 03:00:00','2026-01-08 00:00:00','2026-05-10 03:00:00'),(82,'bernardo_camille@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000082','student','active',1,'system','2026-05-08 22:00:00','2026-01-08 00:00:00','2026-05-08 22:00:00'),(83,'espiritu_alvin@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000083','student','active',1,'light','2026-05-07 21:30:00','2026-01-08 00:00:00','2026-05-07 21:30:00'),(84,'santiago_rina@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000084','student','active',1,'system','2026-05-07 02:00:00','2026-01-08 00:00:00','2026-05-07 02:00:00'),(85,'chua_ben@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000085','student','active',1,'system','2026-05-06 03:00:00','2026-01-08 00:00:00','2026-05-06 03:00:00'),(86,'tan_mia@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000086','student','active',1,'dark','2026-05-05 04:00:00','2026-01-08 00:00:00','2026-05-05 04:00:00'),(87,'lim_peter@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000087','student','active',1,'system','2026-05-04 00:30:00','2026-01-08 00:00:00','2026-05-04 00:30:00'),(88,'go_patricia@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000088','student','active',1,'system','2026-05-02 23:00:00','2026-01-08 00:00:00','2026-05-02 23:00:00'),(89,'uy_dennis@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000089','student','active',1,'light','2026-05-02 00:00:00','2026-01-08 00:00:00','2026-05-02 00:00:00'),(90,'sy_rachel@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000090','student','active',1,'system','2026-05-01 01:00:00','2026-01-08 00:00:00','2026-05-01 01:00:00'),(91,'tiu_harold@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000091','student','active',1,'system','2026-04-30 02:00:00','2026-01-08 00:00:00','2026-04-30 02:00:00'),(92,'ong_carla@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000092','student','active',1,'dark','2026-04-29 03:00:00','2026-01-08 00:00:00','2026-04-29 03:00:00'),(93,'kho_victor@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000093','student','active',1,'system','2026-04-27 22:00:00','2026-01-08 00:00:00','2026-04-27 22:00:00'),(94,'chan_sheila@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000094','student','active',1,'light','2026-04-26 23:30:00','2026-01-08 00:00:00','2026-04-26 23:30:00'),(95,'ang_jerome@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000095','student','active',1,'system','2026-04-26 00:00:00','2026-01-08 00:00:00','2026-04-26 00:00:00'),(96,'yap_elaine@plpasig.edu.ph','$2y$10$sample_hash_placeholder_00000096','student','active',1,'system','2026-04-25 01:00:00','2026-01-08 00:00:00','2026-04-25 01:00:00'),(97,'martinez_aaron@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000101','student','active',1,'system','2026-05-12 00:00:00','2026-04-30 16:00:00','2026-05-12 00:00:00'),(98,'ramirez_bea@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000102','student','active',1,'system','2026-05-12 00:01:00','2026-04-30 16:00:00','2026-05-12 00:01:00'),(99,'gomez_carl@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000103','student','active',1,'system','2026-05-12 00:02:00','2026-04-30 16:00:00','2026-05-12 00:02:00'),(100,'navarro_diana@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000104','student','active',1,'system','2026-05-12 00:03:00','2026-04-30 16:00:00','2026-05-12 00:03:00'),(101,'perez_ethan@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000105','student','active',1,'system','2026-05-12 00:04:00','2026-04-30 16:00:00','2026-05-12 00:04:00'),(102,'aquino_faith@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000106','student','active',1,'system','2026-05-12 00:05:00','2026-04-30 16:00:00','2026-05-12 00:05:00'),(103,'cruz_gabriel@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000107','student','active',1,'system','2026-05-12 00:06:00','2026-04-30 16:00:00','2026-05-12 00:06:00'),(104,'santos_hannah@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000108','student','active',1,'system','2026-05-12 00:07:00','2026-04-30 16:00:00','2026-05-12 00:07:00'),(105,'reyes_ivan@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000109','student','active',1,'system','2026-05-12 00:08:00','2026-04-30 16:00:00','2026-05-12 00:08:00'),(106,'flores_jasmine@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000110','student','active',1,'system','2026-05-12 00:09:00','2026-04-30 16:00:00','2026-05-12 00:09:00'),(107,'torres_kevin@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000111','student','active',1,'system','2026-05-12 00:10:00','2026-04-30 16:00:00','2026-05-12 00:10:00'),(108,'mendoza_lara@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000112','student','active',1,'system','2026-05-12 00:11:00','2026-04-30 16:00:00','2026-05-12 00:11:00'),(109,'castillo_matthew@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000113','student','active',1,'system','2026-05-12 00:12:00','2026-04-30 16:00:00','2026-05-12 00:12:00'),(110,'garcia_nicole@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000114','student','active',1,'system','2026-05-12 00:13:00','2026-04-30 16:00:00','2026-05-12 00:13:00'),(111,'herrera_oscar@plpasig.edu.ph','$2y$10$sample_hash_placeholder_0000115','student','active',1,'system','2026-05-12 00:14:00','2026-04-30 16:00:00','2026-05-12 00:14:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_users_after_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

  VALUES (NEW.id, 'user_registered', 'users', NEW.id,

          JSON_OBJECT('email', NEW.email, 'role', NEW.role));

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_users_after_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN

  IF OLD.email <> NEW.email THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NEW.id, 'user_updated', 'users', NEW.id,

            JSON_OBJECT('field','email','old', OLD.email,'new', NEW.email));

  END IF;

  IF OLD.role <> NEW.role THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NEW.id, 'user_updated', 'users', NEW.id,

            JSON_OBJECT('field','role','old', OLD.role,'new', NEW.role));

  END IF;

  IF OLD.status <> NEW.status THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NEW.id, 'user_updated', 'users', NEW.id,

            JSON_OBJECT('field','status','old', OLD.status,'new', NEW.status));

  END IF;

  IF OLD.email_verified <> NEW.email_verified THEN

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

    VALUES (NEW.id, 'user_updated', 'users', NEW.id,

            JSON_OBJECT('field','email_verified','old', OLD.email_verified,'new', NEW.email_verified));

  END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_users_after_delete` AFTER DELETE ON `users` FOR EACH ROW BEGIN

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail)

  VALUES (NULL, 'user_deleted', 'users', OLD.id,

          JSON_OBJECT('email', OLD.email, 'role', OLD.role));

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary table structure for view `v_active_enrollments`
--

DROP TABLE IF EXISTS `v_active_enrollments`;
/*!50001 DROP VIEW IF EXISTS `v_active_enrollments`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_active_enrollments` AS SELECT
 1 AS `enrollment_id`,
  1 AS `student_id`,
  1 AS `student_name`,
  1 AS `student_number`,
  1 AS `section_id`,
  1 AS `section_code`,
  1 AS `course_id`,
  1 AS `course_code`,
  1 AS `course_title`,
  1 AS `instructor_name`,
  1 AS `term_label`,
  1 AS `enrollment_status`,
  1 AS `final_grade`,
  1 AS `enrolled_at` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_instructor_course_feed`
--

DROP TABLE IF EXISTS `v_instructor_course_feed`;
/*!50001 DROP VIEW IF EXISTS `v_instructor_course_feed`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_instructor_course_feed` AS SELECT
 1 AS `post_id`,
  1 AS `section_id`,
  1 AS `section_code`,
  1 AS `course_code`,
  1 AS `course_title`,
  1 AS `post_type`,
  1 AS `title`,
  1 AS `body`,
  1 AS `is_pinned`,
  1 AS `is_published`,
  1 AS `published_at`,
  1 AS `author_id`,
  1 AS `author_name`,
  1 AS `file_count`,
  1 AS `read_count` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_instructor_dashboard`
--

DROP TABLE IF EXISTS `v_instructor_dashboard`;
/*!50001 DROP VIEW IF EXISTS `v_instructor_dashboard`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_instructor_dashboard` AS SELECT
 1 AS `instructor_id`,
  1 AS `section_id`,
  1 AS `section_code`,
  1 AS `course_code`,
  1 AS `course_title`,
  1 AS `term_label`,
  1 AS `enrolled_students`,
  1 AS `pending_submissions`,
  1 AS `total_posts`,
  1 AS `total_modules`,
  1 AS `total_assignments`,
  1 AS `total_quizzes` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_pending_submissions`
--

DROP TABLE IF EXISTS `v_pending_submissions`;
/*!50001 DROP VIEW IF EXISTS `v_pending_submissions`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_pending_submissions` AS SELECT
 1 AS `submission_id`,
  1 AS `assignment_id`,
  1 AS `assignment_title`,
  1 AS `student_id`,
  1 AS `student_name`,
  1 AS `submitted_at`,
  1 AS `is_late`,
  1 AS `section_id`,
  1 AS `section_code`,
  1 AS `course_code` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_student_grades_summary`
--

DROP TABLE IF EXISTS `v_student_grades_summary`;
/*!50001 DROP VIEW IF EXISTS `v_student_grades_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_student_grades_summary` AS SELECT
 1 AS `enrollment_id`,
  1 AS `student_id`,
  1 AS `student_name`,
  1 AS `course_code`,
  1 AS `course_title`,
  1 AS `section_code`,
  1 AS `term_label`,
  1 AS `final_grade`,
  1 AS `enrollment_status` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_active_enrollments`
--

/*!50001 DROP VIEW IF EXISTS `v_active_enrollments`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_active_enrollments` AS select `e`.`id` AS `enrollment_id`,`e`.`student_id` AS `student_id`,concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`,`sp`.`student_id` AS `student_number`,`cs`.`id` AS `section_id`,`cs`.`section_code` AS `section_code`,`c`.`id` AS `course_id`,`c`.`code` AS `course_code`,`c`.`title` AS `course_title`,concat(`ui`.`first_name`,' ',`ui`.`last_name`) AS `instructor_name`,`at`.`label` AS `term_label`,`e`.`status` AS `enrollment_status`,`e`.`final_grade` AS `final_grade`,`e`.`enrolled_at` AS `enrolled_at` from ((((((((`enrollments` `e` join `users` `u` on(`u`.`id` = `e`.`student_id`)) join `user_profiles` `up` on(`up`.`user_id` = `e`.`student_id`)) join `student_profiles` `sp` on(`sp`.`user_id` = `e`.`student_id`)) join `course_sections` `cs` on(`cs`.`id` = `e`.`section_id`)) join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `users` `ui2` on(`ui2`.`id` = `cs`.`instructor_id`)) join `user_profiles` `ui` on(`ui`.`user_id` = `cs`.`instructor_id`)) join `academic_terms` `at` on(`at`.`id` = `cs`.`term_id`)) where `e`.`status` = 'enrolled' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_instructor_course_feed`
--

/*!50001 DROP VIEW IF EXISTS `v_instructor_course_feed`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_instructor_course_feed` AS select `cp`.`id` AS `post_id`,`cp`.`section_id` AS `section_id`,`cs`.`section_code` AS `section_code`,`c`.`code` AS `course_code`,`c`.`title` AS `course_title`,`cp`.`post_type` AS `post_type`,`cp`.`title` AS `title`,`cp`.`body` AS `body`,`cp`.`is_pinned` AS `is_pinned`,`cp`.`is_published` AS `is_published`,`cp`.`published_at` AS `published_at`,`cp`.`author_id` AS `author_id`,concat(`up`.`first_name`,' ',`up`.`last_name`) AS `author_name`,(select count(0) from `course_post_files` `cpf` where `cpf`.`post_id` = `cp`.`id`) AS `file_count`,(select count(0) from `course_post_reads` `cpr` where `cpr`.`post_id` = `cp`.`id`) AS `read_count` from (((`course_posts` `cp` join `course_sections` `cs` on(`cs`.`id` = `cp`.`section_id`)) join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `user_profiles` `up` on(`up`.`user_id` = `cp`.`author_id`)) where `cp`.`is_published` = 1 order by `cp`.`is_pinned` desc,`cp`.`published_at` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_instructor_dashboard`
--

/*!50001 DROP VIEW IF EXISTS `v_instructor_dashboard`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_instructor_dashboard` AS select `cs`.`instructor_id` AS `instructor_id`,`cs`.`id` AS `section_id`,`cs`.`section_code` AS `section_code`,`c`.`code` AS `course_code`,`c`.`title` AS `course_title`,`at`.`label` AS `term_label`,count(distinct `e`.`student_id`) AS `enrolled_students`,count(distinct `sub`.`id`) AS `pending_submissions`,count(distinct `cp`.`id`) AS `total_posts`,count(distinct `m`.`id`) AS `total_modules`,count(distinct `a`.`id`) AS `total_assignments`,count(distinct `q`.`id`) AS `total_quizzes` from ((((((((`course_sections` `cs` join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `academic_terms` `at` on(`at`.`id` = `cs`.`term_id`)) left join `enrollments` `e` on(`e`.`section_id` = `cs`.`id` and `e`.`status` = 'enrolled')) left join `assignments` `a` on(`a`.`section_id` = `cs`.`id`)) left join `submissions` `sub` on(`sub`.`assignment_id` = `a`.`id` and `sub`.`status` = 'submitted')) left join `quizzes` `q` on(`q`.`section_id` = `cs`.`id`)) left join `modules` `m` on(`m`.`section_id` = `cs`.`id` and `m`.`is_published` = 1)) left join `course_posts` `cp` on(`cp`.`section_id` = `cs`.`id` and `cp`.`is_published` = 1)) group by `cs`.`instructor_id`,`cs`.`id`,`cs`.`section_code`,`c`.`code`,`c`.`title`,`at`.`label` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_pending_submissions`
--

/*!50001 DROP VIEW IF EXISTS `v_pending_submissions`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_pending_submissions` AS select `s`.`id` AS `submission_id`,`s`.`assignment_id` AS `assignment_id`,`a`.`title` AS `assignment_title`,`s`.`student_id` AS `student_id`,concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`,`s`.`submitted_at` AS `submitted_at`,`s`.`is_late` AS `is_late`,`cs`.`id` AS `section_id`,`cs`.`section_code` AS `section_code`,`c`.`code` AS `course_code` from ((((`submissions` `s` join `assignments` `a` on(`a`.`id` = `s`.`assignment_id`)) join `course_sections` `cs` on(`cs`.`id` = `a`.`section_id`)) join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `user_profiles` `up` on(`up`.`user_id` = `s`.`student_id`)) where `s`.`status` = 'submitted' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_student_grades_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_student_grades_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_student_grades_summary` AS select `e`.`id` AS `enrollment_id`,`e`.`student_id` AS `student_id`,concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`,`c`.`code` AS `course_code`,`c`.`title` AS `course_title`,`cs`.`section_code` AS `section_code`,`at`.`label` AS `term_label`,`e`.`final_grade` AS `final_grade`,`e`.`status` AS `enrollment_status` from ((((`enrollments` `e` join `user_profiles` `up` on(`up`.`user_id` = `e`.`student_id`)) join `course_sections` `cs` on(`cs`.`id` = `e`.`section_id`)) join `courses` `c` on(`c`.`id` = `cs`.`course_id`)) join `academic_terms` `at` on(`at`.`id` = `cs`.`term_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-16  8:00:51
