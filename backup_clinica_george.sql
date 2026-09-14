-- MySQL dump 10.13  Distrib 9.7.1, for macos26.4 (arm64)
--
-- Host: localhost    Database: clinica_george
-- ------------------------------------------------------
-- Server version	9.7.1

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
-- Table structure for table `clinic_content`
--

DROP TABLE IF EXISTS `clinic_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_content` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paragraph_1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `paragraph_2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `highlight_quote` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '/assets/images/clinic.png',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic_content`
--

LOCK TABLES `clinic_content` WRITE;
/*!40000 ALTER TABLE `clinic_content` DISABLE KEYS */;
INSERT INTO `clinic_content` VALUES (1,'Dr. George Scapin','Biomédico Esteta | CRBM 5202 - Especialista em Harmonização Facial Avançada','<p>Com formação sólida e dedicação exclusiva à estética avançada e ao gerenciamento do envelhecimento, o <strong>Dr. George Scapin</strong> combina conhecimento anatômico aprofundado e visão artística refinada.</p>','<p>Cada paciente recebe um plano de tratamento 360°<strong> </strong>totalmente individualizado, utilizando produtos de padrão ouro e tecnologias modernas para proporcionar rejuvenescimento seguro, duradouro e com máxima naturalidade.</p>','A verdadeira elegância está na naturalidade e no respeito aos seus traços.','/assets/img/drgeorge.jpeg','2026-08-24 17:31:59');
/*!40000 ALTER TABLE `clinic_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'novo',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,'Mariana Silva','(51) 98765-4321','Olá, gostaria de saber o valor da harmonização facial full face e os horários disponíveis para avaliação.','novo','2026-08-16 13:51:29'),(2,'teste 123','51993573343','sdfdfd','novo','2026-08-16 13:52:30'),(3,'vagner teste','51993573343','asklskd','novo','2026-08-17 08:39:49');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_pages`
--

DROP TABLE IF EXISTS `custom_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `banner_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_pages`
--

LOCK TABLES `custom_pages` WRITE;
/*!40000 ALTER TABLE `custom_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hero_content`
--

DROP TABLE IF EXISTS `hero_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hero_content` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `button_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Agendar Consulta',
  `button_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '/contato',
  `bg_image_dark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '/assets/images/hero.png',
  `bg_image_light` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '/assets/images/hero_light.png',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hero_content`
--

LOCK TABLES `hero_content` WRITE;
/*!40000 ALTER TABLE `hero_content` DISABLE KEYS */;
INSERT INTO `hero_content` VALUES (1,'Gerenciamento do Envelhecimento,<br> e Harmonização Facial.','Resultados que transcendem o tempo. Harmonização sofisticada com o rigor e a excelência que sua beleza merece.','Agende sua consulta','/contato','/assets/images/hero.png','/assets/images/hero_light.png','2026-08-24 17:31:08');
/*!40000 ALTER TABLE `hero_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '_self',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `is_button` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (1,'Início','/','_self',1,1,0,'2026-08-17 00:35:08','2026-08-17 00:35:08'),(2,'A Clínica','/clinica','_self',2,1,0,'2026-08-17 00:35:08','2026-08-17 11:41:55'),(3,'Procedimentos','/procedimentos','_self',3,1,0,'2026-08-17 00:35:08','2026-08-17 00:35:08'),(4,'Full Face','/harmonizacao-facial','_self',4,1,0,'2026-08-17 00:35:08','2026-08-17 00:35:08'),(5,'Blog','/blog','_self',5,1,0,'2026-08-17 00:35:08','2026-08-17 00:35:08'),(6,'Contato','/contato','_self',6,1,0,'2026-08-17 00:35:08','2026-08-17 00:35:08');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
INSERT INTO `newsletter_subscribers` VALUES (1,'paciente.exemplo@gmail.com','ativo','2026-08-16 13:54:44'),(2,'vameri@gmail.com','ativo','2026-08-17 08:40:18');
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Dr. George',
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,'Toxina Botulínica: O Segredo da Prevenção e Naturalidade','toxina-botulinica','Dr. George','Descubra como a toxina botulínica vai muito além de tratar rugas, atuando de forma preventiva para manter um rosto descansado e jovial.','<p>Muitos pacientes chegam ao meu consultório com receio de perder a expressão facial. O que eu sempre reforço é que a toxina botulínica moderna, quando aplicada com técnica refinada, não paralisa o rosto. Pelo contrário, ela suaviza as expressões e previne o aprofundamento das linhas dinâmicas.</p><p>A durabilidade do procedimento varia de 3 a 5 meses, dependendo do metabolismo e estilo de vida de cada paciente. A prevenção é a chave do sucesso: iniciar o tratamento antes que as rugas se tornem estáticas (marcadas mesmo sem expressão) garante resultados extremamente mais naturais.</p><p>O meu foco é devolver o ar de descanso e a vivacidade que o tempo e o estresse diário tendem a apagar, sempre priorizando a sua anatomia única. Uma avaliação cuidadosa permite que a dosagem seja exata, entregando a você não um rosto congelado, mas sim a sua melhor versão.</p>','/assets/botox.png',1,'2026-08-16 13:08:40','2026-08-16 13:08:40'),(2,'A Arte do Preenchimento Labial Seguro e Sofisticado','preenchimento-labial','Dr. George','O preenchimento labial ideal é aquele que realça sua beleza sem exageros. Entenda os pilares para um contorno perfeito e seguro.','<p>Lábios bem desenhados transmitem jovialidade, sensualidade e elegância. No entanto, o medo de resultados exagerados ou artificiais ainda afasta muitas pessoas do preenchimento labial com ácido hialurônico.</p><p>A chave para um resultado elegante está no respeito à anatomia e às proporções áureas de cada rosto. O lábio inferior naturalmente deve ser ligeiramente mais volumoso que o superior, e o contorno do arco do cupido precisa ser esculpido com delicadeza.</p><p>Utilizando produtos de primeira linha com alta maleabilidade e biocompatibilidade, conseguimos devolver a hidratação, corrigir assimetrias e proporcionar um volume sutil que se harmoniza perfeitamente com os seus traços.</p>','/assets/lips.png',1,'2026-08-16 13:08:40','2026-08-16 13:08:40'),(3,'Primeiros Passos na Estética Avançada: Qual o Tratamento Ideal?','procedimentos-esteticos','Dr. George','Dar o primeiro passo nos procedimentos estéticos pode gerar dúvidas. Veja como uma avaliação individualizada muda tudo.','<p>Iniciar uma jornada de cuidados estéticos avançados é uma decisão importante que deve ser pautada pela confiança e pelo alinhamento de expectativas. Muitas pessoas sentem vontade de melhorar algum aspecto da face, mas não sabem por onde começar.</p><p>O primeiro passo é sempre uma consulta de avaliação 360°, onde analisamos a qualidade da pele, a dinâmica muscular, a perda de sustentação óssea e de compartimentos de gordura. A partir desse diagnóstico, traçamos um plano de tratamento personalizado em etapas.</p><p>A estética moderna não busca transformar você em outra pessoa, mas sim gerenciar o envelhecimento para que você se sinta radiante e confiante em todas as fases da vida.</p>','/assets/fullface.png',1,'2026-08-16 13:08:40','2026-08-16 13:08:40'),(4,'Harmonização Facial: Alta Performance','harmonizacao-facial','Dr. George','O conceito de harmonização vai além da face. Descubra como tecnologias avançadas podem esculpir contornos e tratar flacidez corporal.','<p>A busca por um contorno corporal harmônico, firme e bem definido tem impulsionado tratamentos de alta performance que vão muito além de cirurgias invasivas.</p><p>A harmonização corporal combina bioestimuladores de colágeno, preenchedores corporais para glúteos e áreas específicas, e protocolos de combate à flacidez e celulite. Esses procedimentos estimulam a produção natural de colágeno e melhoram a densidade tecidual.</p><p>Com recuperação rápida e resultados progressivos, é possível conquistar firmeza, contorno e melhora significativa da textura da pele com total segurança e embasamento científico.</p>','/assets/clinic.png',1,'2026-08-16 13:08:40','2026-08-24 17:30:13');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `procedures`
--

DROP TABLE IF EXISTS `procedures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `procedures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_description` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'sparkles',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `procedures`
--

LOCK TABLES `procedures` WRITE;
/*!40000 ALTER TABLE `procedures` DISABLE KEYS */;
INSERT INTO `procedures` VALUES (1,'Toxina Botulínica','toxina-botulinica','Aplicação preventiva para suavização de linhas de expressão com naturalidade absoluta.','Procedimento avançado de toxina botulínica.','/assets/img/01.jpeg','sparkles',1,1,'2026-08-16 12:27:12','2026-08-16 15:52:46'),(2,'Prevenção e Suavização de Rugas','prevencao-rugas','Ajuste fino da sua rotina e protocolos combinados para rejuvenescer sem exageros, tratando rugas estáticas e dinâmicas.','Combinando bioestimuladores de colágeno, peelings de alta precisão e hidratação injetável profunda (Skinboosters), tratamos as rugas em todas as suas camadas, estimulando a regeneração celular e a firmeza tecidual.','/assets/img/02.jpeg','scan-face',2,1,'2026-08-16 12:27:12','2026-08-16 13:09:52'),(3,'Harmonização Facial Full Face','harmonizacao-facial','A Harmonização Facial é um conjunto de procedimentos estéticos realizados com o objetivo de equilibrar e realçar os traços faciais, proporcionando uma aparência mais harmônica e rejuvenescida.','Com planejamento arquitetônico 360°, estruturamos malar, mandíbula, queixo, têmporas e lábios com ácido hialurônico de padrão ouro. O resultado é um rejuvenescimento global que valoriza a sua beleza natural.','/assets/img/03.jpeg','user',3,1,'2026-08-16 12:27:12','2026-08-16 13:09:52'),(4,'Harmonização Corporal','harmonizacao-corporal','A Harmonização Corporal é um conjunto de procedimentos estéticos realizados com o objetivo de tratar afecções estéticas como celulite, gordura localizada e flacidez, além de modelar e esculpir os contornos do corpo.','Utilizando bioestimuladores corporais (como ácido polilático e hidroxiapatita de cálcio) e preenchedores de alta densidade, conseguimos remodelar glúteos, tratar a flacidez de braços, abdômen e coxas com excelência e segurança.','/assets/img/04.jpeg','activity',4,1,'2026-08-16 13:09:52','2026-08-16 13:09:52');
/*!40000 ALTER TABLE `procedures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'site_title','Dr. George Scapin | Harmonização e Estética Facial Avançada em RS','2026-08-24 17:31:08'),(2,'meta_description','Clínica Dr. George Scapin em Porto Alegre. Especialista em Estética Facial, Toxina Botulínica, Preenchimento e Harmonização Full Face. Agende sua consulta.','2026-08-24 17:31:08'),(3,'meta_keywords','Dr. George Scapin, estética facial, harmonização facial, toxina botulínica, preenchimento facial, full face, clínica de estética Porto Alegre, rejuvenescimento','2026-08-24 17:31:08'),(4,'contact_phone','(51) 99824-4379','2026-08-24 17:31:08'),(5,'contact_whatsapp','5551998244379','2026-08-24 17:31:08'),(6,'contact_address','Av. Ipiranga, 40, sala 1512 - Praia de Belas, Porto Alegre - RS | CEP 90160-090','2026-08-24 17:31:08'),(7,'contact_hours_week','Seg - Sex: 08h às 19h','2026-08-24 17:31:08'),(8,'contact_hours_sat','Sáb: 09h às 13h','2026-08-24 17:31:08');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Dr. George Scapin','admin@drgeorgescapin.com.br','$2y$12$1wGmKc8IUNqm42ESLmmN4OD0qv71WF8eXeX6nU0LASy5tvIEhHDWC','2026-08-16 12:27:12','2026-08-16 12:58:46');
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

-- Dump completed on 2026-09-01 10:05:15
