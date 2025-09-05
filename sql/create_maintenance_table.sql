CREATE TABLE `maintenance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `device_id` INT(11) NOT NULL,
  `device_type` VARCHAR(32) NOT NULL, -- e.g. 'nvr', 'pc', etc.
  `date` DATE NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `responsible` VARCHAR(100) NOT NULL,
  `external` VARCHAR(100) DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
