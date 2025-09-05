-- Tabla para registrar mantenimientos de impresoras

CREATE TABLE `printer_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `printer_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `responsible` varchar(100) DEFAULT NULL,
  `external` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `printer_id` (`printer_id`),
  CONSTRAINT `printer_maintenance_ibfk_1` FOREIGN KEY (`printer_id`) REFERENCES `printer` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
