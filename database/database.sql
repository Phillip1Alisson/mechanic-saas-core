-- Banco de dados para o SaaS de oficina mecânica
-- Execute este script para criar as tabelas necessárias.

CREATE DATABASE IF NOT EXISTS mechanic_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mechanic_saas;

-- Tabela de usuários (autenticação)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Tabela de clientes (PF/PJ) com soft delete
CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    type ENUM('PF', 'PJ') NOT NULL,
    document VARCHAR(20) NOT NULL COMMENT 'CPF (11 dígitos) ou CNPJ (14 dígitos)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete',
    INDEX idx_type (type),
    INDEX idx_document (document),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- Usuário inicial: email admin@mecanica.com, senha 123456 (hash bcrypt)
INSERT INTO users (email, password_hash) VALUES ('admin@mecanica.com', '$2y$10$jnC1RHHVYmza.TYW0/nBceDSFtgBCdxsqOHOaoIgpwBeItfHpowBO')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);
