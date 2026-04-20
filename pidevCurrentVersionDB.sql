-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 20, 2026 at 02:22 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pidev`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
CREATE TABLE IF NOT EXISTS `activities` (
  `id_activity` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `activity_date` date NOT NULL,
  `description` longtext,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `deadline` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `time_spent` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_activity`),
  KEY `IDX_B5F1AFE58C03F15C` (`employee_id`),
  KEY `IDX_B5F1AFE5166D1F9C` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id_activity`, `employee_id`, `activity_date`, `description`, `hours_worked`, `project_id`, `status`, `deadline`, `submitted_at`, `time_spent`) VALUES
(1, 14, '2026-03-17', '[✅ REVIEWED]\n151561', 15.00, 6, 'PENDING', NULL, NULL, 0),
(2, 14, '2026-03-17', '15651', 6.00, 6, 'PENDING', NULL, NULL, 0),
(3, 19, '2026-03-26', '11111', 555.00, 7, 'PENDING', NULL, NULL, 0),
(4, 19, '2026-04-01', '15151', 4.00, 7, 'PENDING', NULL, NULL, 0),
(5, 13, '2026-04-03', 'hfiezhfiuezh', 18.00, 7, 'PENDING', NULL, NULL, 0),
(6, 19, '2026-04-06', '67', 14.00, 4, 'PENDING', NULL, NULL, 0),
(7, 19, '2026-04-06', 'dddddd', 10.00, 8, 'PENDING', NULL, NULL, 0),
(8, 19, '2026-04-20', '123456', NULL, 7, 'PENDING', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `offer_id` int NOT NULL,
  `cv_file_path` varchar(500) DEFAULT NULL,
  `motivation_letter` longtext,
  `status` varchar(50) NOT NULL DEFAULT 'Nouvelle',
  `application_date` date DEFAULT NULL,
  `score` double NOT NULL DEFAULT '0',
  `notes` longtext,
  `interviewer` varchar(150) DEFAULT NULL,
  `interview_date` date DEFAULT NULL,
  `interview_result` varchar(100) DEFAULT NULL,
  `recruiter_response` longtext,
  `response_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_offer` (`user_id`,`offer_id`),
  KEY `IDX_F7C966F0A76ED395` (`user_id`),
  KEY `IDX_F7C966F053C674EE` (`offer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `offer_id`, `cv_file_path`, `motivation_letter`, `status`, `application_date`, `score`, `notes`, `interviewer`, `interview_date`, `interview_result`, `recruiter_response`, `response_date`) VALUES
