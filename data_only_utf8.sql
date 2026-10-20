-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: prac_crud
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` (`ProductID`, `ProductName`, `Unit`, `IsReturnable`, `CategoryID`, `SupplierID`, `Brand`, `SKU`, `Barcode`, `Description`, `Material`, `ProfileType`, `Color`, `Length`, `LengthUnit`, `Width`, `WidthUnit`, `Thickness`, `Weight`, `WeightUnit`, `SellingPrice`, `CostPrice`, `OpeningStock`, `ReorderLevel`, `Product_Image`, `created_at`, `updated_at`) VALUES (1001,'Long Span Roofing Sheet','Sheet',1,1,1,'RoofMaster','RMS-LS-001','9300617065029','Pre-painted roofing sheet','Galvanized Steel','Rib Type','Red',6.00,'m',1.00,'m','0.50',24.50,'kg',1850.00,1450.00,120,20,'longspan_red.jpg','2026-06-11 19:42:58','2026-07-27 10:07:13'),(1002,'Corrugated Roofing Sheet','Sheet',1,1,2,'BuildPro','BPS-CR-002',NULL,'Corrugated roofing sheet','Galvanized Iron','Corrugated','Silver',8.00,'ft',32.00,'in','0.40',18.00,'kg',950.00,720.00,200,30,'corrugated.jpg','2026-06-11 19:42:58','2026-06-11 19:42:58'),(1003,'Roof Ridge Roll','Piece',1,2,1,'RoofMaster','RMS-RR-003',NULL,'Ridge cap for roofing systems','Galvanized Steel','Ridge Roll','Red',2.40,'m',0.45,'m','0.50',5.00,'kg',450.00,320.00,80,10,'ridge_roll.jpg','2026-06-11 19:42:58','2026-06-11 19:42:58'),(1004,'Tek Screw 2in','Box',0,3,4,'FixPro','FPS-TS-004',NULL,'Self-drilling roofing screws','Steel','Fastener','Silver',NULL,NULL,NULL,NULL,NULL,2.50,'kg',350.00,250.00,150,30,'tek_screw.jpg','2026-06-11 19:42:58','2026-06-23 22:18:23'),(1005,'Bubble Foil Insulation','Roll',1,4,5,'ThermoShield','TSI-BF-005',NULL,'Reflective roof insulation','Foil Bubble','Insulation','Silver',30.00,'m',1.20,'m','4.00',8.50,'kg',2800.00,2200.00,25,5,'foil_insulation.jpg','2026-06-11 19:42:58','2026-06-29 11:30:50'),(1006,'C-Purlin 2x4','Piece',1,6,2,'SteelBuild','SB-CP-006',NULL,'Structural C-Purlin for roof framing','Galvanized Steel','C-Purlin','Silver',6.00,'m',100.00,'mm','1.50',16.50,'kg',980.00,760.00,120,20,'c_purlin.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1007,'Z-Purlin 2x6','Piece',1,6,2,'SteelBuild','SB-ZP-007',NULL,'Galvanized Z-Purlin for roofing support','Galvanized Steel','Z-Purlin','Silver',6.00,'m',150.00,'mm','2.00',21.00,'kg',1450.00,1180.00,80,15,'z_purlin.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1008,'Roof Gutter','Piece',1,2,3,'RainFlow','RF-GT-008',NULL,'Pre-painted rain gutter','Galvanized Steel','Gutter','White',2.40,'m',150.00,'mm','0.50',4.50,'kg',650.00,480.00,60,10,'gutter.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1009,'Downspout Pipe','Piece',1,2,3,'RainFlow','RF-DP-009',NULL,'Rainwater downspout pipe','Galvanized Steel','Downspout','White',2.40,'m',75.00,'mm','0.50',3.20,'kg',420.00,300.00,75,10,'downspout.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1010,'Roof Sealant Silicone','Tube',0,7,4,'SealTech','ST-RS-010',NULL,'Weatherproof silicone sealant','Silicone','Sealant','Clear',NULL,NULL,NULL,NULL,NULL,0.30,'kg',185.00,125.00,200,40,'roof_sealant.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1011,'Butyl Flashing Tape','Roll',1,7,4,'SealTech','ST-BT-011',NULL,'Self-adhesive waterproof flashing tape','Butyl Rubber','Flashing Tape','Silver',10.00,'m',100.00,'mm','1.50',1.10,'kg',480.00,340.00,100,20,'flashing_tape.jpg','2026-08-04 20:01:46','2026-08-04 20:01:46'),(1012,'Roofing Nail 2in','Box',0,3,4,'FixPro',NULL,NULL,'Galvanized roofing nails','Steel','Roofing Nail','Silver',NULL,NULL,NULL,NULL,NULL,5.00,'kg',390.00,280.00,180,40,'products/cHNHIrv0vHPYLTAI7Me2C78DeRRCoTC8TymMZabn.jpg','2026-08-04 20:01:46','2026-08-17 16:00:22'),(1013,'Metal Fascia Board','Piece',1,2,1,'RoofMaster',NULL,NULL,'Pre-painted metal fascia board for roof edges','Galvanized Steel','Fascia','Brown',2.40,'m',200.00,'mm','0.45',3.80,'kg',780.00,590.00,50,10,'products/8TJCn08NOTU5TwQwlczXk8luu8F4wovJdjiZ0sfD.jpg','2026-08-04 20:01:46','2026-08-17 15:59:20'),(1014,'Roof Ventilator','Piece',1,2,5,'VentPro',NULL,NULL,'Wind-driven roof ventilator','Aluminum','Ventilator','Silver',NULL,NULL,500.00,'mm',NULL,5.50,'kg',3250.00,2650.00,20,5,'products/o1W92aMbyLoXsEgg7FV6p6E9Rc0RKKmnr2I3aT3R.jpg','2026-08-04 20:01:46','2026-08-17 15:58:09'),(1015,'Roofing Rivets','Box',0,3,4,'FixPro',NULL,NULL,'Aluminum blind rivets for roofing installation','Aluminum','Rivet','Silver',NULL,NULL,NULL,NULL,NULL,1.00,'kg',260.00,180.00,250,50,'products/1Hxzfjmp8J7Qn0RvxUYvyo996jVBZ1tZLy4642Yh.jpg','2026-08-04 20:01:46','2026-08-17 06:25:18'),(1017,'rew','Pack',0,3,NULL,NULL,'REW-00001',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',21.00,12.00,NULL,NULL,NULL,'2026-08-14 03:36:39','2026-08-14 03:36:39'),(1018,'sample','Pack',0,7,NULL,NULL,'SAM-00001',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',34.00,14.00,NULL,NULL,NULL,'2026-08-17 16:09:51','2026-08-17 16:09:51'),(1019,'SAMPLE1','Box',0,4,NULL,NULL,'SAM-00002',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',23.00,9.96,NULL,NULL,NULL,'2026-08-29 15:03:08','2026-08-29 15:03:08'),(1020,'SAMPLE2','Set',1,4,NULL,NULL,'SAM-00003',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',122.00,50.00,NULL,NULL,NULL,'2026-08-29 15:15:21','2026-08-29 15:15:21'),(1022,'SAMPLE3','Set',0,1,NULL,NULL,'SAM-00004',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',21.00,11.72,NULL,NULL,NULL,'2026-08-29 15:19:09','2026-08-29 15:19:09'),(1023,'SAMPLE 4','Box',0,5,NULL,NULL,'SAM-00005',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',21.00,11.99,NULL,NULL,NULL,'2026-08-29 15:21:45','2026-08-29 15:21:45'),(1024,'gg','Box',0,1,NULL,NULL,'GGX-00001','B2D21',NULL,'xz',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',12.00,5.00,NULL,NULL,NULL,'2026-09-17 10:10:57','2026-09-17 10:10:57'),(1025,'book','Pack',0,4,NULL,NULL,'BOO-00001','9781781257029',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'g',12.00,6.00,NULL,NULL,NULL,'2026-09-17 10:14:37','2026-09-17 10:14:37');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` (`CategoryID`, `CategoryName`, `Description`, `created_at`, `updated_at`) VALUES (1,'Roofing Sheets','Metal roofing sheets including long span, corrugated, and rib type profiles.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(2,'Roof Accessories','Ridge rolls, flashings, gutters, valleys, and trims.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(3,'Fasteners','Tek screws, rivets, washers, and roofing nails.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(4,'Insulation','Roof insulation materials such as foil and fiberglass.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(5,'Polycarbonate Panels','Transparent and translucent roofing panels for skylights and canopies.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(6,'Structural Steel','Purlins, C-channels, Z-purlins, and support framing materials.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(7,'Sealants and Adhesives','Roof sealants, silicone, and waterproofing compounds.','2026-06-11 19:40:57','2026-06-11 19:40:57'),(8,'Ceiling Materials','Ceiling boards, metal furring, and ceiling accessories.','2026-06-11 19:40:57','2026-06-11 19:40:57');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` (`SupplierID`, `SupplierName`, `ContactNumber`, `Email`, `Address`, `created_at`, `updated_at`) VALUES (1,'RoofMaster Supply Corporation','09171234567','sales@roofmaster.com','Quezon City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19'),(2,'BuildPro Construction Supplies','09181234567','info@buildpro.com','Makati City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19'),(3,'SteelTech Roofing Solutions','09191234567','contact@steeltech.com','Pasig City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19'),(4,'FixPro Fasteners Trading','09201234567','support@fixpro.com','Caloocan City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19'),(5,'ThermoShield Insulation Center','09211234567','sales@thermoshield.com','Valenzuela City, Metro Manila','2026-06-11 19:42:19','2026-06-11 19:42:19');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-26  0:50:02

