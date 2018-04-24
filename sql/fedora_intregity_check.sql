-- MySQL dump 10.13  Distrib 5.1.73, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: fedora_integrity_check
-- ------------------------------------------------------
-- Server version       5.1.73

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE IF NOT EXISTS fedora_integrity_check;

USE fedora_integrity_check;

--
-- Table structure for table `datastreamStore`
--

DROP TABLE IF EXISTS `datastreamStore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `datastreamStore` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `PID` varchar(64) NOT NULL DEFAULT '0',
  `dsid` varchar(16) NOT NULL DEFAULT '0',
  `dsVersionID` varchar(20) NOT NULL DEFAULT '0',
  `dsLabel` varchar(255) NOT NULL DEFAULT '0',
  `dsCreateDate` int(9) NOT NULL DEFAULT '0',
  `dsMIME` varchar(50) NOT NULL DEFAULT '0',
  `dsSize` int(5) NOT NULL DEFAULT '0',
  `dsLocation` varchar(255) NOT NULL DEFAULT '0',
  `dsChecksum` varchar(64) NOT NULL DEFAULT '0',
  `dsChecksumType` varchar(8) NOT NULL DEFAULT '0',
  `dsChecksumValid` tinyint(1) NOT NULL DEFAULT '0',
  `problem` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_pid_dsid` (`PID`,`dsid`)
) ENGINE=InnoDB AUTO_INCREMENT=228646 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `objectStore`
--

DROP TABLE IF EXISTS `objectStore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `objectStore` (
  `offset` int(11) NOT NULL,
  `PID` varchar(64) DEFAULT NULL,
  `full_filename` varchar(200) DEFAULT NULL,
  `Label` varchar(255) DEFAULT NULL,
  `models` varchar(64) DEFAULT NULL,
  `Owner` varchar(50) DEFAULT NULL,
  `timestamp` int(9) DEFAULT NULL,
  `problem` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`offset`),
  UNIQUE KEY `Index 2` (`PID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-04-16 15:26:29

