-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: mapa_sala
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
-- Table structure for table `administrador`
--

DROP TABLE IF EXISTS `administrador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `administrador` (
  `admCodigo` tinyint NOT NULL AUTO_INCREMENT,
  `admNome` varchar(45) DEFAULT NULL,
  `admEmail` varchar(100) DEFAULT NULL,
  `admSenha` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`admCodigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administrador`
--

LOCK TABLES `administrador` WRITE;
/*!40000 ALTER TABLE `administrador` DISABLE KEYS */;
INSERT INTO `administrador` VALUES (1,'Ricardo Bruno','ricardo@senac','123'),(2,'Guilherme','guilherme@senac','456');
/*!40000 ALTER TABLE `administrador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `area`
--

DROP TABLE IF EXISTS `area`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `area` (
  `areCodigo` tinyint NOT NULL AUTO_INCREMENT,
  `areNome` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`areCodigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area`
--

LOCK TABLES `area` WRITE;
/*!40000 ALTER TABLE `area` DISABLE KEYS */;
INSERT INTO `area` VALUES (1,'Ti'),(2,'Enfermagem');
/*!40000 ALTER TABLE `area` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cursos`
--

DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cursos` (
  `curCod` mediumint NOT NULL AUTO_INCREMENT,
  `curNome` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`curCod`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cursos`
--

LOCK TABLES `cursos` WRITE;
/*!40000 ALTER TABLE `cursos` DISABLE KEYS */;
INSERT INTO `cursos` VALUES (1,'Técnico em Informática'),(2,'Técnico em Informática para Internet'),(3,'Técnico em Enfermagem'),(4,'Desenvolvimento Mobile'),(5,'Desenvolvimento de Sistema');
/*!40000 ALTER TABLE `cursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docentes`
--

DROP TABLE IF EXISTS `docentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `docentes` (
  `docCodigo` int NOT NULL AUTO_INCREMENT,
  `docNome` varchar(45) DEFAULT NULL,
  `docHoras` tinyint DEFAULT NULL,
  `areCodigo` tinyint NOT NULL,
  PRIMARY KEY (`docCodigo`),
  KEY `fk_docentes_area1_idx` (`areCodigo`),
  CONSTRAINT `fk_docentes_area1` FOREIGN KEY (`areCodigo`) REFERENCES `area` (`areCodigo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docentes`
--

LOCK TABLES `docentes` WRITE;
/*!40000 ALTER TABLE `docentes` DISABLE KEYS */;
INSERT INTO `docentes` VALUES (1,'Ricardo',31,1);
/*!40000 ALTER TABLE `docentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docentes_turmas`
--

DROP TABLE IF EXISTS `docentes_turmas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `docentes_turmas` (
  `docCodigo` int NOT NULL,
  `turCod` bigint NOT NULL,
  PRIMARY KEY (`docCodigo`,`turCod`),
  KEY `fk_docentes_has_Turmas_Turmas1_idx` (`turCod`),
  KEY `fk_docentes_has_Turmas_docentes1_idx` (`docCodigo`),
  CONSTRAINT `fk_docentes_has_Turmas_docentes1` FOREIGN KEY (`docCodigo`) REFERENCES `docentes` (`docCodigo`),
  CONSTRAINT `fk_docentes_has_Turmas_Turmas1` FOREIGN KEY (`turCod`) REFERENCES `turmas` (`turCod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docentes_turmas`
--

LOCK TABLES `docentes_turmas` WRITE;
/*!40000 ALTER TABLE `docentes_turmas` DISABLE KEYS */;
/*!40000 ALTER TABLE `docentes_turmas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `horarios`
--

DROP TABLE IF EXISTS `horarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `horarios` (
  `horDia` date NOT NULL,
  `salCod` tinyint NOT NULL,
  `turCod` bigint NOT NULL,
  PRIMARY KEY (`salCod`,`turCod`,`horDia`),
  KEY `fk_horarios_Turmas1_idx` (`turCod`),
  CONSTRAINT `fk_horarios_sala1` FOREIGN KEY (`salCod`) REFERENCES `sala` (`salCod`),
  CONSTRAINT `fk_horarios_Turmas1` FOREIGN KEY (`turCod`) REFERENCES `turmas` (`turCod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `horarios`
--

LOCK TABLES `horarios` WRITE;
/*!40000 ALTER TABLE `horarios` DISABLE KEYS */;
INSERT INTO `horarios` VALUES ('2025-11-26',1,1),('2025-11-28',1,1),('2025-11-28',1,2),('2025-11-26',8,2),('2025-11-28',8,3),('2025-11-28',1,4),('2025-11-28',1,5);
/*!40000 ALTER TABLE `horarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `log` (
  `logCodigo` bigint NOT NULL AUTO_INCREMENT,
  `logTabela` varchar(45) DEFAULT NULL,
  `admCodigo` tinyint NOT NULL,
  `logCrud` char(1) DEFAULT NULL,
  `logSql` varchar(600) DEFAULT NULL,
  `logData` datetime DEFAULT NULL,
  PRIMARY KEY (`logCodigo`),
  KEY `fk_log_administrador1_idx` (`admCodigo`),
  CONSTRAINT `fk_log_administrador1` FOREIGN KEY (`admCodigo`) REFERENCES `administrador` (`admCodigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periodo`
--

DROP TABLE IF EXISTS `periodo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periodo` (
  `perCodigo` tinyint(1) NOT NULL AUTO_INCREMENT,
  `perNome` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`perCodigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periodo`
--

LOCK TABLES `periodo` WRITE;
/*!40000 ALTER TABLE `periodo` DISABLE KEYS */;
INSERT INTO `periodo` VALUES (1,'Manhã'),(2,'Tarde'),(3,'Noite');
/*!40000 ALTER TABLE `periodo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sala`
--

DROP TABLE IF EXISTS `sala`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sala` (
  `salCod` tinyint NOT NULL AUTO_INCREMENT,
  `salNumero` tinyint DEFAULT NULL,
  `tipCod` tinyint(1) NOT NULL,
  PRIMARY KEY (`salCod`),
  KEY `fk_sala_tipo de sala1_idx` (`tipCod`),
  CONSTRAINT `fk_sala_tipo de sala1` FOREIGN KEY (`tipCod`) REFERENCES `tipo_de_sala` (`tipCod`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sala`
--

LOCK TABLES `sala` WRITE;
/*!40000 ALTER TABLE `sala` DISABLE KEYS */;
INSERT INTO `sala` VALUES (1,1,1),(2,2,1),(3,3,1),(4,4,2),(5,5,1),(6,6,1),(7,7,2),(8,13,2);
/*!40000 ALTER TABLE `sala` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_de_sala`
--

DROP TABLE IF EXISTS `tipo_de_sala`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_de_sala` (
  `tipCod` tinyint(1) NOT NULL AUTO_INCREMENT,
  `tipNome` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`tipCod`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_de_sala`
--

LOCK TABLES `tipo_de_sala` WRITE;
/*!40000 ALTER TABLE `tipo_de_sala` DISABLE KEYS */;
INSERT INTO `tipo_de_sala` VALUES (1,'Laboratório de Informática'),(2,'Sala de Aula');
/*!40000 ALTER TABLE `tipo_de_sala` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turmas`
--

DROP TABLE IF EXISTS `turmas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turmas` (
  `turCod` bigint NOT NULL AUTO_INCREMENT,
  `turNome` varchar(45) DEFAULT NULL,
  `turAno` year DEFAULT NULL,
  `curCod` mediumint NOT NULL,
  `perCodigo` tinyint(1) NOT NULL,
  PRIMARY KEY (`turCod`),
  KEY `fk_Turmas_cursos1_idx` (`curCod`),
  KEY `fk_Turmas_periodo1_idx` (`perCodigo`),
  CONSTRAINT `fk_Turmas_cursos1` FOREIGN KEY (`curCod`) REFERENCES `cursos` (`curCod`),
  CONSTRAINT `fk_Turmas_periodo1` FOREIGN KEY (`perCodigo`) REFERENCES `periodo` (`perCodigo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turmas`
--

LOCK TABLES `turmas` WRITE;
/*!40000 ALTER TABLE `turmas` DISABLE KEYS */;
INSERT INTO `turmas` VALUES (1,'TI 2024',2024,1,2),(2,'TII 2024',2024,2,3),(3,'T Enfermagem',2025,3,3),(4,'Desenvolvimento Mobile ',2025,4,3),(5,'Dev de Sistemas',2025,5,2);
/*!40000 ALTER TABLE `turmas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-16 15:19:43
