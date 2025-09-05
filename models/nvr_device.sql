CREATE TABLE nvr_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internal_number VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    serial VARCHAR(100) NOT NULL,
    purchase_date DATE,
    supplier VARCHAR(100),
    type VARCHAR(100),
    decoding VARCHAR(100),
    inputs VARCHAR(100),
    connectivity VARCHAR(100),
    storage VARCHAR(100),
    transmission VARCHAR(100),
    `usage` VARCHAR(100),
    parts_description TEXT,
    image_folder VARCHAR(255), -- Folder where images are stored, named after internal_number
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
