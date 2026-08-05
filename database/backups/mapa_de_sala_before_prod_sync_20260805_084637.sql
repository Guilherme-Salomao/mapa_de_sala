-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mapa_de_sala
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
-- Table structure for table `aprendizagem_quadros`
--

DROP TABLE IF EXISTS `aprendizagem_quadros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aprendizagem_quadros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_oferta_id` int(11) NOT NULL,
  `unidade_curricular_id` int(11) NOT NULL,
  `sala_id` int(11) DEFAULT NULL,
  `docente_id` int(11) DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_aprendizagem_turma` (`curso_oferta_id`),
  KEY `fk_aprendizagem_uc` (`unidade_curricular_id`),
  KEY `fk_aprendizagem_sala` (`sala_id`),
  KEY `fk_aprendizagem_docente` (`docente_id`),
  CONSTRAINT `fk_aprendizagem_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_aprendizagem_sala` FOREIGN KEY (`sala_id`) REFERENCES `salas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_aprendizagem_turma` FOREIGN KEY (`curso_oferta_id`) REFERENCES `cursos_ofertas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_aprendizagem_uc` FOREIGN KEY (`unidade_curricular_id`) REFERENCES `unidades_curriculares` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aprendizagem_quadros`
--

LOCK TABLES `aprendizagem_quadros` WRITE;
/*!40000 ALTER TABLE `aprendizagem_quadros` DISABLE KEYS */;
/*!40000 ALTER TABLE `aprendizagem_quadros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `status` enum('Ativa','Inativa') NOT NULL DEFAULT 'Ativa',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendario_bloqueios`
--

DROP TABLE IF EXISTS `calendario_bloqueios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendario_bloqueios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cidade_id` int(11) DEFAULT NULL,
  `data` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fim` time DEFAULT NULL,
  `titulo` varchar(150) NOT NULL,
  `tipo` enum('Feriado','Recesso','Parada Pedagogica') NOT NULL DEFAULT 'Feriado',
  `descricao` text DEFAULT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_calendario_bloqueios_status` (`status`),
  KEY `idx_calendario_bloqueios_data` (`data`),
  KEY `fk_calendario_bloqueios_cidade` (`cidade_id`),
  CONSTRAINT `fk_calendario_bloqueios_cidade` FOREIGN KEY (`cidade_id`) REFERENCES `cidades` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendario_bloqueios`
--

LOCK TABLES `calendario_bloqueios` WRITE;
/*!40000 ALTER TABLE `calendario_bloqueios` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendario_bloqueios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cidades`
--

DROP TABLE IF EXISTS `cidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `status` enum('Ativa','Inativa') NOT NULL DEFAULT 'Ativa',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cidades`
--

LOCK TABLES `cidades` WRITE;
/*!40000 ALTER TABLE `cidades` DISABLE KEYS */;
/*!40000 ALTER TABLE `cidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curso_modelos`
--

DROP TABLE IF EXISTS `curso_modelos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curso_modelos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_id` int(11) DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `carga_horaria_total` decimal(8,2) NOT NULL DEFAULT 0.00,
  `sem_uc` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`),
  KEY `fk_curso_modelos_area` (`area_id`),
  CONSTRAINT `fk_curso_modelos_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso_modelos`
--

LOCK TABLES `curso_modelos` WRITE;
/*!40000 ALTER TABLE `curso_modelos` DISABLE KEYS */;
/*!40000 ALTER TABLE `curso_modelos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cursos_ofertas`
--

DROP TABLE IF EXISTS `cursos_ofertas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cursos_ofertas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_modelo_id` int(11) DEFAULT NULL,
  `cidade_id` int(11) DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `codigo_oferta` varchar(50) NOT NULL,
  `integral` tinyint(1) NOT NULL DEFAULT 0,
  `hora_inicio` time DEFAULT NULL,
  `hora_fim` time DEFAULT NULL,
  `hora_inicio_tarde` time DEFAULT NULL,
  `hora_fim_tarde` time DEFAULT NULL,
  `participa_parada_pedagogica` tinyint(1) NOT NULL DEFAULT 1,
  `participa_recesso_escolar` tinyint(1) NOT NULL DEFAULT 1,
  `aula_segunda` tinyint(1) NOT NULL DEFAULT 1,
  `aula_terca` tinyint(1) NOT NULL DEFAULT 1,
  `aula_quarta` tinyint(1) NOT NULL DEFAULT 1,
  `aula_quinta` tinyint(1) NOT NULL DEFAULT 1,
  `aula_sexta` tinyint(1) NOT NULL DEFAULT 1,
  `aula_sabado` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('Em andamento','Finalizada') NOT NULL DEFAULT 'Em andamento',
  `descricao` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_oferta` (`codigo_oferta`),
  KEY `fk_cursos_ofertas_modelo` (`curso_modelo_id`),
  KEY `fk_cursos_ofertas_cidade` (`cidade_id`),
  CONSTRAINT `fk_cursos_ofertas_cidade` FOREIGN KEY (`cidade_id`) REFERENCES `cidades` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cursos_ofertas_modelo` FOREIGN KEY (`curso_modelo_id`) REFERENCES `curso_modelos` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cursos_ofertas`
--

LOCK TABLES `cursos_ofertas` WRITE;
/*!40000 ALTER TABLE `cursos_ofertas` DISABLE KEYS */;
/*!40000 ALTER TABLE `cursos_ofertas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente_areas`
--

DROP TABLE IF EXISTS `docente_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docente_areas` (
  `docente_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  PRIMARY KEY (`docente_id`,`area_id`),
  KEY `fk_docente_areas_area` (`area_id`),
  CONSTRAINT `fk_docente_areas_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_docente_areas_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente_areas`
--

LOCK TABLES `docente_areas` WRITE;
/*!40000 ALTER TABLE `docente_areas` DISABLE KEYS */;
/*!40000 ALTER TABLE `docente_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente_compensacoes`
--

DROP TABLE IF EXISTS `docente_compensacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docente_compensacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_docente_compensacoes_periodo` (`data_inicio`,`data_fim`,`status`),
  KEY `idx_docente_compensacoes_docente_periodo` (`docente_id`,`data_inicio`,`data_fim`,`status`),
  CONSTRAINT `fk_docente_compensacoes_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente_compensacoes`
--

LOCK TABLES `docente_compensacoes` WRITE;
/*!40000 ALTER TABLE `docente_compensacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `docente_compensacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente_cursos`
--

DROP TABLE IF EXISTS `docente_cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docente_cursos` (
  `docente_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  PRIMARY KEY (`docente_id`,`curso_id`),
  KEY `fk_docente_cursos_oferta` (`curso_id`),
  CONSTRAINT `fk_docente_cursos_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_docente_cursos_oferta` FOREIGN KEY (`curso_id`) REFERENCES `cursos_ofertas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente_cursos`
--

LOCK TABLES `docente_cursos` WRITE;
/*!40000 ALTER TABLE `docente_cursos` DISABLE KEYS */;
/*!40000 ALTER TABLE `docente_cursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente_escala`
--

DROP TABLE IF EXISTS `docente_escala`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docente_escala` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `dia_semana` enum('Segunda','Terça','Quarta','Quinta','Sexta','Sábado') NOT NULL,
  `periodo` enum('Manhã','Tarde','Noite') NOT NULL,
  `horas` decimal(4,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_docente_escala_docente` (`docente_id`),
  CONSTRAINT `fk_docente_escala_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente_escala`
--

LOCK TABLES `docente_escala` WRITE;
/*!40000 ALTER TABLE `docente_escala` DISABLE KEYS */;
/*!40000 ALTER TABLE `docente_escala` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente_ferias`
--

DROP TABLE IF EXISTS `docente_ferias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docente_ferias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_docente_ferias_periodo` (`data_inicio`,`data_fim`,`status`),
  KEY `idx_docente_ferias_docente_periodo` (`docente_id`,`data_inicio`,`data_fim`,`status`),
  CONSTRAINT `fk_docente_ferias_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente_ferias`
--

LOCK TABLES `docente_ferias` WRITE;
/*!40000 ALTER TABLE `docente_ferias` DISABLE KEYS */;
/*!40000 ALTER TABLE `docente_ferias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente_substituicoes`
--

DROP TABLE IF EXISTS `docente_substituicoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docente_substituicoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `sala_id` int(11) DEFAULT NULL,
  `data_aula` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `turma` varchar(150) NOT NULL,
  `unidade_curricular` varchar(200) DEFAULT NULL,
  `motivo` varchar(150) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_docente_substituicoes_docente_data` (`docente_id`,`data_aula`,`hora_inicio`,`hora_fim`,`status`),
  KEY `idx_docente_substituicoes_sala_data` (`sala_id`,`data_aula`,`hora_inicio`,`hora_fim`,`status`),
  CONSTRAINT `fk_docente_substituicoes_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_docente_substituicoes_sala` FOREIGN KEY (`sala_id`) REFERENCES `salas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente_substituicoes`
--

LOCK TABLES `docente_substituicoes` WRITE;
/*!40000 ALTER TABLE `docente_substituicoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `docente_substituicoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docente_unidades_curriculares`
--

DROP TABLE IF EXISTS `docente_unidades_curriculares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docente_unidades_curriculares` (
  `docente_id` int(11) NOT NULL,
  `unidade_curricular_id` int(11) NOT NULL,
  PRIMARY KEY (`docente_id`,`unidade_curricular_id`),
  KEY `fk_docente_uc_uc` (`unidade_curricular_id`),
  CONSTRAINT `fk_docente_uc_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_docente_uc_uc` FOREIGN KEY (`unidade_curricular_id`) REFERENCES `unidades_curriculares` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docente_unidades_curriculares`
--

LOCK TABLES `docente_unidades_curriculares` WRITE;
/*!40000 ALTER TABLE `docente_unidades_curriculares` DISABLE KEYS */;
/*!40000 ALTER TABLE `docente_unidades_curriculares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docentes`
--

DROP TABLE IF EXISTS `docentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `horas_semanais` decimal(5,2) NOT NULL DEFAULT 0.00,
  `area_atuacao` varchar(100) NOT NULL,
  `status` enum('Ativo','Inativo') DEFAULT 'Ativo',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_docentes_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docentes`
--

LOCK TABLES `docentes` WRITE;
/*!40000 ALTER TABLE `docentes` DISABLE KEYS */;
/*!40000 ALTER TABLE `docentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `educacao_corporativa_docentes`
--

DROP TABLE IF EXISTS `educacao_corporativa_docentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `educacao_corporativa_docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `dia_inteiro` tinyint(1) NOT NULL DEFAULT 1,
  `hora_inicio` time DEFAULT NULL,
  `hora_fim` time DEFAULT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_educacao_corporativa_data` (`data`),
  KEY `idx_educacao_corporativa_docente_data` (`docente_id`,`data`),
  CONSTRAINT `fk_educacao_corporativa_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `educacao_corporativa_docentes`
--

LOCK TABLES `educacao_corporativa_docentes` WRITE;
/*!40000 ALTER TABLE `educacao_corporativa_docentes` DISABLE KEYS */;
/*!40000 ALTER TABLE `educacao_corporativa_docentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quadro_horario`
--

DROP TABLE IF EXISTS `quadro_horario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quadro_horario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aprendizagem_quadro_id` int(11) DEFAULT NULL,
  `curso_oferta_id` int(11) NOT NULL,
  `unidade_curricular_id` int(11) NOT NULL,
  `sala_id` int(11) DEFAULT NULL,
  `data_aula` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `divisao_por_hora` tinyint(1) NOT NULL DEFAULT 0,
  `dupla_docencia` tinyint(1) NOT NULL DEFAULT 0,
  `visita_tecnica` tinyint(1) NOT NULL DEFAULT 0,
  `ead_assincrona` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Ativa','Cancelada','Reposição') NOT NULL DEFAULT 'Ativa',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_quadro_aprendizagem` (`aprendizagem_quadro_id`),
  KEY `fk_quadro_curso_oferta` (`curso_oferta_id`),
  KEY `fk_quadro_uc` (`unidade_curricular_id`),
  KEY `fk_quadro_sala` (`sala_id`),
  CONSTRAINT `fk_quadro_aprendizagem` FOREIGN KEY (`aprendizagem_quadro_id`) REFERENCES `aprendizagem_quadros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_quadro_curso_oferta` FOREIGN KEY (`curso_oferta_id`) REFERENCES `cursos_ofertas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_quadro_sala` FOREIGN KEY (`sala_id`) REFERENCES `salas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_quadro_uc` FOREIGN KEY (`unidade_curricular_id`) REFERENCES `unidades_curriculares` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quadro_horario`
--

LOCK TABLES `quadro_horario` WRITE;
/*!40000 ALTER TABLE `quadro_horario` DISABLE KEYS */;
/*!40000 ALTER TABLE `quadro_horario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quadro_horario_docentes`
--

DROP TABLE IF EXISTS `quadro_horario_docentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quadro_horario_docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quadro_horario_id` int(11) NOT NULL,
  `docente_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quadro_docente` (`quadro_horario_id`,`docente_id`),
  KEY `fk_quadro_docente` (`docente_id`),
  CONSTRAINT `fk_quadro_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_quadro_docente_horario` FOREIGN KEY (`quadro_horario_id`) REFERENCES `quadro_horario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quadro_horario_docentes`
--

LOCK TABLES `quadro_horario_docentes` WRITE;
/*!40000 ALTER TABLE `quadro_horario_docentes` DISABLE KEYS */;
/*!40000 ALTER TABLE `quadro_horario_docentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recursos`
--

DROP TABLE IF EXISTS `recursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recursos`
--

LOCK TABLES `recursos` WRITE;
/*!40000 ALTER TABLE `recursos` DISABLE KEYS */;
/*!40000 ALTER TABLE `recursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sala_recursos`
--

DROP TABLE IF EXISTS `sala_recursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sala_recursos` (
  `sala_id` int(11) NOT NULL,
  `recurso_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`sala_id`,`recurso_id`),
  KEY `fk_sala_recursos_recurso` (`recurso_id`),
  CONSTRAINT `fk_sala_recursos_recurso` FOREIGN KEY (`recurso_id`) REFERENCES `recursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sala_recursos_sala` FOREIGN KEY (`sala_id`) REFERENCES `salas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sala_recursos`
--

LOCK TABLES `sala_recursos` WRITE;
/*!40000 ALTER TABLE `sala_recursos` DISABLE KEYS */;
/*!40000 ALTER TABLE `sala_recursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sala_reservas`
--

DROP TABLE IF EXISTS `sala_reservas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sala_reservas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sala_id` int(11) NOT NULL,
  `tipo` enum('Reservada','Manutencao') NOT NULL DEFAULT 'Reservada',
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `solicitante_usuario_id` int(11) DEFAULT NULL,
  `solicitante` varchar(150) DEFAULT NULL,
  `motivo` varchar(150) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sala_reservas_periodo` (`sala_id`,`data_inicio`,`data_fim`,`hora_inicio`,`hora_fim`,`status`),
  KEY `idx_sala_reservas_solicitante` (`solicitante_usuario_id`),
  CONSTRAINT `fk_sala_reservas_sala` FOREIGN KEY (`sala_id`) REFERENCES `salas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sala_reservas_solicitante` FOREIGN KEY (`solicitante_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sala_reservas`
--

LOCK TABLES `sala_reservas` WRITE;
/*!40000 ALTER TABLE `sala_reservas` DISABLE KEYS */;
/*!40000 ALTER TABLE `sala_reservas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sala_trocas`
--

DROP TABLE IF EXISTS `sala_trocas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sala_trocas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quadro_horario_id` int(11) NOT NULL,
  `sala_origem_id` int(11) DEFAULT NULL,
  `sala_destino_id` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sala_trocas_aula` (`quadro_horario_id`),
  KEY `idx_sala_trocas_salas` (`sala_origem_id`,`sala_destino_id`),
  KEY `idx_sala_trocas_usuario` (`usuario_id`),
  KEY `fk_sala_trocas_destino` (`sala_destino_id`),
  CONSTRAINT `fk_sala_trocas_aula` FOREIGN KEY (`quadro_horario_id`) REFERENCES `quadro_horario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sala_trocas_destino` FOREIGN KEY (`sala_destino_id`) REFERENCES `salas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sala_trocas_origem` FOREIGN KEY (`sala_origem_id`) REFERENCES `salas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sala_trocas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sala_trocas`
--

LOCK TABLES `sala_trocas` WRITE;
/*!40000 ALTER TABLE `sala_trocas` DISABLE KEYS */;
/*!40000 ALTER TABLE `sala_trocas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salas`
--

DROP TABLE IF EXISTS `salas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `capacidade` int(11) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'ativa',
  `descricao` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salas`
--

LOCK TABLES `salas` WRITE;
/*!40000 ALTER TABLE `salas` DISABLE KEYS */;
/*!40000 ALTER TABLE `salas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sistema_logs`
--

DROP TABLE IF EXISTS `sistema_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sistema_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `usuario_nome` varchar(150) DEFAULT NULL,
  `usuario_email` varchar(150) DEFAULT NULL,
  `nivel_acesso` varchar(50) DEFAULT NULL,
  `metodo` varchar(10) NOT NULL,
  `pagina` varchar(80) NOT NULL,
  `acao` varchar(80) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `dados` longtext DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `navegador` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sistema_logs_usuario` (`usuario_id`),
  KEY `idx_sistema_logs_pagina_acao` (`pagina`,`acao`),
  KEY `idx_sistema_logs_criado_em` (`criado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sistema_logs`
--

LOCK TABLES `sistema_logs` WRITE;
/*!40000 ALTER TABLE `sistema_logs` DISABLE KEYS */;
INSERT INTO `sistema_logs` VALUES (1,NULL,NULL,NULL,NULL,'POST','login','autenticar','Autenticacao em login','{\"email\":\"vitrineata@vitrineata.com.br\",\"senha\":\"[protegido]\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-29 11:30:07'),(2,NULL,NULL,NULL,NULL,'POST','login','autenticar','Autenticacao em login','{\"email\":\"vitrineata@vitrineata.com.br\",\"senha\":\"[protegido]\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-29 11:30:12'),(3,1,'Administrador','vitrineata@vitrineata.com.br','Admin','POST','login','entrar','Entrar em login','{\"email\":\"vitrineata@vitrineata.com.br\",\"senha\":\"[protegido]\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-29 11:30:12'),(4,NULL,NULL,NULL,NULL,'POST','login','autenticar','Autenticacao em login','{\"email\":\"vitrineata@vitrineata.com.br\",\"senha\":\"[protegido]\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-08-05 11:21:39'),(5,NULL,NULL,NULL,NULL,'POST','login','autenticar','Autenticacao em login','{\"email\":\"vitrineata@vitrineata.com.br\",\"senha\":\"[protegido]\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-08-05 11:21:44'),(6,1,'Administrador','vitrineata@vitrineata.com.br','Admin','POST','login','entrar','Entrar em login','{\"email\":\"vitrineata@vitrineata.com.br\",\"senha\":\"[protegido]\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-08-05 11:21:44');
/*!40000 ALTER TABLE `sistema_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turma_unidades_curriculares`
--

DROP TABLE IF EXISTS `turma_unidades_curriculares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turma_unidades_curriculares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_oferta_id` int(11) NOT NULL,
  `unidade_curricular_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(200) NOT NULL,
  `carga_horaria` decimal(8,2) NOT NULL,
  `status` enum('Ativa','Inativa') NOT NULL DEFAULT 'Ativa',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_turma_uc` (`curso_oferta_id`,`unidade_curricular_id`),
  KEY `fk_turma_uc_unidade` (`unidade_curricular_id`),
  CONSTRAINT `fk_turma_uc_oferta` FOREIGN KEY (`curso_oferta_id`) REFERENCES `cursos_ofertas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_turma_uc_unidade` FOREIGN KEY (`unidade_curricular_id`) REFERENCES `unidades_curriculares` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turma_unidades_curriculares`
--

LOCK TABLES `turma_unidades_curriculares` WRITE;
/*!40000 ALTER TABLE `turma_unidades_curriculares` DISABLE KEYS */;
/*!40000 ALTER TABLE `turma_unidades_curriculares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unidades_curriculares`
--

DROP TABLE IF EXISTS `unidades_curriculares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unidades_curriculares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_modelo_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(200) NOT NULL,
  `carga_horaria` decimal(8,2) NOT NULL,
  `status` enum('Ativa','Inativa') NOT NULL DEFAULT 'Ativa',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uc_modelo_codigo` (`curso_modelo_id`,`codigo`),
  CONSTRAINT `fk_uc_curso_modelo` FOREIGN KEY (`curso_modelo_id`) REFERENCES `curso_modelos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidades_curriculares`
--

LOCK TABLES `unidades_curriculares` WRITE;
/*!40000 ALTER TABLE `unidades_curriculares` DISABLE KEYS */;
/*!40000 ALTER TABLE `unidades_curriculares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_areas`
--

DROP TABLE IF EXISTS `usuario_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario_areas` (
  `usuario_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  PRIMARY KEY (`usuario_id`,`area_id`),
  KEY `fk_usuario_areas_area` (`area_id`),
  CONSTRAINT `fk_usuario_areas_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_areas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_areas`
--

LOCK TABLES `usuario_areas` WRITE;
/*!40000 ALTER TABLE `usuario_areas` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('Admin','Gestor','Professor','Apoio') NOT NULL DEFAULT 'Professor',
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `ultimo_login` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','vitrineata@vitrineata.com.br','$2y$10$KUdIGr8ADlkQkfsUZrVCfuQRueTH0rHluh3bakFrKQja9TNMJ19yK','Admin','Ativo','2026-08-05 08:21:44','2026-07-29 08:29:44','2026-08-05 08:21:44');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'mapa_de_sala'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-05  8:46:39
