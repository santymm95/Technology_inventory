CREATE TABLE `air_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `air_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `responsible` varchar(100) DEFAULT NULL,
  `external` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `air_id` (`air_id`),
  CONSTRAINT `air_maintenance_ibfk_1` FOREIGN KEY (`air_id`) REFERENCES `air` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
