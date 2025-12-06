-- MySQL dump 10.13  Distrib 9.5.0, for macos15.4 (arm64)
--
-- Host: 127.0.0.1    Database: app_couture_new
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.28-MariaDB

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
-- Table structure for table `abonnement`
--

DROP TABLE IF EXISTS `abonnement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abonnement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_abonnement_id` int(11) DEFAULT NULL,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `etat` varchar(255) NOT NULL,
  `date_fin` datetime NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_351268BBA20C84BB` (`module_abonnement_id`),
  KEY `IDX_351268BBA4AEAFEA` (`entreprise_id`),
  KEY `IDX_351268BBB03A8386` (`created_by_id`),
  KEY `IDX_351268BB896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_351268BB896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_351268BBA20C84BB` FOREIGN KEY (`module_abonnement_id`) REFERENCES `module_abonnement` (`id`),
  CONSTRAINT `FK_351268BBA4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_351268BBB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abonnement`
--

LOCK TABLES `abonnement` WRITE;
/*!40000 ALTER TABLE `abonnement` DISABLE KEYS */;
INSERT INTO `abonnement` VALUES (1,1,1,NULL,NULL,'actif','2025-12-21 17:42:36','gratuit','2025-11-21 17:42:36','2025-11-21 17:42:36',1);
/*!40000 ALTER TABLE `abonnement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `boutique`
--

DROP TABLE IF EXISTS `boutique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `boutique` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `contact` varchar(255) NOT NULL,
  `situation` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A1223C54A4AEAFEA` (`entreprise_id`),
  KEY `IDX_A1223C54B03A8386` (`created_by_id`),
  KEY `IDX_A1223C54896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_A1223C54896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_A1223C54A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_A1223C54B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `boutique`
--

LOCK TABLES `boutique` WRITE;
/*!40000 ALTER TABLE `boutique` DISABLE KEYS */;
INSERT INTO `boutique` VALUES (1,1,1,1,'KNH boutique','+225 0123456789','Avenue 12, Quartier Plateau, Abidjan','2025-11-21 17:49:13','2025-11-21 17:49:13',1),(2,1,1,1,'Papson boutique','+225 0123456789','Avenue 12, Quartier Plateau, Abidjan','2025-11-21 17:50:05','2025-11-21 17:50:05',0);
/*!40000 ALTER TABLE `boutique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caisse`
--

DROP TABLE IF EXISTS `caisse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caisse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `montant` varchar(255) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  `discr` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B2A353C8A4AEAFEA` (`entreprise_id`),
  KEY `IDX_B2A353C8B03A8386` (`created_by_id`),
  KEY `IDX_B2A353C8896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_B2A353C8896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_B2A353C8A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_B2A353C8B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caisse`
--

LOCK TABLES `caisse` WRITE;
/*!40000 ALTER TABLE `caisse` DISABLE KEYS */;
INSERT INTO `caisse` VALUES (1,1,1,1,'95000','CAIS251121174913001','boutique','2025-11-21 17:49:13','2025-11-21 22:58:06',1,'caisseboutique'),(2,1,1,1,'0','CAIS251121175005001','boutique','2025-11-21 17:50:05','2025-11-21 17:50:05',1,'caisseboutique'),(3,1,1,1,'20000','CAIS251124120841009','caisse_succursale','2025-11-24 12:08:41','2025-11-24 12:08:41',1,'caissesuccursale');
/*!40000 ALTER TABLE `caisse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caisse_boutique`
--

DROP TABLE IF EXISTS `caisse_boutique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caisse_boutique` (
  `id` int(11) NOT NULL,
  `boutique_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_761557CCAB677BE6` (`boutique_id`),
  CONSTRAINT `FK_761557CCAB677BE6` FOREIGN KEY (`boutique_id`) REFERENCES `boutique` (`id`),
  CONSTRAINT `FK_761557CCBF396750` FOREIGN KEY (`id`) REFERENCES `caisse` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caisse_boutique`
--

