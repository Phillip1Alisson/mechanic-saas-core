-- Seed: usuário inicial
-- Email: admin@mecanica.com
-- Senha: 123456 (armazenada como hash bcrypt)
-- Execute após criar as tabelas com database.sql: mysql -u user -p mechanic_saas < database/seed_initial_user.sql

USE mechanic_saas;

INSERT INTO users (email, password_hash) VALUES (
    'admin@mecanica.com',
    '$2y$10$jnC1RHHVYmza.TYW0/nBceDSFtgBCdxsqOHOaoIgpwBeItfHpowBO'
) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);
