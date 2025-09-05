CREATE TABLE vehicle_novedad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    novedad TEXT NOT NULL,
    usuario VARCHAR(100) NOT NULL,
    fecha DATETIME NOT NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicle(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