LOCK TABLES `caisse_boutique` WRITE;
/*!40000 ALTER TABLE `caisse_boutique` DISABLE KEYS */;
INSERT INTO `caisse_boutique` VALUES (1,1),(2,2);
/*!40000 ALTER TABLE `caisse_boutique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caisse_succursale`
--

DROP TABLE IF EXISTS `caisse_succursale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caisse_succursale` (
  `id` int(11) NOT NULL,
  `succursale_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DFF07E66C4166807` (`succursale_id`),
  CONSTRAINT `FK_DFF07E66BF396750` FOREIGN KEY (`id`) REFERENCES `caisse` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_DFF07E66C4166807` FOREIGN KEY (`succursale_id`) REFERENCES `surccursale` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caisse_succursale`
--

LOCK TABLES `caisse_succursale` WRITE;
/*!40000 ALTER TABLE `caisse_succursale` DISABLE KEYS */;
INSERT INTO `caisse_succursale` VALUES (3,1);
/*!40000 ALTER TABLE `caisse_succursale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorie_mesure`
--

DROP TABLE IF EXISTS `categorie_mesure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorie_mesure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D93DC449A4AEAFEA` (`entreprise_id`),
  KEY `IDX_D93DC449B03A8386` (`created_by_id`),
  KEY `IDX_D93DC449896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_D93DC449896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_D93DC449A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_D93DC449B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorie_mesure`
--

LOCK TABLES `categorie_mesure` WRITE;
/*!40000 ALTER TABLE `categorie_mesure` DISABLE KEYS */;
INSERT INTO `categorie_mesure` VALUES (1,NULL,NULL,NULL,'épaule','2025-01-01 00:00:00',NULL,1),(2,NULL,NULL,NULL,'poitrine','2025-01-01 00:00:00',NULL,1),(3,NULL,NULL,NULL,'taille','2025-01-01 00:00:00',NULL,1),(4,NULL,NULL,NULL,'hanches','2025-01-01 00:00:00',NULL,1),(5,NULL,NULL,NULL,'longueur dos','2025-01-01 00:00:00',NULL,1),(6,NULL,NULL,NULL,'longueur manche','2025-01-01 00:00:00',NULL,1),(7,NULL,NULL,NULL,'tour de manche','2025-01-01 00:00:00',NULL,1),(8,NULL,NULL,NULL,'encolure','2025-01-01 00:00:00',NULL,1),(9,NULL,NULL,NULL,'longueur pantalon','2025-01-01 00:00:00',NULL,1),(10,NULL,NULL,NULL,'tour de cuisse','2025-01-01 00:00:00',NULL,1),(11,NULL,NULL,NULL,'tour de genou','2025-01-01 00:00:00',NULL,1),(12,NULL,NULL,NULL,'tour de mollet','2025-01-01 00:00:00',NULL,1),(13,NULL,NULL,NULL,'tour de cheville','2025-01-01 00:00:00',NULL,1),(14,NULL,NULL,NULL,'fourche','2025-01-01 00:00:00',NULL,1),(15,NULL,NULL,NULL,'ceinture','2025-01-01 00:00:00',NULL,1),(16,NULL,NULL,NULL,'bas','2025-01-01 00:00:00',NULL,1),(17,NULL,NULL,NULL,'longueur jupe','2025-01-01 00:00:00',NULL,1),(18,NULL,NULL,NULL,'tour de jupe','2025-01-01 00:00:00',NULL,1),(19,NULL,NULL,NULL,'hauteur poitrine','2025-01-01 00:00:00',NULL,1),(20,NULL,NULL,NULL,'largeur dos','2025-01-01 00:00:00',NULL,1),(21,NULL,NULL,NULL,'longueur entrejambe','2025-01-01 00:00:00',NULL,1),(22,NULL,NULL,NULL,'tour de bras','2025-01-01 00:00:00',NULL,1),(23,NULL,NULL,NULL,'tour de cou','2025-01-01 00:00:00',NULL,1),(24,NULL,NULL,NULL,'tour de poignet','2025-01-01 00:00:00',NULL,1),(25,NULL,NULL,NULL,'hauteur taille','2025-01-01 00:00:00',NULL,1);
/*!40000 ALTER TABLE `categorie_mesure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorie_type_mesure`
--

DROP TABLE IF EXISTS `categorie_type_mesure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorie_type_mesure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_mesure_id` int(11) DEFAULT NULL,
  `categorie_mesure_id` int(11) DEFAULT NULL,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A3322341F76D63EA` (`type_mesure_id`),
  KEY `IDX_A3322341EEF42DFD` (`categorie_mesure_id`),
  KEY `IDX_A3322341A4AEAFEA` (`entreprise_id`),
  KEY `IDX_A3322341B03A8386` (`created_by_id`),
  KEY `IDX_A3322341896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_A3322341896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_A3322341A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_A3322341B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_A3322341EEF42DFD` FOREIGN KEY (`categorie_mesure_id`) REFERENCES `categorie_mesure` (`id`),
  CONSTRAINT `FK_A3322341F76D63EA` FOREIGN KEY (`type_mesure_id`) REFERENCES `type_mesure` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorie_type_mesure`
--

LOCK TABLES `categorie_type_mesure` WRITE;
/*!40000 ALTER TABLE `categorie_type_mesure` DISABLE KEYS */;
INSERT INTO `categorie_type_mesure` VALUES (74,1,1,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(75,1,2,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(76,1,3,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(77,1,5,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(78,1,6,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(79,1,9,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(80,1,15,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(81,2,1,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(82,2,2,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(83,2,3,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(84,2,6,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(85,2,8,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(86,2,23,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(87,3,3,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(88,3,4,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(89,3,9,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(90,3,10,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(91,3,14,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(92,3,15,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(93,3,21,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(94,7,1,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(95,7,2,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(96,7,3,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(97,7,4,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(98,7,17,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(99,7,19,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(100,8,3,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(101,8,4,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(102,8,17,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(103,8,18,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(104,8,15,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(105,14,1,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(106,14,2,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(107,14,5,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(108,14,6,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(109,14,20,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(110,15,1,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(111,15,2,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(112,15,5,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(113,15,6,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(114,15,19,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(115,16,1,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(116,16,2,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(117,16,3,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(118,16,4,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(119,16,17,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(120,16,19,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(121,19,1,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(122,19,2,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(123,19,6,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(124,19,8,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(125,24,3,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(126,24,4,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(127,24,9,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(128,24,10,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(129,24,14,1,1,NULL,'2025-11-21 17:42:36',NULL,1),(130,24,15,1,1,NULL,'2025-11-21 17:42:36',NULL,1);
/*!40000 ALTER TABLE `categorie_type_mesure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client`
--

DROP TABLE IF EXISTS `client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `surccursale_id` int(11) DEFAULT NULL,
  `photo_id` int(11) DEFAULT NULL,
  `boutique_id` int(11) DEFAULT NULL,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `numero` varchar(255) DEFAULT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_C7440455F55AE19E` (`numero`),
  KEY `IDX_C744045558AAF78E` (`surccursale_id`),
  KEY `IDX_C74404557E9E4C8C` (`photo_id`),
  KEY `IDX_C7440455AB677BE6` (`boutique_id`),
  KEY `IDX_C7440455A4AEAFEA` (`entreprise_id`),
  KEY `IDX_C7440455B03A8386` (`created_by_id`),
  KEY `IDX_C7440455896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_C744045558AAF78E` FOREIGN KEY (`surccursale_id`) REFERENCES `surccursale` (`id`),
  CONSTRAINT `FK_C74404557E9E4C8C` FOREIGN KEY (`photo_id`) REFERENCES `param_fichier` (`id`),
  CONSTRAINT `FK_C7440455896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_C7440455A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_C7440455AB677BE6` FOREIGN KEY (`boutique_id`) REFERENCES `boutique` (`id`),
  CONSTRAINT `FK_C7440455B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client`
--

LOCK TABLES `client` WRITE;
/*!40000 ALTER TABLE `client` DISABLE KEYS */;
INSERT INTO `client` VALUES (1,NULL,12,1,1,1,1,'+225 0123456789','Kouassi','Yao Jean','2025-11-21 21:43:10','2025-11-21 21:43:10',1);
/*!40000 ALTER TABLE `client` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entre_stock`
--

DROP TABLE IF EXISTS `entre_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entre_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entreprise_id` int(11) DEFAULT NULL,
  `boutique_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6F52C37CA4AEAFEA` (`entreprise_id`),
  KEY `IDX_6F52C37CAB677BE6` (`boutique_id`),
  KEY `IDX_6F52C37CB03A8386` (`created_by_id`),
  KEY `IDX_6F52C37C896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_6F52C37C896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_6F52C37CA4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_6F52C37CAB677BE6` FOREIGN KEY (`boutique_id`) REFERENCES `boutique` (`id`),
  CONSTRAINT `FK_6F52C37CB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entre_stock`
--

LOCK TABLES `entre_stock` WRITE;
/*!40000 ALTER TABLE `entre_stock` DISABLE KEYS */;
INSERT INTO `entre_stock` VALUES (1,1,1,1,1,'2025-11-21 18:08:19',2,'Entree','2025-11-21 18:07:54','2025-11-21 18:07:54',1),(2,1,1,1,1,'2025-11-21 18:08:19',2,'Entree','2025-11-21 18:08:19','2025-11-21 18:08:19',1),(3,1,1,1,1,'2025-11-21 18:08:19',2,'Entree','2025-11-21 18:09:01','2025-11-21 18:09:01',1),(4,1,1,1,1,'2025-11-21 18:08:19',2,'Entree','2025-11-21 18:14:57','2025-11-21 18:14:57',1),(5,1,1,1,1,'2025-11-21 18:08:19',2,'Sortie','2025-11-21 20:45:27','2025-11-21 20:45:27',1),(6,1,1,1,1,'2025-11-21 18:08:19',50,'Entree','2025-11-21 22:21:24','2025-11-21 22:21:24',1);
/*!40000 ALTER TABLE `entre_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entreprise`
--

DROP TABLE IF EXISTS `entreprise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entreprise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `logo_id` int(11) DEFAULT NULL,
  `pays_id` int(11) NOT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `numero` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D19FA60F98F144A` (`logo_id`),
  KEY `IDX_D19FA60A6E44244` (`pays_id`),
  KEY `IDX_D19FA60B03A8386` (`created_by_id`),
  KEY `IDX_D19FA60896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_D19FA60896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_D19FA60A6E44244` FOREIGN KEY (`pays_id`) REFERENCES `pays` (`id`),
  CONSTRAINT `FK_D19FA60B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_D19FA60F98F144A` FOREIGN KEY (`logo_id`) REFERENCES `param_fichier` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entreprise`
--

LOCK TABLES `entreprise` WRITE;
/*!40000 ALTER TABLE `entreprise` DISABLE KEYS */;
INSERT INTO `entreprise` VALUES (1,NULL,1,NULL,NULL,'ATELIYA','+2250704314164','supports@ateliya.com','2025-11-21 17:42:36','2025-11-21 17:42:36',1);
/*!40000 ALTER TABLE `entreprise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facture`
--

DROP TABLE IF EXISTS `facture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facture` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `signature_id` int(11) DEFAULT NULL,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `date_retrait` datetime NOT NULL,
  `date_depot` datetime NOT NULL,
  `avance` varchar(255) DEFAULT NULL,
  `montant_total` varchar(255) DEFAULT NULL,
  `remise` varchar(255) DEFAULT NULL,
  `reste_argent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FE86641019EB6921` (`client_id`),
  KEY `IDX_FE866410ED61183A` (`signature_id`),
  KEY `IDX_FE866410A4AEAFEA` (`entreprise_id`),
  KEY `IDX_FE866410B03A8386` (`created_by_id`),
  KEY `IDX_FE866410896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_FE86641019EB6921` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`),
  CONSTRAINT `FK_FE866410896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_FE866410A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_FE866410B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_FE866410ED61183A` FOREIGN KEY (`signature_id`) REFERENCES `param_fichier` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facture`
--

LOCK TABLES `facture` WRITE;
/*!40000 ALTER TABLE `facture` DISABLE KEYS */;
INSERT INTO `facture` VALUES (1,1,13,1,1,1,'2025-02-01 14:00:00','2025-11-24 12:34:03','20000','50000','5000','30000','2025-11-24 12:34:03','2025-11-24 12:34:03',1),(2,1,14,1,1,1,'2025-02-01 14:00:00','2025-11-24 13:10:37','20000','50000','5000','30000','2025-11-24 13:10:37','2025-11-24 13:10:37',1);
/*!40000 ALTER TABLE `facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ligne_entre`
--

DROP TABLE IF EXISTS `ligne_entre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ligne_entre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modele_id` int(11) DEFAULT NULL,
  `entre_stock_id` int(11) DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3C8C2B0FAC14B70A` (`modele_id`),
  KEY `IDX_3C8C2B0F5E18E03E` (`entre_stock_id`),
  CONSTRAINT `FK_3C8C2B0F5E18E03E` FOREIGN KEY (`entre_stock_id`) REFERENCES `entre_stock` (`id`),
  CONSTRAINT `FK_3C8C2B0FAC14B70A` FOREIGN KEY (`modele_id`) REFERENCES `modele_boutique` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ligne_entre`
--

LOCK TABLES `ligne_entre` WRITE;
/*!40000 ALTER TABLE `ligne_entre` DISABLE KEYS */;
INSERT INTO `ligne_entre` VALUES (1,1,1,2),(2,1,2,2),(3,1,3,2),(4,1,4,2),(5,1,5,2),(6,1,6,50);
/*!40000 ALTER TABLE `ligne_entre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ligne_mesure`
--

DROP TABLE IF EXISTS `ligne_mesure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ligne_mesure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categorie_mesure_id` int(11) DEFAULT NULL,
  `mesure_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `taille` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_79C1F236EEF42DFD` (`categorie_mesure_id`),
  KEY `IDX_79C1F23643AB22FA` (`mesure_id`),
  KEY `IDX_79C1F236B03A8386` (`created_by_id`),
  KEY `IDX_79C1F236896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_79C1F23643AB22FA` FOREIGN KEY (`mesure_id`) REFERENCES `mesure` (`id`),
  CONSTRAINT `FK_79C1F236896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_79C1F236B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_79C1F236EEF42DFD` FOREIGN KEY (`categorie_mesure_id`) REFERENCES `categorie_mesure` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ligne_mesure`
--

LOCK TABLES `ligne_mesure` WRITE;
/*!40000 ALTER TABLE `ligne_mesure` DISABLE KEYS */;
INSERT INTO `ligne_mesure` VALUES (1,1,1,NULL,NULL,'85cm',NULL,NULL,1),(2,2,1,NULL,NULL,'120cm',NULL,NULL,1),(3,3,2,NULL,NULL,'90cm',NULL,NULL,1),(4,4,2,NULL,NULL,'42cm',NULL,NULL,1);
/*!40000 ALTER TABLE `ligne_mesure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ligne_module`
--

DROP TABLE IF EXISTS `ligne_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ligne_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_abonnement_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `quantite` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2AFEBA6EA20C84BB` (`module_abonnement_id`),
  KEY `IDX_2AFEBA6EB03A8386` (`created_by_id`),
  KEY `IDX_2AFEBA6E896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_2AFEBA6E896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_2AFEBA6EA20C84BB` FOREIGN KEY (`module_abonnement_id`) REFERENCES `module_abonnement` (`id`),
  CONSTRAINT `FK_2AFEBA6EB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ligne_module`
--

LOCK TABLES `ligne_module` WRITE;
/*!40000 ALTER TABLE `ligne_module` DISABLE KEYS */;
INSERT INTO `ligne_module` VALUES (1,1,NULL,NULL,'SMS','sddd','1',NULL,NULL,1),(2,1,NULL,NULL,'USER','ccc','3',NULL,NULL,1),(3,1,NULL,NULL,'SUCCURSALE','dsds','3',NULL,NULL,1),(4,1,NULL,NULL,'BOUTIQUE','sss','3',NULL,NULL,1),(5,3,NULL,NULL,'SMS','sddd','1',NULL,NULL,1),(6,3,NULL,NULL,'USER','ccc','3',NULL,NULL,1),(7,3,NULL,NULL,'SUCCURSALE','dsds','3',NULL,NULL,1),(8,3,NULL,NULL,'BOUTIQUE','sss','3',NULL,NULL,1);
/*!40000 ALTER TABLE `ligne_module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ligne_reservation`
--

DROP TABLE IF EXISTS `ligne_reservation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ligne_reservation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) DEFAULT NULL,
  `modele_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  `avance_modele` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_212D8962B83297E7` (`reservation_id`),
  KEY `IDX_212D8962AC14B70A` (`modele_id`),
  KEY `IDX_212D8962B03A8386` (`created_by_id`),
  KEY `IDX_212D8962896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_212D8962896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_212D8962AC14B70A` FOREIGN KEY (`modele_id`) REFERENCES `modele_boutique` (`id`),
  CONSTRAINT `FK_212D8962B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_212D8962B83297E7` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ligne_reservation`
--

LOCK TABLES `ligne_reservation` WRITE;
/*!40000 ALTER TABLE `ligne_reservation` DISABLE KEYS */;
INSERT INTO `ligne_reservation` VALUES (1,1,1,1,1,2,NULL,'2025-11-21 22:39:57','2025-11-21 22:39:57',1),(2,2,1,1,1,2,NULL,'2025-11-21 22:40:44','2025-11-21 22:40:44',1),(3,3,1,1,1,2,NULL,'2025-11-21 22:52:26','2025-11-21 22:52:26',1),(4,4,1,1,1,2,NULL,'2025-11-21 22:53:45','2025-11-21 22:53:45',1),(5,5,1,1,1,2,NULL,'2025-11-21 22:58:06','2025-11-21 22:58:06',1);
/*!40000 ALTER TABLE `ligne_reservation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messenger_messages`
--

LOCK TABLES `messenger_messages` WRITE;
/*!40000 ALTER TABLE `messenger_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messenger_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mesure`
--

DROP TABLE IF EXISTS `mesure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mesure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facture_id` int(11) DEFAULT NULL,
  `photo_modele_id` int(11) DEFAULT NULL,
  `photo_pagne_id` int(11) DEFAULT NULL,
  `type_mesure_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `montant` varchar(255) DEFAULT NULL,
  `remise` varchar(255) DEFAULT NULL,
  `etat` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5F1B6E707F2DEE08` (`facture_id`),
  KEY `IDX_5F1B6E70A1EAE12B` (`photo_modele_id`),
  KEY `IDX_5F1B6E70D184FBFF` (`photo_pagne_id`),
  KEY `IDX_5F1B6E70F76D63EA` (`type_mesure_id`),
  KEY `IDX_5F1B6E70B03A8386` (`created_by_id`),
  KEY `IDX_5F1B6E70896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_5F1B6E707F2DEE08` FOREIGN KEY (`facture_id`) REFERENCES `facture` (`id`),
  CONSTRAINT `FK_5F1B6E70896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_5F1B6E70A1EAE12B` FOREIGN KEY (`photo_modele_id`) REFERENCES `param_fichier` (`id`),
  CONSTRAINT `FK_5F1B6E70B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_5F1B6E70D184FBFF` FOREIGN KEY (`photo_pagne_id`) REFERENCES `param_fichier` (`id`),
  CONSTRAINT `FK_5F1B6E70F76D63EA` FOREIGN KEY (`type_mesure_id`) REFERENCES `type_mesure` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesure`
--

LOCK TABLES `mesure` WRITE;
/*!40000 ALTER TABLE `mesure` DISABLE KEYS */;
INSERT INTO `mesure` VALUES (1,2,15,16,1,NULL,NULL,NULL,'25000','2000','En cours',NULL,NULL,1),(2,2,17,18,2,NULL,NULL,NULL,'20000','0','En cours',NULL,NULL,1);
/*!40000 ALTER TABLE `mesure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modele`
--

DROP TABLE IF EXISTS `modele`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modele` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `photo_id` int(11) DEFAULT NULL,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `quantite_globale` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_100285587E9E4C8C` (`photo_id`),
  KEY `IDX_10028558A4AEAFEA` (`entreprise_id`),
  KEY `IDX_10028558B03A8386` (`created_by_id`),
  KEY `IDX_10028558896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_100285587E9E4C8C` FOREIGN KEY (`photo_id`) REFERENCES `param_fichier` (`id`),
  CONSTRAINT `FK_10028558896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_10028558A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_10028558B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modele`
--

LOCK TABLES `modele` WRITE;
/*!40000 ALTER TABLE `modele` DISABLE KEYS */;
INSERT INTO `modele` VALUES (1,10,1,1,1,'Robe Wax Élégante',49,'2025-11-21 17:54:20','2025-11-21 17:54:20',1),(2,11,1,1,1,'Robe Wax simple',10,'2025-11-21 17:55:02','2025-11-21 17:55:02',1);
/*!40000 ALTER TABLE `modele` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modele_boutique`
--

DROP TABLE IF EXISTS `modele_boutique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modele_boutique` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modele_id` int(11) DEFAULT NULL,
  `boutique_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  `prix` varchar(255) NOT NULL,
  `taille` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C9FA0256AC14B70A` (`modele_id`),
  KEY `IDX_C9FA0256AB677BE6` (`boutique_id`),
  KEY `IDX_C9FA0256B03A8386` (`created_by_id`),
  KEY `IDX_C9FA0256896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_C9FA0256896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_C9FA0256AB677BE6` FOREIGN KEY (`boutique_id`) REFERENCES `boutique` (`id`),
  CONSTRAINT `FK_C9FA0256AC14B70A` FOREIGN KEY (`modele_id`) REFERENCES `modele` (`id`),
  CONSTRAINT `FK_C9FA0256B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modele_boutique`
--

LOCK TABLES `modele_boutique` WRITE;
/*!40000 ALTER TABLE `modele_boutique` DISABLE KEYS */;
INSERT INTO `modele_boutique` VALUES (1,1,1,1,1,42,'15000','M','2025-11-21 17:58:36','2025-11-21 17:58:36',1),(2,2,1,1,1,3,'15000','M','2025-11-21 17:58:59','2025-11-21 17:58:59',1);
/*!40000 ALTER TABLE `modele_boutique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_abonnement`
--

DROP TABLE IF EXISTS `module_abonnement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_abonnement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `etat` tinyint(1) NOT NULL,
  `description` longtext NOT NULL,
  `montant` varchar(255) NOT NULL,
  `duree` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A0F74326F55AE19E` (`numero`),
  KEY `IDX_A0F74326B03A8386` (`created_by_id`),
  KEY `IDX_A0F74326896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_A0F74326896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_A0F74326B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module_abonnement`
--

LOCK TABLES `module_abonnement` WRITE;
/*!40000 ALTER TABLE `module_abonnement` DISABLE KEYS */;
INSERT INTO `module_abonnement` VALUES (1,NULL,NULL,1,'jhlkjshjqklhq','100','1','FREE',1,NULL,NULL,0),(3,NULL,NULL,1,'jhlkjshjqklhq','200','1','CLASSIQUE',2,NULL,NULL,0);
/*!40000 ALTER TABLE `module_abonnement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entreprise_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `etat` tinyint(1) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `libelle` longtext NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BF5476CAA4AEAFEA` (`entreprise_id`),
  KEY `IDX_BF5476CAA76ED395` (`user_id`),
  KEY `IDX_BF5476CAB03A8386` (`created_by_id`),
  KEY `IDX_BF5476CA896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_BF5476CA896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_BF5476CAA4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_BF5476CAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_BF5476CAB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification`
--

LOCK TABLES `notification` WRITE;
/*!40000 ALTER TABLE `notification` DISABLE KEYS */;
INSERT INTO `notification` VALUES (1,1,1,NULL,NULL,1,'Bienvenue','Bienvenue dans notre application','2025-11-21 17:42:36','2025-11-21 17:42:36',1),(2,1,1,1,1,1,'Vente - KNH boutique','Bonjour konatenhamed@gmail.com,\n\nNous vous informons qu\'une nouvelle vente vient d\'être enregistrée dans la boutique **KNH boutique**.\n\n- Montant : 15 000 FCFA\n- Effectué par : konatenhamed@gmail.com\n- Date : 21/11/2025 22:16\n\nCordialement,\nVotre application de gestion.','2025-11-21 22:16:02','2025-11-21 22:16:02',1),(3,1,1,1,1,1,'Vente - KNH boutique','Bonjour konatenhamed@gmail.com,\n\nNous vous informons qu\'une nouvelle vente vient d\'être enregistrée dans la boutique **KNH boutique**.\n\n- Montant : 15 000 FCFA\n- Effectué par : konatenhamed@gmail.com\n- Date : 21/11/2025 22:23\n\nCordialement,\nVotre application de gestion.','2025-11-21 22:23:06','2025-11-21 22:23:06',1),(4,1,1,1,1,1,'Réservation - KNH boutique','Bonjour konatenhamed@gmail.com,\n\nNous vous informons qu\'une nouvelle réservation vient d\'être enregistrée dans la boutique **KNH boutique**.\n\n- Client : Kouassi Yao Jean\n- Montant total : 15 000 FCFA\n- Avance versée : 10 000 FCFA\n- Reste à payer : 5 000 FCFA\n- Quantité totale : 2 article(s)\n- Date de retrait prévue : 24/11/2025\n- Effectué par : konatenhamed@gmail.com\n- Date de réservation : 21/11/2025 22:53\n\nCordialement,\nVotre application de gestion.','2025-11-21 22:53:45','2025-11-21 22:53:45',1),(5,1,1,1,1,1,'Réservation - KNH boutique','Bonjour konatenhamed@gmail.com,\n\nNous vous informons qu\'une nouvelle réservation vient d\'être enregistrée dans la boutique **KNH boutique**.\n\n- Client : Kouassi Yao Jean\n- Montant total : 15 000 FCFA\n- Avance versée : 10 000 FCFA\n- Reste à payer : 5 000 FCFA\n- Quantité totale : 2 article(s)\n- Date de retrait prévue : 24/11/2025\n- Effectué par : konatenhamed@gmail.com\n- Date de réservation : 21/11/2025 22:58\n\nCordialement,\nVotre application de gestion.','2025-11-21 22:58:06','2025-11-21 22:58:06',1),(6,1,1,1,1,1,'Vente - KNH boutique','Bonjour konatenhamed@gmail.com,\n\nNous vous informons qu\'une nouvelle vente vient d\'être enregistrée dans la boutique **KNH boutique**.\n\n- Montant : 15 000 FCFA\n- Effectué par : konatenhamed@gmail.com\n- Date : 24/11/2025 10:04\n\nCordialement,\nVotre application de gestion.','2025-11-24 10:04:49','2025-11-24 10:04:49',1),(7,1,1,1,1,1,'Paiement facture - ','Bonjour konatenhamed@gmail.com,\n\nNous vous informons qu\'un nouveau paiement vient d\'être enregistré dans la succursale **N/A**.\n\n- Montant : 20 000 FCFA\n- Effectué par : konatenhamed@gmail.com\n- Date : 24/11/2025 12:22\n\nCordialement,\nVotre application de gestion.','2025-11-24 12:22:59','2025-11-24 12:22:59',1),(8,1,1,1,1,1,'Paiement facture - ','Bonjour konatenhamed@gmail.com,\n\nNous vous informons qu\'un nouveau paiement vient d\'être enregistré dans la succursale **N/A**.\n\n- Montant : 20 000 FCFA\n- Effectué par : konatenhamed@gmail.com\n- Date : 24/11/2025 12:34\n\nCordialement,\nVotre application de gestion.','2025-11-24 12:34:03','2025-11-24 12:34:03',1),(9,1,1,1,1,1,'Paiement facture - ','Bonjour konatenhamed@gmail.com,\n\nNous vous informons qu\'un nouveau paiement vient d\'être enregistré dans la succursale **N/A**.\n\n- Montant : 20 000 FCFA\n- Effectué par : konatenhamed@gmail.com\n- Date : 24/11/2025 13:10\n\nCordialement,\nVotre application de gestion.','2025-11-24 13:10:37','2025-11-24 13:10:37',1);
/*!40000 ALTER TABLE `notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `operateur`
--

DROP TABLE IF EXISTS `operateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operateur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pays_id` int(11) DEFAULT NULL,
  `photo_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `actif` tinyint(1) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B4B7F99DA6E44244` (`pays_id`),
  KEY `IDX_B4B7F99D7E9E4C8C` (`photo_id`),
  KEY `IDX_B4B7F99DB03A8386` (`created_by_id`),
  KEY `IDX_B4B7F99D896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_B4B7F99D7E9E4C8C` FOREIGN KEY (`photo_id`) REFERENCES `param_fichier` (`id`),
  CONSTRAINT `FK_B4B7F99D896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_B4B7F99DA6E44244` FOREIGN KEY (`pays_id`) REFERENCES `pays` (`id`),
  CONSTRAINT `FK_B4B7F99DB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operateur`
--

LOCK TABLES `operateur` WRITE;
/*!40000 ALTER TABLE `operateur` DISABLE KEYS */;
/*!40000 ALTER TABLE `operateur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiement`
--

DROP TABLE IF EXISTS `paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paiement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `montant` varchar(255) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  `discr` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B1DC7A1EB03A8386` (`created_by_id`),
  KEY `IDX_B1DC7A1E896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_B1DC7A1E896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_B1DC7A1EB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiement`
--

LOCK TABLES `paiement` WRITE;
/*!40000 ALTER TABLE `paiement` DISABLE KEYS */;
INSERT INTO `paiement` VALUES (1,1,1,'15000','PMT251121221602001','paiementBoutique','2025-11-21 22:16:02','2025-11-21 22:16:02',1,'paiementboutique'),(2,1,1,'15000','PMT251121222306002','paiementBoutique','2025-11-21 22:23:06','2025-11-21 22:23:06',1,'paiementboutique'),(3,1,1,'10000','PMT251121223957003','paiementReservation','2025-11-21 22:39:57','2025-11-21 22:39:57',1,'paiementreservation'),(4,1,1,'10000','PMT251121224044004','paiementReservation','2025-11-21 22:40:44','2025-11-21 22:40:44',1,'paiementreservation'),(5,1,1,'10000','PMT251121225226005','paiementReservation','2025-11-21 22:52:26','2025-11-21 22:52:26',1,'paiementreservation'),(6,1,1,'10000','PMT251121225345006','paiementReservation','2025-11-21 22:53:45','2025-11-21 22:53:45',1,'paiementreservation'),(7,1,1,'10000','PMT251121225806007','paiementReservation','2025-11-21 22:58:06','2025-11-21 22:58:06',1,'paiementreservation'),(8,1,1,'15000','PMT251124100449008','paiementBoutique','2025-11-24 10:04:49','2025-11-24 10:04:49',1,'paiementboutique'),(9,1,1,'20000','PMT251124123403009','paiementFacture','2025-11-24 12:34:03','2025-11-24 12:34:03',1,'paiementfacture'),(10,1,1,'20000','PMT251124131037010','paiementFacture','2025-11-24 13:10:37','2025-11-24 13:10:37',1,'paiementfacture');
/*!40000 ALTER TABLE `paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiement_abonnement`
--

DROP TABLE IF EXISTS `paiement_abonnement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paiement_abonnement` (
  `id` int(11) NOT NULL,
  `module_abonnement_id` int(11) DEFAULT NULL,
  `entreprise_id` int(11) DEFAULT NULL,
  `channel` varchar(255) NOT NULL,
  `state` int(11) NOT NULL,
  `pays` varchar(255) NOT NULL,
  `data_user` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data_user`)),
  `data_succursale` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data_succursale`)),
  `data_boutique` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data_boutique`)),
  PRIMARY KEY (`id`),
  KEY `IDX_A3DD064BA20C84BB` (`module_abonnement_id`),
  KEY `IDX_A3DD064BA4AEAFEA` (`entreprise_id`),
  CONSTRAINT `FK_A3DD064BA20C84BB` FOREIGN KEY (`module_abonnement_id`) REFERENCES `module_abonnement` (`id`),
  CONSTRAINT `FK_A3DD064BA4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_A3DD064BBF396750` FOREIGN KEY (`id`) REFERENCES `paiement` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiement_abonnement`
--

LOCK TABLES `paiement_abonnement` WRITE;
/*!40000 ALTER TABLE `paiement_abonnement` DISABLE KEYS */;
/*!40000 ALTER TABLE `paiement_abonnement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiement_boutique`
--

DROP TABLE IF EXISTS `paiement_boutique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paiement_boutique` (
  `id` int(11) NOT NULL,
  `boutique_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `quantite` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6B85C46EAB677BE6` (`boutique_id`),
  KEY `IDX_6B85C46E19EB6921` (`client_id`),
  CONSTRAINT `FK_6B85C46E19EB6921` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`),
  CONSTRAINT `FK_6B85C46EAB677BE6` FOREIGN KEY (`boutique_id`) REFERENCES `boutique` (`id`),
  CONSTRAINT `FK_6B85C46EBF396750` FOREIGN KEY (`id`) REFERENCES `paiement` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiement_boutique`
--

LOCK TABLES `paiement_boutique` WRITE;
/*!40000 ALTER TABLE `paiement_boutique` DISABLE KEYS */;
INSERT INTO `paiement_boutique` VALUES (1,1,1,2),(2,1,1,2),(8,1,1,1);
/*!40000 ALTER TABLE `paiement_boutique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiement_boutique_ligne`
--

DROP TABLE IF EXISTS `paiement_boutique_ligne`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paiement_boutique_ligne` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modele_boutique_id` int(11) DEFAULT NULL,
  `paiement_boutique_id` int(11) DEFAULT NULL,
  `quantite` int(11) DEFAULT NULL,
  `montant` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6DA675E6612EEE7B` (`modele_boutique_id`),
  KEY `IDX_6DA675E6164C4694` (`paiement_boutique_id`),
  CONSTRAINT `FK_6DA675E6164C4694` FOREIGN KEY (`paiement_boutique_id`) REFERENCES `paiement_boutique` (`id`),
  CONSTRAINT `FK_6DA675E6612EEE7B` FOREIGN KEY (`modele_boutique_id`) REFERENCES `modele_boutique` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiement_boutique_ligne`
--

LOCK TABLES `paiement_boutique_ligne` WRITE;
/*!40000 ALTER TABLE `paiement_boutique_ligne` DISABLE KEYS */;
INSERT INTO `paiement_boutique_ligne` VALUES (1,1,1,2,'15000'),(2,1,2,2,'15000'),(3,1,8,1,'15000');
/*!40000 ALTER TABLE `paiement_boutique_ligne` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiement_facture`
--

DROP TABLE IF EXISTS `paiement_facture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paiement_facture` (
  `id` int(11) NOT NULL,
  `facture_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E3BCD4E17F2DEE08` (`facture_id`),
  CONSTRAINT `FK_E3BCD4E17F2DEE08` FOREIGN KEY (`facture_id`) REFERENCES `facture` (`id`),
  CONSTRAINT `FK_E3BCD4E1BF396750` FOREIGN KEY (`id`) REFERENCES `paiement` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiement_facture`
--

LOCK TABLES `paiement_facture` WRITE;
/*!40000 ALTER TABLE `paiement_facture` DISABLE KEYS */;
INSERT INTO `paiement_facture` VALUES (9,1),(10,2);
/*!40000 ALTER TABLE `paiement_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiement_reservation`
--

DROP TABLE IF EXISTS `paiement_reservation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paiement_reservation` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FFE37427B83297E7` (`reservation_id`),
  CONSTRAINT `FK_FFE37427B83297E7` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`id`),
  CONSTRAINT `FK_FFE37427BF396750` FOREIGN KEY (`id`) REFERENCES `paiement` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiement_reservation`
--

LOCK TABLES `paiement_reservation` WRITE;
/*!40000 ALTER TABLE `paiement_reservation` DISABLE KEYS */;
INSERT INTO `paiement_reservation` VALUES (3,1),(4,2),(5,3),(6,4),(7,5);
/*!40000 ALTER TABLE `paiement_reservation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `param_fichier`
--

DROP TABLE IF EXISTS `param_fichier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `param_fichier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `size` int(11) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `date_creation` datetime NOT NULL,
  `url` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `param_fichier`
--

LOCK TABLES `param_fichier` WRITE;
/*!40000 ALTER TABLE `param_fichier` DISABLE KEYS */;
INSERT INTO `param_fichier` VALUES (1,61092,'media_deeps','document-01play_store_512.png','2025-11-01 13:43:59','png'),(2,3936985,'media_deeps','document-01image_picker_ad75f10c_9dbd_4f9a_a880_f330c364e18b_10103_00001311e9c9f3cf.jpg','2025-11-08 18:47:02','jpg'),(3,3936985,'media_deeps','document-01image_picker_b6664318_d6ed_4614_b41b_fa18f91b9525_10103_0000131bb66c7ef1.jpg','2025-11-08 19:16:37','jpg'),(4,1483878,'media_deeps','document-01image_picker_3dad7139_c7bd_463d_8b14_602f6bd7da1e_10103_0000131defdbacbb.jpg','2025-11-08 19:22:51','jpg'),(5,1483878,'media_deeps','document-01image_picker_98f5ee1d_6a9d_4fbe_98b2_e0d5c4f5430b_10103_0000131f5f186bea.jpg','2025-11-08 19:28:28','jpg'),(6,3936985,'media_deeps','document-01image_picker_918cbbd1_5c6a_4503_86f6_b18b3a0dce74_10103_0000132de22ae061.jpg','2025-11-08 21:13:40','jpg'),(7,3627361,'media_deeps','document-013f5b695a_13fb_46e4_96f2_e2d50a50ebf92129686039011549076.jpg','2025-11-08 23:47:05','jpg'),(8,220147,'media_deeps','document-011000630116.jpg','2025-11-09 00:09:06','jpg'),(9,2957353,'media_deeps','document-0182faef2f_6bdf_4880_ba0c_90b43ed96e306744331203919576442.jpg','2025-11-21 16:26:26','jpg'),(10,73502,'media_deeps','document-01img_0884.webp','2025-11-21 17:54:20','webp'),(11,75556,'media_deeps','document-01img_8657.webp','2025-11-21 17:55:02','webp'),(12,16945,'media_deeps','document-01robe_africaine_a_col_volant_103_200x.avif','2025-11-21 21:43:10','avif'),(13,73502,'media_deeps','document-01img_0884.webp','2025-11-24 12:34:03','webp'),(14,75556,'media_deeps','document-01img_8657.webp','2025-11-24 13:10:37','webp'),(15,75556,'media_deeps','document-01img_8657.webp','2025-11-24 13:10:37','webp'),(16,73502,'media_deeps','document-01img_0884.webp','2025-11-24 13:10:37','webp'),(17,75556,'media_deeps','document-01img_8657.webp','2025-11-24 13:10:37','webp'),(18,73502,'media_deeps','document-01img_0884.webp','2025-11-24 13:10:37','webp');
/*!40000 ALTER TABLE `param_fichier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pays`
--

DROP TABLE IF EXISTS `pays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `indicatif` varchar(255) NOT NULL,
  `actif` tinyint(1) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_349F3CAEB03A8386` (`created_by_id`),
  KEY `IDX_349F3CAE896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_349F3CAE896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_349F3CAEB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pays`
--

LOCK TABLES `pays` WRITE;
/*!40000 ALTER TABLE `pays` DISABLE KEYS */;
INSERT INTO `pays` VALUES (1,NULL,NULL,'Côte d\'ivoire','CI','+225',1,NULL,NULL,0);
/*!40000 ALTER TABLE `pays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservation`
--

DROP TABLE IF EXISTS `reservation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `entreprise_id` int(11) DEFAULT NULL,
  `boutique_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `montant` varchar(255) NOT NULL,
  `date_retrait` datetime DEFAULT NULL,
  `avance` varchar(255) DEFAULT NULL,
  `reste` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_42C8495519EB6921` (`client_id`),
  KEY `IDX_42C84955A4AEAFEA` (`entreprise_id`),
  KEY `IDX_42C84955AB677BE6` (`boutique_id`),
  KEY `IDX_42C84955B03A8386` (`created_by_id`),
  KEY `IDX_42C84955896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_42C8495519EB6921` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`),
  CONSTRAINT `FK_42C84955896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_42C84955A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_42C84955AB677BE6` FOREIGN KEY (`boutique_id`) REFERENCES `boutique` (`id`),
  CONSTRAINT `FK_42C84955B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservation`
--

LOCK TABLES `reservation` WRITE;
/*!40000 ALTER TABLE `reservation` DISABLE KEYS */;
INSERT INTO `reservation` VALUES (1,1,1,1,1,1,'15000','2025-11-24 22:39:21','10000','5000','2025-11-21 22:39:57','2025-11-21 22:39:57',1),(2,1,1,1,1,1,'15000','2025-11-24 22:39:21','10000','5000','2025-11-21 22:40:44','2025-11-21 22:40:44',1),(3,1,1,1,1,1,'15000','2025-11-24 00:00:00','10000','5000','2025-11-21 22:52:26','2025-11-21 22:52:26',1),(4,1,1,1,1,1,'15000','2025-11-24 00:00:00','10000','5000','2025-11-21 22:53:45','2025-11-21 22:53:45',1),(5,1,1,1,1,1,'15000','2025-11-24 00:00:00','10000','5000','2025-11-21 22:58:06','2025-11-21 22:58:06',1);
/*!40000 ALTER TABLE `reservation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reset_password_request`
--

DROP TABLE IF EXISTS `reset_password_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reset_password_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` varchar(20) NOT NULL,
  `hashed_token` varchar(100) NOT NULL,
  `requested_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `expires_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_7CE748AA76ED395` (`user_id`),
  CONSTRAINT `FK_7CE748AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reset_password_request`
--

LOCK TABLES `reset_password_request` WRITE;
/*!40000 ALTER TABLE `reset_password_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `reset_password_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reset_password_token`
--

DROP TABLE IF EXISTS `reset_password_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reset_password_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_452C9EC55F37A13B` (`token`),
  KEY `IDX_452C9EC5A76ED395` (`user_id`),
  CONSTRAINT `FK_452C9EC5A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reset_password_token`
--

LOCK TABLES `reset_password_token` WRITE;
/*!40000 ALTER TABLE `reset_password_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `reset_password_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setting`
--

DROP TABLE IF EXISTS `setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `nombre_user` int(11) DEFAULT NULL,
  `nombre_sms` int(11) DEFAULT NULL,
  `nombre_succursale` int(11) NOT NULL,
  `send_messsage_automatic_if_rendez_vous_proche` tinyint(1) DEFAULT NULL,
  `nombre_jour_restant_pour_envoyer_sms` int(11) DEFAULT NULL,
  `modele_message_envoyer_pour_rendez_vous_proche` longtext DEFAULT NULL,
  `nombre_boutique` int(11) NOT NULL,
  `numero_abonnement` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9F74B898A4AEAFEA` (`entreprise_id`),
  KEY `IDX_9F74B898B03A8386` (`created_by_id`),
  KEY `IDX_9F74B898896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_9F74B898896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_9F74B898A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_9F74B898B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setting`
--

LOCK TABLES `setting` WRITE;
/*!40000 ALTER TABLE `setting` DISABLE KEYS */;
INSERT INTO `setting` VALUES (1,1,NULL,NULL,3,1,3,1,10,'Bonjour, ceci est un rappel pour votre rendez-vous prévu prochainement dans 10 jours, merci de vous présenter à l’heure.',3,1,NULL,NULL,1);
/*!40000 ALTER TABLE `setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `surccursale`
--

DROP TABLE IF EXISTS `surccursale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surccursale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3ABAE175A4AEAFEA` (`entreprise_id`),
  KEY `IDX_3ABAE175B03A8386` (`created_by_id`),
  KEY `IDX_3ABAE175896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_3ABAE175896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_3ABAE175A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_3ABAE175B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `surccursale`
--

LOCK TABLES `surccursale` WRITE;
/*!40000 ALTER TABLE `surccursale` DISABLE KEYS */;
INSERT INTO `surccursale` VALUES (1,1,1,1,'Succursale Abidjan Cocody','+2252720123456','2025-11-24 12:08:41','2025-11-24 12:08:41',1);
/*!40000 ALTER TABLE `surccursale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_mesure`
--

DROP TABLE IF EXISTS `type_mesure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_mesure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entreprise_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7CCC0548A4AEAFEA` (`entreprise_id`),
  KEY `IDX_7CCC0548B03A8386` (`created_by_id`),
  KEY `IDX_7CCC0548896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_7CCC0548896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_7CCC0548A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_7CCC0548B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_mesure`
--

LOCK TABLES `type_mesure` WRITE;
/*!40000 ALTER TABLE `type_mesure` DISABLE KEYS */;
INSERT INTO `type_mesure` VALUES (1,NULL,NULL,NULL,'costume','2025-01-01 00:00:00',NULL,1),(2,NULL,NULL,NULL,'chemise homme','2025-01-01 00:00:00',NULL,1),(3,NULL,NULL,NULL,'pantalon homme','2025-01-01 00:00:00',NULL,1),(4,NULL,NULL,NULL,'veste homme','2025-01-01 00:00:00',NULL,1),(5,NULL,NULL,NULL,'manteau homme','2025-01-01 00:00:00',NULL,1),(6,NULL,NULL,NULL,'blazer homme','2025-01-01 00:00:00',NULL,1),(7,NULL,NULL,NULL,'robe','2025-01-01 00:00:00',NULL,1),(8,NULL,NULL,NULL,'jupe','2025-01-01 00:00:00',NULL,1),(9,NULL,NULL,NULL,'chemisier femme','2025-01-01 00:00:00',NULL,1),(10,NULL,NULL,NULL,'pantalon femme','2025-01-01 00:00:00',NULL,1),(11,NULL,NULL,NULL,'veste femme','2025-01-01 00:00:00',NULL,1),(12,NULL,NULL,NULL,'manteau femme','2025-01-01 00:00:00',NULL,1),(13,NULL,NULL,NULL,'tailleur','2025-01-01 00:00:00',NULL,1),(14,NULL,NULL,NULL,'boubou homme','2025-01-01 00:00:00',NULL,1),(15,NULL,NULL,NULL,'boubou femme','2025-01-01 00:00:00',NULL,1),(16,NULL,NULL,NULL,'kaba','2025-01-01 00:00:00',NULL,1),(17,NULL,NULL,NULL,'pagne','2025-01-01 00:00:00',NULL,1),(18,NULL,NULL,NULL,'tenue traditionnelle','2025-01-01 00:00:00',NULL,1),(19,NULL,NULL,NULL,'t-shirt','2025-01-01 00:00:00',NULL,1),(20,NULL,NULL,NULL,'polo','2025-01-01 00:00:00',NULL,1),(21,NULL,NULL,NULL,'sweat','2025-01-01 00:00:00',NULL,1),(22,NULL,NULL,NULL,'gilet','2025-01-01 00:00:00',NULL,1),(23,NULL,NULL,NULL,'short','2025-01-01 00:00:00',NULL,1),(24,NULL,NULL,NULL,'jean','2025-01-01 00:00:00',NULL,1),(25,NULL,NULL,NULL,'sur-mesure','2025-01-01 00:00:00',NULL,1);
/*!40000 ALTER TABLE `type_mesure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_user`
--

DROP TABLE IF EXISTS `type_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by_id` int(11) DEFAULT NULL,
  `updated_by_id` int(11) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5A9C1341B03A8386` (`created_by_id`),
  KEY `IDX_5A9C1341896DBBDE` (`updated_by_id`),
  CONSTRAINT `FK_5A9C1341896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_5A9C1341B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_user`
--

LOCK TABLES `type_user` WRITE;
/*!40000 ALTER TABLE `type_user` DISABLE KEYS */;
INSERT INTO `type_user` VALUES (1,NULL,NULL,'Super Administrateur','SADM',NULL,NULL,1),(2,NULL,NULL,'Gérant boutique','ADB',NULL,NULL,1),(3,NULL,NULL,'Gérant succursale','ADS',NULL,NULL,1),(4,NULL,NULL,'Gérant succursale et boutique','ADSB',NULL,NULL,1);
/*!40000 ALTER TABLE `type_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `surccursale_id` int(11) DEFAULT NULL,
  `entreprise_id` int(11) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `logo_id` int(11) DEFAULT NULL,
  `boutique_id` int(11) DEFAULT NULL,
  `login` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `nom` varchar(255) DEFAULT NULL,
  `prenoms` varchar(255) DEFAULT NULL,
  `plain_reset_token` varchar(6) DEFAULT NULL,
  `plain_token_expires_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `fcm_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9AA08CB10` (`login`),
  KEY `IDX_1483A5E958AAF78E` (`surccursale_id`),
  KEY `IDX_1483A5E9A4AEAFEA` (`entreprise_id`),
  KEY `IDX_1483A5E9C54C8C93` (`type_id`),
  KEY `IDX_1483A5E9F98F144A` (`logo_id`),
  KEY `IDX_1483A5E9AB677BE6` (`boutique_id`),
  CONSTRAINT `FK_1483A5E958AAF78E` FOREIGN KEY (`surccursale_id`) REFERENCES `surccursale` (`id`),
  CONSTRAINT `FK_1483A5E9A4AEAFEA` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprise` (`id`),
  CONSTRAINT `FK_1483A5E9AB677BE6` FOREIGN KEY (`boutique_id`) REFERENCES `boutique` (`id`),
  CONSTRAINT `FK_1483A5E9C54C8C93` FOREIGN KEY (`type_id`) REFERENCES `type_user` (`id`),
  CONSTRAINT `FK_1483A5E9F98F144A` FOREIGN KEY (`logo_id`) REFERENCES `param_fichier` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,1,1,NULL,NULL,'konatenhamed@gmail.com','[\"ROLE_ADMIN\"]','$2y$13$iwy0M0SkxU4BNgEFlg.lFeMnHtdU1aAQWnVUA5q5DbjSItQhWkpES',1,'2025-11-21 17:42:36',NULL,NULL,NULL,NULL,NULL);
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

-- Dump completed on 2025-11-25 10:45:14
