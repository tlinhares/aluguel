-- ============================================================
-- SISTEMA DE CONTROLE DE ALUGUEL
-- Banco de dados: aluguel_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS aluguel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aluguel_db;

-- ============================================================
-- USUARIOS DO SISTEMA
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin','operador') NOT NULL DEFAULT 'operador',
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    trocar_senha TINYINT(1) NOT NULL DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_tentativas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(160) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    tentado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lt_email (email, tentado_em),
    KEY idx_lt_ip (ip, tentado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- senha inicial admin123 (hash bcrypt); troca obrigatória no 1º acesso
INSERT INTO usuarios (nome, email, senha, nivel, status, trocar_senha) VALUES 
('Administrador', 'admin@sistema.com', '$2y$10$uMNKBb857oq9BV9Cvrc0K.hTrZSI0fv/DcTlA9oKG4efWudkvlCy2', 'admin', 'ativo', 1);

-- ============================================================
-- INQUILINOS
-- ============================================================
CREATE TABLE IF NOT EXISTS inquilinos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),
    rg VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(100),
    profissao VARCHAR(100),
    renda DECIMAL(10,2) DEFAULT 0,
    estado_civil ENUM('solteiro','casado','divorciado','viuvo','outro') DEFAULT 'solteiro',
    cep VARCHAR(10),
    logradouro VARCHAR(200),
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    observacoes TEXT,
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- PROPRIETARIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS proprietarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf_cnpj VARCHAR(18),
    rg VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(100),
    cep VARCHAR(10),
    logradouro VARCHAR(200),
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    banco VARCHAR(100),
    agencia VARCHAR(20),
    conta VARCHAR(30),
    tipo_conta ENUM('corrente','poupanca') DEFAULT 'corrente',
    pix VARCHAR(100),
    observacoes TEXT,
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- IMOVEIS
-- ============================================================
CREATE TABLE IF NOT EXISTS imoveis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proprietario_id INT NOT NULL,
    tipo ENUM('casa','apartamento','comercial','terreno','sala','outro') NOT NULL DEFAULT 'casa',
    descricao VARCHAR(200),
    cep VARCHAR(10),
    logradouro VARCHAR(200) NOT NULL,
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    area DECIMAL(10,2),
    quartos TINYINT DEFAULT 0,
    banheiros TINYINT DEFAULT 0,
    vagas TINYINT DEFAULT 0,
    valor_aluguel DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_condominio DECIMAL(10,2) DEFAULT 0,
    valor_iptu DECIMAL(10,2) DEFAULT 0,
    status ENUM('disponivel','alugado','manutencao','inativo') NOT NULL DEFAULT 'disponivel',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proprietario_id) REFERENCES proprietarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- CONTRATOS
-- ============================================================
CREATE TABLE IF NOT EXISTS contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    imovel_id INT NOT NULL,
    inquilino_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    valor_aluguel DECIMAL(10,2) NOT NULL,
    dia_vencimento TINYINT NOT NULL DEFAULT 10,
    caucao DECIMAL(10,2) DEFAULT 0,
    caucao_pago TINYINT DEFAULT 0,
    indices_reajuste ENUM('IGPM','IPCA','INPC','fixo') DEFAULT 'IGPM',
    multa_rescisao DECIMAL(5,2) DEFAULT 0,
    status ENUM('ativo','encerrado','rescindido','pendente') NOT NULL DEFAULT 'ativo',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE RESTRICT,
    FOREIGN KEY (inquilino_id) REFERENCES inquilinos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- PARCELAS (CONTAS A RECEBER)
-- ============================================================
CREATE TABLE IF NOT EXISTS parcelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT NOT NULL,
    competencia VARCHAR(7) NOT NULL,
    data_vencimento DATE NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    valor_pago DECIMAL(10,2) DEFAULT 0,
    multa DECIMAL(10,2) DEFAULT 0,
    juros DECIMAL(10,2) DEFAULT 0,
    desconto DECIMAL(10,2) DEFAULT 0,
    data_pagamento DATE,
    forma_pagamento ENUM('dinheiro','pix','transferencia','boleto','cheque','cartao') DEFAULT 'pix',
    status ENUM('pendente','pago','atrasado','cancelado') NOT NULL DEFAULT 'pendente',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- RECIBOS
-- ============================================================
CREATE TABLE IF NOT EXISTS recibos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    parcela_id INT NOT NULL,
    contrato_id INT NOT NULL,
    inquilino_id INT NOT NULL,
    imovel_id INT NOT NULL,
    competencia VARCHAR(7) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    multa DECIMAL(10,2) DEFAULT 0,
    juros DECIMAL(10,2) DEFAULT 0,
    desconto DECIMAL(10,2) DEFAULT 0,
    valor_total DECIMAL(10,2) NOT NULL,
    data_pagamento DATE NOT NULL,
    forma_pagamento ENUM('dinheiro','pix','transferencia','boleto','cheque','cartao') DEFAULT 'pix',
    emitido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_recibos_parcela (parcela_id),
    FOREIGN KEY (parcela_id) REFERENCES parcelas(id),
    FOREIGN KEY (contrato_id) REFERENCES contratos(id),
    FOREIGN KEY (inquilino_id) REFERENCES inquilinos(id),
    FOREIGN KEY (imovel_id) REFERENCES imoveis(id)
) ENGINE=InnoDB;

-- ============================================================
-- MANUTENCOES
-- ============================================================
CREATE TABLE IF NOT EXISTS manutencoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imovel_id INT NOT NULL,
    contrato_id INT,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    tipo ENUM('eletrica','hidraulica','estrutural','pintura','jardinagem','limpeza','outro') DEFAULT 'outro',
    prioridade ENUM('baixa','media','alta','urgente') DEFAULT 'media',
    custo DECIMAL(10,2) DEFAULT 0,
    responsavel VARCHAR(100),
    data_abertura DATE NOT NULL,
    data_prevista DATE,
    data_conclusao DATE,
    status ENUM('aberta','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'aberta',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- CONFIGURACOES DO SISTEMA
-- ============================================================
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(200)
) ENGINE=InnoDB;

INSERT INTO configuracoes (chave, valor, descricao) VALUES
('empresa_nome', 'Imobiliária Sistema', 'Nome da empresa'),
('empresa_cnpj', '00.000.000/0001-00', 'CNPJ da empresa'),
('empresa_telefone', '(00) 0000-0000', 'Telefone da empresa'),
('empresa_email', 'contato@empresa.com', 'E-mail da empresa'),
('empresa_endereco', 'Rua Exemplo, 123 - Bairro - Cidade/UF', 'Endereço da empresa'),
('multa_atraso', '2', 'Percentual de multa por atraso (%)'),
('juros_atraso', '1', 'Percentual de juros mensais por atraso (%)'),
('prazo_aviso_vencimento', '5', 'Dias antes do vencimento para alerta');