(1, 14, 2, 'C:\\Users\\ayoub\\Downloads\\RH (3).pdf', 'hey there', 'Nouvelle', '2026-02-26', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 15, 2, 'C:\\Users\\ayoub\\Downloads\\RH (3).pdf', 'hey', 'Nouvelle', '2026-02-26', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 14, 3, 'C:\\Users\\ayoub\\Downloads\\RH (3).pdf', 'hello', 'Acceptée', '2026-02-26', 0, NULL, NULL, NULL, NULL, 'très bien', '2026-02-26'),
(4, 17, 3, NULL, '', 'Refusée', '2026-02-26', 0, NULL, NULL, NULL, NULL, 'your profile is incomplete', '2026-02-26'),
(5, 17, 2, NULL, '', 'Nouvelle', '2026-02-26', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 17, 4, 'C:\\Users\\ayoub\\Downloads\\RH (3).pdf', 'hey', 'Refusée', '2026-02-28', 0, NULL, NULL, NULL, NULL, 'BARA ZAMER', '2026-02-28'),
(7, 19, 4, NULL, 'hey there im looking forward to meet ya', 'Acceptée', '2026-03-01', 0, NULL, NULL, NULL, NULL, 'Félicitations ! Votre candidature a été acceptée.', '2026-03-02'),
(8, 19, 2, NULL, 'ayo whats up good fella!', 'Nouvelle', '2026-03-01', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 14, 6, NULL, 'ozfjfez', 'Acceptée', '2026-03-30', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 19, 6, '/uploads/cvs/app-cv-19-69d3bdea5c457.pdf', '', 'Entretien', '2026-04-06', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 19, 7, '/uploads/cvs/app-cv-19-69d3cb5237efd.pdf', 'hjkghg', 'Acceptée', '2026-04-06', 0, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

DROP TABLE IF EXISTS `bookmarks`;
CREATE TABLE IF NOT EXISTS `bookmarks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `offer_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_78D2C14053C674EE` (`offer_id`),
  KEY `IDX_78D2C140A76ED395` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookmarks`
--

INSERT INTO `bookmarks` (`id`, `user_id`, `offer_id`, `created_at`) VALUES
(3, 14, 4, '2026-04-03 17:16:04'),
(5, 14, 6, '2026-04-03 17:16:04'),
(7, 19, 5, '2026-04-06 13:29:36'),
(8, 19, 6, '2026-04-06 14:06:42');

-- --------------------------------------------------------

--
-- Table structure for table `choix`
--

DROP TABLE IF EXISTS `choix`;
CREATE TABLE IF NOT EXISTS `choix` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `texte` varchar(500) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `IDX_4F4880911E27F6BF` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `choix`
--

INSERT INTO `choix` (`id`, `question_id`, `texte`, `is_correct`) VALUES
(25, 7, 'c1', 1),
(26, 7, 'c2', 0);

-- --------------------------------------------------------

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
CREATE TABLE IF NOT EXISTS `event` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` longtext,
  `event_type` enum('MEETUP','CONFERENCE','WORKSHOP','WEBINAR') DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `location` varchar(500) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT '0',
  `online_link` varchar(500) DEFAULT NULL,
  `max_capacity` int NOT NULL DEFAULT '0',
  `cover_image` varchar(500) DEFAULT NULL,
  `organizer_id` int NOT NULL,
  `status` enum('UPCOMING','ONGOING','COMPLETED','CANCELLED') DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_3BAE0AA7876C4DDA` (`organizer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`id`, `title`, `description`, `event_type`, `event_date`, `end_date`, `location`, `latitude`, `longitude`, `is_online`, `online_link`, `max_capacity`, `cover_image`, `organizer_id`, `status`, `created_at`) VALUES
(1, 'java meetup', 'hello', 'CONFERENCE', '2026-04-06 11:00:00', '2026-04-06 16:00:00', 'technopole', NULL, NULL, 0, '', 50, NULL, 13, 'UPCOMING', '2026-03-30 14:57:51'),
(2, 'jfeziojgoergjkjroij', 'jgrejoigjiroj', 'WORKSHOP', '2026-04-22 14:34:00', NULL, 'tunis', NULL, NULL, 0, NULL, 12, NULL, 13, 'UPCOMING', '2026-04-06 13:32:40'),
(3, 'aadd', 'ddadafaaaaa', 'WORKSHOP', '2026-04-14 15:55:00', NULL, 'tunis', NULL, NULL, 0, NULL, 4, NULL, 12, 'UPCOMING', '2026-04-06 14:55:21');

-- --------------------------------------------------------

--
-- Table structure for table `event_comment`
--

DROP TABLE IF EXISTS `event_comment`;
CREATE TABLE IF NOT EXISTS `event_comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_1123FBC371F7E88B` (`event_id`),
  KEY `IDX_1123FBC3A76ED395` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_comment`
--

INSERT INTO `event_comment` (`id`, `event_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 13, 'hey', '2026-03-30 14:58:23'),
(2, 1, 17, 'hiuhuih', '2026-03-30 15:01:29'),
(3, 3, 19, 'hey', '2026-04-06 14:58:08');

-- --------------------------------------------------------

--
-- Table structure for table `event_like`
--

DROP TABLE IF EXISTS `event_like`;
CREATE TABLE IF NOT EXISTS `event_like` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`event_id`,`user_id`),
  KEY `IDX_B3A80C1871F7E88B` (`event_id`),
  KEY `IDX_B3A80C18A76ED395` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_like`
--

INSERT INTO `event_like` (`id`, `event_id`, `user_id`, `created_at`) VALUES
(1, 1, 17, '2026-03-30 15:01:31'),
(2, 1, 19, '2026-04-03 20:14:56'),
(3, 3, 19, '2026-04-06 14:57:47');

-- --------------------------------------------------------

--
-- Table structure for table `event_participation`
--

DROP TABLE IF EXISTS `event_participation`;
CREATE TABLE IF NOT EXISTS `event_participation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('CONFIRMED','PENDING','CANCELLED','ATTENDED') DEFAULT NULL,
  `registered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qr_code` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participation` (`event_id`,`user_id`),
  KEY `IDX_8F0C52E371F7E88B` (`event_id`),
  KEY `IDX_8F0C52E3A76ED395` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_participation`
--

INSERT INTO `event_participation` (`id`, `event_id`, `user_id`, `status`, `registered_at`, `qr_code`) VALUES
(1, 1, 14, 'CONFIRMED', '2026-03-30 14:59:10', 'talentos://event/1/user/14/stats'),
(2, 1, 17, 'CONFIRMED', '2026-03-30 15:00:40', 'talentos://event/1/user/17/stats'),
(3, 1, 19, 'CONFIRMED', '2026-04-06 13:36:07', NULL),
(4, 3, 19, 'CONFIRMED', '2026-04-06 14:56:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `formation`
--

DROP TABLE IF EXISTS `formation`;
CREATE TABLE IF NOT EXISTS `formation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` longtext,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `recruiter_id` int DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `niveau` varchar(100) DEFAULT NULL,
  `duree` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `formation`
--

INSERT INTO `formation` (`id`, `description`, `date_debut`, `date_fin`, `recruiter_id`, `titre`, `niveau`, `duree`) VALUES
(1, 'hello', '2026-03-18', '2026-03-19', 13, '', NULL, NULL),
(2, 'byofejziofjez', '2026-04-15', NULL, 12, 'hellow', 'Intermédiaire', 1),
(3, 'formation en python', '2026-04-06', NULL, 12, 'python', 'Avancé', 30);

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

DROP TABLE IF EXISTS `interviews`;
CREATE TABLE IF NOT EXISTS `interviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `interview_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'SCHEDULED',
  `notes` longtext,
  `location` varchar(100) DEFAULT NULL,
  `meeting_link` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `application_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3A7526823E030ACD` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
CREATE TABLE IF NOT EXISTS `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `messenger_messages`
--

INSERT INTO `messenger_messages` (`id`, `body`, `headers`, `queue_name`, `created_at`, `available_at`, `delivered_at`) VALUES
(1, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:897:\\\"<div style=\\\"font-family:Inter,sans-serif;max-width:500px;margin:0 auto;padding:40px\\\"><h2 style=\\\"color:#4f46e5\\\">🔐 Réinitialisation de mot de passe</h2><p>Bonjour,</p><p>Vous avez demandé la réinitialisation de votre mot de passe Talentos.</p><p style=\\\"text-align:center;margin:30px 0\\\"><a href=\\\"http://localhost:8000/reset-password/2239e28f5a57e0da705a6df49077c5a46863e1283b42b6588da1b71998068844\\\" style=\\\"background:linear-gradient(135deg,#4f46e5,#06b6d4);color:white;padding:14px 32px;border-radius:12px;text-decoration:none;font-weight:600;display:inline-block\\\">Réinitialiser mon mot de passe</a></p><p style=\\\"color:#64748b;font-size:0.85rem\\\">Ce lien est valable 1 heure. Si vous n\\\'avez pas fait cette demande, ignorez cet email.</p><hr style=\\\"border:none;border-top:1px solid #e2e8f0;margin:30px 0\\\"><p style=\\\"color:#94a3b8;font-size:0.8rem\\\">Talentos — Plateforme de recrutement</p></div>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"noreply@talentos.tn\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"ayoubhamed111@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:46:\\\"Talentos — Réinitialisation de mot de passe\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-04-06 03:51:15', '2026-04-06 03:51:15', NULL),
(2, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:844:\\\"<div style=\\\"font-family: \\\'Segoe UI\\\', Arial, sans-serif; max-width: 480px; margin: 0 auto;padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);border-radius: 16px;\\\"><div style=\\\"background: white; border-radius: 12px; padding: 32px; text-align: center;\\\"><h1 style=\\\"color: #111827; font-size: 24px; margin: 0 0 8px;\\\">Talentos</h1><p style=\\\"color: #6b7280; font-size: 14px; margin: 0 0 24px;\\\">Réinitialisation de mot de passe</p><div style=\\\"background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px;\\\"><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0 0 8px;\\\">Votre code de vérification</p><h2 style=\\\"color: #6366f1; font-size: 36px; letter-spacing: 8px; margin: 0; font-weight: 800;\\\">189919</h2></div><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0;\\\">Ce code expire dans 10 minutes.</p></div></div>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:24:\\\"talentos.pidev@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"ayoubhamed111@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:39:\\\"🔐 Talentos — Code de vérification\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-04-06 04:14:34', '2026-04-06 04:14:34', NULL),
(3, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:844:\\\"<div style=\\\"font-family: \\\'Segoe UI\\\', Arial, sans-serif; max-width: 480px; margin: 0 auto;padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);border-radius: 16px;\\\"><div style=\\\"background: white; border-radius: 12px; padding: 32px; text-align: center;\\\"><h1 style=\\\"color: #111827; font-size: 24px; margin: 0 0 8px;\\\">Talentos</h1><p style=\\\"color: #6b7280; font-size: 14px; margin: 0 0 24px;\\\">Réinitialisation de mot de passe</p><div style=\\\"background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px;\\\"><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0 0 8px;\\\">Votre code de vérification</p><h2 style=\\\"color: #6366f1; font-size: 36px; letter-spacing: 8px; margin: 0; font-weight: 800;\\\">053011</h2></div><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0;\\\">Ce code expire dans 10 minutes.</p></div></div>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:24:\\\"talentos.pidev@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"ayoubhamed111@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:39:\\\"🔐 Talentos — Code de vérification\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-04-06 04:19:53', '2026-04-06 04:19:53', NULL),
(4, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:844:\\\"<div style=\\\"font-family: \\\'Segoe UI\\\', Arial, sans-serif; max-width: 480px; margin: 0 auto;padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);border-radius: 16px;\\\"><div style=\\\"background: white; border-radius: 12px; padding: 32px; text-align: center;\\\"><h1 style=\\\"color: #111827; font-size: 24px; margin: 0 0 8px;\\\">Talentos</h1><p style=\\\"color: #6b7280; font-size: 14px; margin: 0 0 24px;\\\">Réinitialisation de mot de passe</p><div style=\\\"background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px;\\\"><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0 0 8px;\\\">Votre code de vérification</p><h2 style=\\\"color: #6366f1; font-size: 36px; letter-spacing: 8px; margin: 0; font-weight: 800;\\\">268627</h2></div><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0;\\\">Ce code expire dans 10 minutes.</p></div></div>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:24:\\\"talentos.pidev@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"ayoubhamed111@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:39:\\\"🔐 Talentos — Code de vérification\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-04-06 04:20:10', '2026-04-06 04:20:10', NULL),
(5, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:839:\\\"<div style=\\\"font-family: \\\'Segoe UI\\\', Arial, sans-serif; max-width: 480px; margin: 0 auto;padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);border-radius: 16px;\\\"><div style=\\\"background: white; border-radius: 12px; padding: 32px; text-align: center;\\\"><h1 style=\\\"color: #111827; font-size: 24px; margin: 0 0 8px;\\\">Talentos</h1><p style=\\\"color: #6b7280; font-size: 14px; margin: 0 0 24px;\\\">Vérification de votre email</p><div style=\\\"background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px;\\\"><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0 0 8px;\\\">Votre code de vérification</p><h2 style=\\\"color: #6366f1; font-size: 36px; letter-spacing: 8px; margin: 0; font-weight: 800;\\\">961249</h2></div><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0;\\\">Ce code expire dans 10 minutes.</p></div></div>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:24:\\\"talentos.pidev@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:22:\\\"fiatsiena961@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:39:\\\"🔐 Talentos — Code de vérification\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-04-06 04:21:55', '2026-04-06 04:21:55', NULL),
(6, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:844:\\\"<div style=\\\"font-family: \\\'Segoe UI\\\', Arial, sans-serif; max-width: 480px; margin: 0 auto;padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);border-radius: 16px;\\\"><div style=\\\"background: white; border-radius: 12px; padding: 32px; text-align: center;\\\"><h1 style=\\\"color: #111827; font-size: 24px; margin: 0 0 8px;\\\">Talentos</h1><p style=\\\"color: #6b7280; font-size: 14px; margin: 0 0 24px;\\\">Réinitialisation de mot de passe</p><div style=\\\"background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px;\\\"><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0 0 8px;\\\">Votre code de vérification</p><h2 style=\\\"color: #6366f1; font-size: 36px; letter-spacing: 8px; margin: 0; font-weight: 800;\\\">722751</h2></div><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0;\\\">Ce code expire dans 10 minutes.</p></div></div>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:24:\\\"talentos.pidev@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"ayoubhamed111@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:39:\\\"🔐 Talentos — Code de vérification\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-04-06 04:40:42', '2026-04-06 04:40:42', NULL),
(7, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:844:\\\"<div style=\\\"font-family: \\\'Segoe UI\\\', Arial, sans-serif; max-width: 480px; margin: 0 auto;padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);border-radius: 16px;\\\"><div style=\\\"background: white; border-radius: 12px; padding: 32px; text-align: center;\\\"><h1 style=\\\"color: #111827; font-size: 24px; margin: 0 0 8px;\\\">Talentos</h1><p style=\\\"color: #6b7280; font-size: 14px; margin: 0 0 24px;\\\">Réinitialisation de mot de passe</p><div style=\\\"background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px;\\\"><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0 0 8px;\\\">Votre code de vérification</p><h2 style=\\\"color: #6366f1; font-size: 36px; letter-spacing: 8px; margin: 0; font-weight: 800;\\\">209809</h2></div><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0;\\\">Ce code expire dans 10 minutes.</p></div></div>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:24:\\\"talentos.pidev@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"ayoubhamed111@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:39:\\\"🔐 Talentos — Code de vérification\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-04-06 04:41:22', '2026-04-06 04:41:22', NULL),
(8, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:844:\\\"<div style=\\\"font-family: \\\'Segoe UI\\\', Arial, sans-serif; max-width: 480px; margin: 0 auto;padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);border-radius: 16px;\\\"><div style=\\\"background: white; border-radius: 12px; padding: 32px; text-align: center;\\\"><h1 style=\\\"color: #111827; font-size: 24px; margin: 0 0 8px;\\\">Talentos</h1><p style=\\\"color: #6b7280; font-size: 14px; margin: 0 0 24px;\\\">Réinitialisation de mot de passe</p><div style=\\\"background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px;\\\"><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0 0 8px;\\\">Votre code de vérification</p><h2 style=\\\"color: #6366f1; font-size: 36px; letter-spacing: 8px; margin: 0; font-weight: 800;\\\">614582</h2></div><p style=\\\"color: #9ca3af; font-size: 12px; margin: 0;\\\">Ce code expire dans 10 minutes.</p></div></div>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:24:\\\"talentos.pidev@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"ayoubhamed111@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:39:\\\"🔐 Talentos — Code de vérification\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-04-06 04:47:52', '2026-04-06 04:47:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` longtext,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6000B0D3A76ED395` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`, `user_id`) VALUES
(1, 'REPLY', 'Réponse à votre ticket', 'Ticket: hhaihzia', '/support/2', 0, '2026-04-06 05:04:00', 20),
(2, 'OFFER_DECISION', '✅ Candidature acceptée', 'Votre candidature pour \"udzajou\" a été mise à jour: Acceptée', '/account/applications', 0, '2026-04-06 13:48:01', 14),
(3, 'GENERAL', 'Nouvelle candidature', 'ayoubhamed111@gmail.com a postulé à \"udzajou\"', '/admin/offers/6/applications', 1, '2026-04-06 14:06:34', 13),
(4, 'OFFER_DECISION', '📅 Entretien programmé', 'Votre candidature pour \"udzajou\" a été mise à jour: Entretien', '/account/applications', 1, '2026-04-06 14:08:50', 19),
(5, 'REPLY', 'Réponse à votre ticket', 'Ticket: intro', '/support/3', 1, '2026-04-06 14:38:32', 19),
(6, 'ACTIVITY', 'Nouvelle activité assignée', 'dddddd (Validation)', '/activities', 1, '2026-04-06 14:44:00', 19),
(7, 'GENERAL', 'Nouvelle candidature', 'ayoubhamed111@gmail.com a postulé à \"validation\"', '/admin/offers/7/applications', 1, '2026-04-06 15:03:46', 13),
(8, 'OFFER_DECISION', '✅ Candidature acceptée', 'Votre candidature pour \"validation\" a été mise à jour: Acceptée', '/account/applications', 1, '2026-04-06 15:05:16', 19),
(9, 'REPLY', 'Réponse à votre ticket', 'Ticket: intro', '/support/3', 1, '2026-04-16 16:06:42', 19),
(10, 'REPLY', 'Réponse à votre ticket', 'Ticket: intro', '/support/3', 0, '2026-04-16 16:10:43', 19),
(11, 'REPLY', 'Ticket fermé', 'Votre ticket \"intro\" a été fermé.', '/support/3', 0, '2026-04-16 16:10:48', 19),
(12, 'ACTIVITY', 'Nouvelle activité assignée', '123456 (dazk)', '/activities', 1, '2026-04-20 02:09:08', 19);

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

DROP TABLE IF EXISTS `offers`;
CREATE TABLE IF NOT EXISTS `offers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` longtext,
  `department` varchar(100) DEFAULT NULL,
  `contract_type` varchar(50) DEFAULT NULL,
  `experience_level` varchar(50) DEFAULT NULL,
  `salary_min` double DEFAULT NULL,
  `salary_max` double DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `positions_available` int NOT NULL DEFAULT '1',
  `applications_received` int NOT NULL DEFAULT '0',
  `recruiter_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `offers`
--

INSERT INTO `offers` (`id`, `title`, `description`, `department`, `contract_type`, `experience_level`, `salary_min`, `salary_max`, `location`, `status`, `publish_date`, `closing_date`, `positions_available`, `applications_received`, `recruiter_id`) VALUES
(2, 'ijio', 'fejzifjjifezjoi', 'IT', 'CDI', 'Senior', 4555, 11555, 'Tunis', 'Ouverte', '2026-02-23', '2026-03-25', 6, 5, 0),
(3, 'dev', 'hey there', 'Finance', 'CDI', 'Junior', 1000, 1666, 'tunis', 'Fermée', '2026-02-26', '2026-03-27', 12, 2, 13),
(4, 'Senior DataScientist', 'Bs or Ms in Computer Science is required for this post.\n+10 years of experience preferable.', 'IT', 'CDD', 'Senior', 6500, 7852, 'Paris', 'Ouverte', '2026-02-28', '2026-03-29', 14, 2, 13),
(5, 'graphics designer', 'hey there pls apply!', 'IT', 'CDI', 'Intermédiaire', 10000, 25555, 'Paris', 'Ouverte', '2026-03-02', '2026-04-02', 11, 0, 13),
(6, 'udzajou', 'huiudazhi', 'Finance', 'CDD', 'Intermédiaire', 154, 888, 'paris', 'Ouverte', '2026-03-30', '2026-04-30', 1, 2, 13),
(7, 'validation', 'hghjhghjghjg', 'IT', 'Stage', 'Junior', 120, 200, 'tunis', 'Active', '2026-04-06', NULL, 1, 1, 13);

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

DROP TABLE IF EXISTS `profiles`;
CREATE TABLE IF NOT EXISTS `profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `professional_title` varchar(100) DEFAULT NULL,
  `years_of_experience` int DEFAULT NULL,
  `summary` longtext,
  `profile_completed` tinyint(1) NOT NULL,
  `profile_picture_path` varchar(500) DEFAULT NULL,
  `cv_path` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8B308530A76ED395` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `first_name`, `last_name`, `birth_date`, `phone_number`, `location`, `professional_title`, `years_of_experience`, `summary`, `profile_completed`, `profile_picture_path`, `cv_path`) VALUES
(1, 4, 'ayoub', 'hamed', '2000-02-17', '26413091', 'Tunis', 'Software Engineer', 2, 'hey im looking forward for your offers!!', 1, NULL, NULL),
(2, 5, 'ademv', 'hamed', '2006-04-15', '99111666', 'ariana', 'graphics designer', 3, 'hey there, let\'s connect!', 1, NULL, NULL),
(4, 7, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL),
(5, 8, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL),
(7, 10, 'meniar', 'mili', '2004-02-18', '11222666', 'ariana', 'Engineer', 2, 'hey there im meniar!', 1, NULL, NULL),
(8, 11, 'admin', 'sudo', '2001-02-09', '95666888', 'ariana', 'admin', 1, 'hey there im sudo!', 1, NULL, NULL),
(9, 12, 'Meniar', 'Mili', '2000-02-03', '66555444', 'Ariana', 'Software Engineer', 4, 'hey there , let\'s connect !', 1, NULL, NULL),
(10, 13, 'amen', 'samader', '2000-10-22', '66555888', 'ariana', 'RH', 2, 'hey there, Im Amen , and Im so hungry and foolish!', 1, NULL, NULL),
(11, 14, 'skander', 'nafti', '2004-02-06', '99888666', 'Nkhilet, Ariana', 'Product manager', 15, 'Hey there, I\'m Nafti and I like belotte alot !', 1, NULL, NULL),
(12, 15, 'Ayoub', 'hm', '2003-02-14', '66555222', 'gafsa', 'Data Scienctist', 1, 'hey there , any welcome?', 1, NULL, NULL),
(13, 16, 'ayoub', 'nma', '2002-02-07', '4644', 'aeioak', 'engineer', 4, 'hey there', 1, NULL, NULL),
(14, 17, 'omar', 'hamdi', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL),
(16, 19, 'ayoub', 'hamed', '2000-02-25', '95666333', 'Manhattan, New York', 'Co-founder of Talentos', 2, 'hey there,you can call me Sudo', 1, '/uploads/avatars/avatar_19_1776450168.jpg', NULL),
(17, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(18, 21, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(20, 23, 'Fiatsiena', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(21, 24, 'mkomfkez', 'jgiroj', '2026-04-15', '26413091', 'tunis', '123', NULL, 'ferzhuheiz', 1, '/uploads/avatars/avatar-24-69d3c2b0056ff.jpg', NULL),
(22, 25, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
CREATE TABLE IF NOT EXISTS `project` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` longtext,
  `status` enum('PLANNED','IN_PROGRESS','DONE','ON_HOLD') DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `project_manager_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `project`
--

INSERT INTO `project` (`id`, `name`, `description`, `status`, `start_date`, `end_date`, `budget`, `project_manager_id`, `created_at`) VALUES
(3, 'gta', 'gta 6 coming soon', 'PLANNED', '2026-02-27', '2026-04-03', 689.00, 13, '2026-02-27 17:46:11'),
(4, 'TEST TRELLO ', 'test maa ayoub', 'PLANNED', '2026-03-27', '2026-03-28', 10.00, 13, '2026-03-01 21:56:14'),
(5, 'trellooo', 'yo', 'PLANNED', '2026-03-24', '2026-03-31', 985.00, 13, '2026-03-01 22:30:48'),
(6, 'gta', 'd', 'IN_PROGRESS', '2026-03-09', '2026-03-25', 5.00, 13, '2026-03-01 22:44:46'),
(7, 'dazk', 'dezbfe', 'PLANNED', '2026-04-01', '2026-04-09', 55.00, 13, '2026-03-02 05:57:43'),
(8, 'Validation', 'oaoao', 'IN_PROGRESS', '2026-04-21', '2026-04-30', 10.00, 12, '2026-04-06 14:43:08');

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
CREATE TABLE IF NOT EXISTS `question` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quiz_id` int NOT NULL,
  `enonce` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B6F7494E853CD175` (`quiz_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`id`, `quiz_id`, `enonce`) VALUES
(7, 8, 'c\'est quoi python');

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

DROP TABLE IF EXISTS `quiz`;
CREATE TABLE IF NOT EXISTS `quiz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `duree` int DEFAULT NULL,
  `formation_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A412FA925200282E` (`formation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quiz`
--

INSERT INTO `quiz` (`id`, `titre`, `duree`, `formation_id`) VALUES
(6, 'hellow', 12, 2),
(7, 'huhu', 10, 1),
(8, 'quiz1', 30, 3);

-- --------------------------------------------------------

--
-- Table structure for table `seance`
--

DROP TABLE IF EXISTS `seance`;
CREATE TABLE IF NOT EXISTS `seance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `formation_id` int NOT NULL,
  `titre` varchar(150) NOT NULL,
  `type` enum('PRESENTIEL','EN_LIGNE') DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `duree_minutes` int DEFAULT NULL,
  `statut` enum('PLANIFIEE','EN_COURS','TERMINEE') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_DF7DFD0E5200282E` (`formation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `seance`
--

INSERT INTO `seance` (`id`, `formation_id`, `titre`, `type`, `date_debut`, `date_fin`, `adresse`, `latitude`, `longitude`, `video_path`, `duree_minutes`, `statut`, `created_at`) VALUES
(1, 1, 'gta', 'EN_LIGNE', '2026-03-02 08:00:00', '2026-03-02 10:00:00', NULL, NULL, NULL, 'hhhhh', NULL, 'PLANIFIEE', '2026-03-02 06:25:19'),
(3, 1, 'javafx', 'EN_LIGNE', '2026-03-02 08:00:00', '2026-03-02 10:00:00', NULL, NULL, NULL, 'https://www.youtube.com/watch?v=H6mfWun73vI&list=RDH6mfWun73vI&start_radio=1', NULL, 'PLANIFIEE', '2026-03-02 07:03:13'),
(4, 1, 'javascript', 'EN_LIGNE', '2026-03-02 08:00:00', '2026-03-02 10:00:00', NULL, NULL, NULL, 'https://youtu.be/IVJs_eSruLg?si=6C_kyhXnCfeY3zQR', NULL, 'PLANIFIEE', '2026-03-02 07:32:44'),
(5, 1, 'gta', 'PRESENTIEL', '2026-03-02 08:00:00', '2026-03-02 10:00:00', 'بو حناش, معتمدية قلعة الأندلس, ولاية أريانة, تونس', 36.96972960762463, 10.12364387512207, NULL, NULL, 'PLANIFIEE', '2026-03-02 07:42:58'),
(6, 2, 'intro', 'EN_LIGNE', '2026-04-06 14:28:00', '2026-04-06 14:31:00', 'https.gr', NULL, NULL, NULL, 15, 'PLANIFIEE', '2026-04-06 12:27:08'),
(7, 3, 'seance 1 en python', 'EN_LIGNE', '2026-04-06 15:51:00', '2026-04-06 19:52:00', 'https.gr ', NULL, NULL, 'uhhhjhjh', 40, 'PLANIFIEE', '2026-04-06 13:52:28');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'OPEN',
  `priority` varchar(50) NOT NULL DEFAULT 'MEDIUM',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` longtext NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E9739508A76ED395` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `subject`, `category`, `status`, `priority`, `created_at`, `description`, `updated_at`) VALUES
(1, 19, 'streak points', 'FEATURE', 'CLOSED', 'MEDIUM', '2026-03-01 01:01:16', '', NULL),
(2, 20, 'hhaihzia', 'FEATURE', 'OPEN', 'MEDIUM', '2026-03-02 13:25:11', '', NULL),
(3, 19, 'intro', NULL, 'CLOSED', 'MEDIUM', '2026-04-06 14:36:34', 'hey there', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `syncs`
--

DROP TABLE IF EXISTS `syncs`;
CREATE TABLE IF NOT EXISTS `syncs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `reason` varchar(50) NOT NULL DEFAULT 'NETWORK',
  `status` varchar(50) NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accepted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_ABF27CDCF624B39D` (`sender_id`),
  KEY `IDX_ABF27CDCCD53EDB6` (`receiver_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `syncs`
--

INSERT INTO `syncs` (`id`, `sender_id`, `receiver_id`, `reason`, `status`, `created_at`, `accepted_at`) VALUES
(1, 19, 13, 'NETWORK', 'ACCEPTED', '2026-03-01 14:32:07', '2026-03-01 13:32:50');

-- --------------------------------------------------------

--
-- Table structure for table `sync_messages`
--

DROP TABLE IF EXISTS `sync_messages`;
CREATE TABLE IF NOT EXISTS `sync_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sync_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `message` longtext NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_9A10A0B8FA50C422` (`sync_id`),
  KEY `IDX_9A10A0B8F624B39D` (`sender_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sync_messages`
--

INSERT INTO `sync_messages` (`id`, `sync_id`, `sender_id`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 13, 'hey there , what\'s up?', 1, '2026-03-01 14:33:11'),
(2, 1, 19, 'im good how you doing?', 1, '2026-03-01 14:33:49'),
(3, 1, 19, 'yo dude', 1, '2026-03-01 18:07:21'),
(4, 1, 13, 'my bad ,i was eating', 1, '2026-03-01 18:49:39'),
(5, 1, 13, 'wanna hang out?', 1, '2026-03-01 18:49:48'),
(6, 1, 19, 'yeah sure', 0, '2026-03-01 21:07:30'),
(7, 1, 19, 'ayoo', 0, '2026-04-03 21:00:05');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

DROP TABLE IF EXISTS `ticket_replies`;
CREATE TABLE IF NOT EXISTS `ticket_replies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_ACCC3E78700047D2` (`ticket_id`),
  KEY `IDX_ACCC3E78A76ED395` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ticket_replies`
--

INSERT INTO `ticket_replies` (`id`, `ticket_id`, `user_id`, `message`, `created_at`) VALUES
(1, 1, 12, 'thank you , we\'re working on it!', '2026-03-01 01:03:39'),
(2, 1, 19, 'thank you', '2026-03-01 01:04:11'),
(3, 1, 12, 'mrigl', '2026-03-01 01:06:11'),
(4, 2, 12, 'hello', '2026-03-02 13:25:45'),
(5, 1, 19, 'hey', '2026-04-03 18:12:15'),
(6, 1, 19, 'hey', '2026-04-03 18:12:27'),
(7, 1, 19, 'hey', '2026-04-03 18:12:28'),
(8, 2, 12, 'what do you want', '2026-04-06 05:04:00'),
(9, 3, 12, 'HELLO', '2026-04-06 14:38:31'),
(10, 3, 12, 'what\'s your issue again ?', '2026-04-16 16:06:41'),
(11, 3, 19, 'Last time an issue happened when i tried to login , but it looks you guys fixed it !', '2026-04-16 16:08:25'),
(12, 3, 12, 'I appreciate your concern and feel free to contact us again if you face an issue like that again.', '2026-04-16 16:10:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('ADMIN','HR','CANDIDATE') DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auth_provider` enum('LOCAL','GOOGLE') DEFAULT NULL,
  `provider_id` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL,
  `failed_attempts` int NOT NULL DEFAULT '0',
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `verification_token` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `active`, `created_at`, `auth_provider`, `provider_id`, `email_verified`, `failed_attempts`, `reset_token`, `reset_token_expires_at`, `verification_token`) VALUES
(1, 'ayoubhamed@esprit.tn', '62d18522b74d75b2a84776c91ba5498377441d4c4af0cea22ca7de9e09475d3a', 'CANDIDATE', 1, '2026-02-09 02:34:01', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(4, 'ayoub@test.com', '97da10d6a688e01e08944d2339eefb163fb5a9e066641c70f2f377f2173b36b8', 'CANDIDATE', 1, '2026-02-09 04:22:56', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(5, 'ayo@test.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'ADMIN', 1, '2026-02-09 05:59:22', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(7, 'adem@test.com', '2ac9a6746aca543af8dff39894cfe8173afba21eb01c6fae33d52947222855ef', 'CANDIDATE', 1, '2026-02-10 14:17:11', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(8, 'omar', 'f6e0a1e2ac41945a9aa7ff8a8aaa0cebc12a3bcc981a929ad5cf810a090e11ae', 'CANDIDATE', 1, '2026-02-11 18:24:04', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(10, 'meniar@lll.com', 'ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb', 'CANDIDATE', 1, '2026-02-15 21:56:37', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(11, 'ayo@test.fr', '5fdf54dc68d6348b46c269d4c190f407d74de4b657b3c88a6b96750d7cc3b5bd', 'ADMIN', 0, '2026-02-15 22:21:24', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(12, 'meniar@esp.tn', '5fdf54dc68d6348b46c269d4c190f407d74de4b657b3c88a6b96750d7cc3b5bd', 'ADMIN', 1, '2026-02-15 22:50:18', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(13, 'amen@disc.lol', '5fdf54dc68d6348b46c269d4c190f407d74de4b657b3c88a6b96750d7cc3b5bd', 'HR', 1, '2026-02-15 23:23:11', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(14, 'skan@nafti.tn', '380e75d7be969ac599b85be1a516618aafd679ba11922949241885802e7b37bb', 'CANDIDATE', 1, '2026-02-16 09:47:48', 'LOCAL', NULL, 1, 3, NULL, NULL, NULL),
(15, 'ayoub@tst.tn', '5fdf54dc68d6348b46c269d4c190f407d74de4b657b3c88a6b96750d7cc3b5bd', 'CANDIDATE', 1, '2026-02-16 11:24:25', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(16, 'ayoub@gmail.com', '5fdf54dc68d6348b46c269d4c190f407d74de4b657b3c88a6b96750d7cc3b5bd', 'CANDIDATE', 1, '2026-02-16 15:44:22', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(17, 'omar@hamdi.tn', '87e5c999eb63fa472d4498109348861923598f1fe7cb36382def69071bb9df5a', 'CANDIDATE', 1, '2026-02-26 23:42:52', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(19, 'ayoubhamed111@gmail.com', '5fdf54dc68d6348b46c269d4c190f407d74de4b657b3c88a6b96750d7cc3b5bd', 'CANDIDATE', 1, '2026-02-27 17:13:22', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(20, 'ayoublopez70@gmail.com', '5fdf54dc68d6348b46c269d4c190f407d74de4b657b3c88a6b96750d7cc3b5bd', 'CANDIDATE', 1, '2026-03-02 13:09:28', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(21, 'rh1@gmail.com', '5fdf54dc68d6348b46c269d4c190f407d74de4b657b3c88a6b96750d7cc3b5bd', 'HR', 1, '2026-04-03 20:45:41', 'LOCAL', NULL, 1, 1, NULL, NULL, NULL),
(23, 'fiatsiena961@gmail.com', NULL, 'CANDIDATE', 1, '2026-04-06 04:25:25', 'GOOGLE', '114438245025170742321', 1, 0, NULL, NULL, NULL),
(24, 'midouepic@gmail.com', '5fdf54dc68d6348b46c269d4c190f407d74de4b657b3c88a6b96750d7cc3b5bd', 'CANDIDATE', 0, '2026-04-06 14:24:03', 'LOCAL', NULL, 1, 0, NULL, NULL, NULL),
(25, 'fatma@gmail.com', 'd3524027de6b33fb8c30362556edc0b7faa0e71b17f4177070a08138eac3fa3d', 'HR', 1, '2026-04-07 14:09:23', 'LOCAL', NULL, 0, 0, NULL, '2026-04-07 14:19:23', '867513');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `FK_B5F1AFE5166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  ADD CONSTRAINT `FK_B5F1AFE58C03F15C` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `FK_F7C966F053C674EE` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`),
  ADD CONSTRAINT `FK_F7C966F0A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `FK_78D2C14053C674EE` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`),
  ADD CONSTRAINT `FK_78D2C140A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `choix`
--
ALTER TABLE `choix`
  ADD CONSTRAINT `FK_4F4880911E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`);

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `FK_3BAE0AA7876C4DDA` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `event_comment`
--
ALTER TABLE `event_comment`
  ADD CONSTRAINT `FK_1123FBC371F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_1123FBC3A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `event_like`
--
ALTER TABLE `event_like`
  ADD CONSTRAINT `FK_B3A80C1871F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_B3A80C18A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `event_participation`
--
ALTER TABLE `event_participation`
  ADD CONSTRAINT `FK_8F0C52E371F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_8F0C52E3A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `FK_3A7526823E030ACD` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`);

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `FK_8B308530A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `FK_B6F7494E853CD175` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`);

--
-- Constraints for table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `FK_A412FA925200282E` FOREIGN KEY (`formation_id`) REFERENCES `formation` (`id`);

--
-- Constraints for table `seance`
--
ALTER TABLE `seance`
  ADD CONSTRAINT `FK_DF7DFD0E5200282E` FOREIGN KEY (`formation_id`) REFERENCES `formation` (`id`);

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `FK_E9739508A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `syncs`
--
ALTER TABLE `syncs`
  ADD CONSTRAINT `FK_ABF27CDCCD53EDB6` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `FK_ABF27CDCF624B39D` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sync_messages`
--
ALTER TABLE `sync_messages`
  ADD CONSTRAINT `FK_9A10A0B8F624B39D` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `FK_9A10A0B8FA50C422` FOREIGN KEY (`sync_id`) REFERENCES `syncs` (`id`);

--
-- Constraints for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `FK_ACCC3E78700047D2` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`),
  ADD CONSTRAINT `FK_ACCC3E78A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
