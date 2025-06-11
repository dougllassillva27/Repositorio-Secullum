CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dashboard_name VARCHAR(100) NULL DEFAULT NULL, -- << CAMPO ADICIONADO
    ordem INT NOT NULL DEFAULT 0
);

CREATE TABLE links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    text VARCHAR(100) NOT NULL,
    url VARCHAR(2048) NOT NULL,
    dashboard_text VARCHAR(100),
    icon VARCHAR(50),
    showOnDashboard BOOLEAN DEFAULT true,
    openInNewTab BOOLEAN DEFAULT false,
    visibilidade ENUM('all', 'admin') NOT NULL DEFAULT 'all',
    ordem INT NOT NULL DEFAULT 0,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);