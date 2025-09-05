-- Tabla extendida para equipos (impresoras, cámaras, aires, etc.)

CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internal_number VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    serial VARCHAR(100) NOT NULL,
    purchase_date DATE NOT NULL,
    device_type VARCHAR(50) NOT NULL,
    provider VARCHAR(100),
    specs TEXT,
    host_name VARCHAR(100),
    fax_speed VARCHAR(50),
    duplex VARCHAR(50),
    connectivity VARCHAR(100),
    front_panel VARCHAR(100),
    features VARCHAR(255),
    filter_type VARCHAR(100),
    print_speed VARCHAR(50),
    ip_url VARCHAR(100),
    voltage VARCHAR(50),
    ink_cartridges VARCHAR(100),
    parts VARCHAR(255),
    parts_desc TEXT,
    photo VARCHAR(255),
    parts_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
