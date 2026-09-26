-- P1 segurança: coluna de senha comporta password_hash (bcrypt/argon2),
-- flag de troca obrigatória e controle de tentativas de login.
ALTER TABLE usuarios MODIFY senha VARCHAR(255) NOT NULL;
ALTER TABLE usuarios ADD COLUMN trocar_senha TINYINT(1) NOT NULL DEFAULT 0;
-- quem ainda usa a senha padrão pública (admin123) é obrigado a trocar
UPDATE usuarios SET trocar_senha = 1 WHERE senha = MD5('admin123');

CREATE TABLE IF NOT EXISTS login_tentativas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(160) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  tentado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_lt_email (email, tentado_em),
  KEY idx_lt_ip (ip, tentado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
