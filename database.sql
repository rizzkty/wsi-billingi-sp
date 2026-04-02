/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.2.2-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: billing_isp
-- ------------------------------------------------------
-- Server version	12.2.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `log_aktivitas`
--

DROP TABLE IF EXISTS `log_aktivitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `aksi` varchar(255) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_aktivitas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `log_aktivitas` WRITE;
/*!40000 ALTER TABLE `log_aktivitas` DISABLE KEYS */;
INSERT INTO `log_aktivitas` VALUES
(1,1,'LOGIN','Login berhasil','::1','2026-04-01 07:39:46'),
(2,1,'TAMBAH_USER','Tambah user: lutpiii (admin)','::1','2026-04-01 07:49:32'),
(3,3,'LOGIN','Login berhasil','::1','2026-04-01 08:01:04'),
(4,3,'LOGIN','Login berhasil','::1','2026-04-01 15:57:01'),
(5,1,'LOGIN','Login berhasil','::1','2026-04-01 15:59:54'),
(6,1,'LOGOUT','Logout','::1','2026-04-01 16:05:44'),
(7,1,'LOGIN','Login berhasil','::1','2026-04-02 01:02:08'),
(8,1,'TOGGLE_USER','User ID 3 aktif=0','::1','2026-04-02 01:04:16'),
(9,1,'TOGGLE_USER','User ID 3 aktif=1','::1','2026-04-02 01:04:18'),
(10,1,'TOGGLE_USER','User ID 3 aktif=0','::1','2026-04-02 01:04:19'),
(11,1,'TOGGLE_USER','User ID 3 aktif=1','::1','2026-04-02 01:04:20'),
(12,1,'LOGOUT','Logout','::1','2026-04-02 01:10:34'),
(13,1,'LOGIN','Login berhasil','::1','2026-04-02 01:35:18'),
(14,1,'LOGOUT','Logout','::1','2026-04-02 01:35:30'),
(15,3,'LOGIN','Login berhasil','::1','2026-04-02 01:35:49'),
(16,3,'LOGOUT','Logout','::1','2026-04-02 01:36:45'),
(17,1,'LOGIN','Login berhasil','::1','2026-04-02 01:37:02'),
(18,1,'TAMBAH_USER','Tambah user: rama (teknisi)','::1','2026-04-02 01:37:53'),
(19,1,'LOGOUT','Logout','::1','2026-04-02 01:55:29'),
(20,3,'LOGIN','Login berhasil','::1','2026-04-02 01:55:45'),
(21,3,'LOGOUT','Logout','::1','2026-04-02 01:56:35'),
(22,4,'LOGIN','Login berhasil','::1','2026-04-02 01:56:51'),
(23,4,'LOGOUT','Logout','::1','2026-04-02 01:57:18'),
(24,1,'LOGIN','Login berhasil','::1','2026-04-02 01:57:30'),
(25,1,'TOGGLE_USER','User ID 3 aktif=0','::1','2026-04-02 01:57:38'),
(26,1,'LOGOUT','Logout','::1','2026-04-02 01:57:45'),
(27,1,'LOGIN','Login berhasil','::1','2026-04-02 01:58:32'),
(28,1,'TOGGLE_USER','User ID 3 aktif=1','::1','2026-04-02 01:58:35'),
(29,1,'LOGOUT','Logout','::1','2026-04-02 01:58:37'),
(30,3,'LOGIN','Login berhasil','::1','2026-04-02 01:58:52'),
(31,3,'LOGOUT','Logout','::1','2026-04-02 02:00:04'),
(32,4,'LOGIN','Login berhasil','::1','2026-04-02 02:00:17'),
(33,4,'LOGOUT','Logout','::1','2026-04-02 02:03:16'),
(34,1,'LOGIN','Login berhasil','::1','2026-04-02 02:03:39'),
(35,1,'TOGGLE_USER','User ID 3 aktif=0','::1','2026-04-02 02:04:00'),
(36,1,'LOGOUT','Logout','::1','2026-04-02 02:04:32'),
(37,4,'LOGIN','Login berhasil','::1','2026-04-02 02:05:22'),
(38,4,'LOGOUT','Logout','::1','2026-04-02 02:06:23'),
(39,1,'LOGIN','Login berhasil','::1','2026-04-02 02:06:35'),
(40,1,'TOGGLE_USER','User ID 3 aktif=1','::1','2026-04-02 02:06:39'),
(41,1,'LOGOUT','Logout','::1','2026-04-02 02:06:45'),
(42,3,'LOGIN','Login berhasil','::1','2026-04-02 02:06:59'),
(43,3,'LOGOUT','Logout','::1','2026-04-02 02:07:19'),
(44,1,'LOGIN','Login berhasil','::1','2026-04-02 02:07:45'),
(45,1,'TOGGLE_USER','User ID 3 aktif=0','::1','2026-04-02 02:07:58'),
(46,1,'TOGGLE_USER','User ID 3 aktif=1','::1','2026-04-02 02:08:05'),
(47,1,'TOGGLE_USER','User ID 3 aktif=0','::1','2026-04-02 02:08:06'),
(48,1,'TOGGLE_USER','User ID 3 aktif=1','::1','2026-04-02 02:08:07'),
(49,1,'TOGGLE_USER','User ID 3 aktif=0','::1','2026-04-02 02:08:08'),
(50,1,'TOGGLE_USER','User ID 3 aktif=1','::1','2026-04-02 02:08:09');
/*!40000 ALTER TABLE `log_aktivitas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('pemilik','admin','teknisi') NOT NULL DEFAULT 'teknisi',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `dibuat_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `dibuat_oleh` (`dibuat_oleh`),
  CONSTRAINT `1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Pemilik ISP','pemilik','$2y$12$Eaq7YDVkf9kDPVgb88Hus.WelBP9FRfvW55U5GeM02w9tjeaFTVki','pemilik',1,NULL,'2026-04-01 02:52:38','2026-04-01 07:39:32'),
(3,'lutpi','lutpiii','$2y$12$BOmse87pV.XdmPaabj0NuO4EPEpxxOla5uaXT0Kk27vR1NRiMt05e','admin',1,1,'2026-04-01 07:49:32','2026-04-02 02:08:08'),
(4,'lucky rama','rama','$2y$12$Y7IM0iznykIeFZFvlrTNKO67jqaGkSG6.DHO0cY1/qaAdO4jGaDAO','teknisi',1,1,'2026-04-02 01:37:53','2026-04-02 01:37:53');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-04-02  9:31:11
