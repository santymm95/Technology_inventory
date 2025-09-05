CREATE TABLE actas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT(20) NOT NULL,
    user_id BIGINT(20) NOT NULL,
    fecha_entrega DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accesorios TEXT,
    observacion TEXT,
    fecha_devolucion DATE,
    estado_devolucion ENUM('Bueno','Regular','Malo'),
    observacion_devolucion TEXT,
    entregado_por VARCHAR(100),
    recibido_por VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

