CREATE TABLE `air` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `internal_number` varchar(50) NOT NULL,
  `model` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `serial` varchar(50) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `specs` text DEFAULT NULL,
  `refrigerant` varchar(50) DEFAULT NULL,
  `capacity` varchar(50) DEFAULT NULL,
  `voltage` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `internal_number` (`internal_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
