CREATE TABLE `nvr_maintenance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nvr_id` INT(11) NOT NULL,
  `date` DATE NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `responsible` VARCHAR(100) NOT NULL,
  `external` VARCHAR(100) DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `nvr_id` (`nvr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
