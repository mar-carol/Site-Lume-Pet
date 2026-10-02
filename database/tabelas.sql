-- =========================================================
-- LUME PET - Schema do banco de dados
-- =========================================================
CREATE DATABASE IF NOT EXISTS lume_pet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lume_pet;

-- ---------------------------------------------------------
-- USUÁRIOS
-- ---------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    cidade VARCHAR(100) DEFAULT NULL,
    estado VARCHAR(2) DEFAULT NULL,
    cep VARCHAR(10) DEFAULT NULL,
    pontos INT NOT NULL DEFAULT 0,
    tipo ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CARTÕES (TOKENIZADOS — nunca número completo nem CVV)
-- ---------------------------------------------------------
CREATE TABLE cartoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    gateway VARCHAR(50) NOT NULL,
    gateway_customer_id VARCHAR(100) DEFAULT NULL,
    gateway_token VARCHAR(255) NOT NULL,
    bandeira VARCHAR(30) DEFAULT NULL,
    ultimos_digitos CHAR(4) NOT NULL,
    nome_titular VARCHAR(150) NOT NULL,
    validade_mes TINYINT DEFAULT NULL,
    validade_ano SMALLINT DEFAULT NULL,
    padrao TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PRODUTOS
-- ---------------------------------------------------------
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    categoria VARCHAR(80) DEFAULT NULL,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    imagem VARCHAR(255) DEFAULT NULL,
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE produto_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    acao ENUM('criado','editado','excluido','estoque_alterado') NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    dados_antes JSON DEFAULT NULL,
    dados_depois JSON DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- COMPRAS
-- ---------------------------------------------------------
CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cartao_id INT DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pendente','pago','enviado','entregue','cancelado') NOT NULL DEFAULT 'pendente',
    endereco_entrega VARCHAR(255) DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (cartao_id) REFERENCES cartoes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE compra_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compra_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ONGs PARCEIRAS
-- ---------------------------------------------------------
CREATE TABLE ongs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cnpj VARCHAR(20) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    endereco VARCHAR(255) DEFAULT NULL,
    responsavel VARCHAR(150) DEFAULT NULL,
    descricao TEXT,
    logo VARCHAR(255) DEFAULT NULL,
    status ENUM('pendente','parceira','inativa') NOT NULL DEFAULT 'pendente',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ANIMAIS DISPONÍVEIS PARA ADOÇÃO
-- ---------------------------------------------------------
CREATE TABLE animais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ong_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    especie VARCHAR(50) NOT NULL,
    raca VARCHAR(80) DEFAULT NULL,
    idade_aproximada VARCHAR(30) DEFAULT NULL,
    porte ENUM('pequeno','medio','grande') DEFAULT NULL,
    sexo ENUM('macho','femea') DEFAULT NULL,
    descricao TEXT,
    foto VARCHAR(255) DEFAULT NULL,
    status ENUM('disponivel','em_processo','adotado') NOT NULL DEFAULT 'disponivel',
    adotante_usuario_id INT DEFAULT NULL,
    data_adocao DATETIME DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ong_id) REFERENCES ongs(id) ON DELETE CASCADE,
    FOREIGN KEY (adotante_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- DOAÇÕES
-- ---------------------------------------------------------
CREATE TABLE doacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ong_id INT NOT NULL,
    cartao_id INT DEFAULT NULL,
    valor DECIMAL(10,2) NOT NULL,
    pontos_gerados INT NOT NULL DEFAULT 0,
    status ENUM('pendente','confirmada','cancelada') NOT NULL DEFAULT 'pendente',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (ong_id) REFERENCES ongs(id) ON DELETE CASCADE,
    FOREIGN KEY (cartao_id) REFERENCES cartoes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- HISTÓRICO DE PONTOS / CASHBACK
-- ---------------------------------------------------------
CREATE TABLE pontos_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('doacao','compra','resgate','ajuste') NOT NULL,
    referencia_id INT DEFAULT NULL,
    pontos INT NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;




-- Usuário admin padrão (senha: lumepet2026 -- TROQUE depois do primeiro login)
INSERT INTO usuarios (nome, email, senha_hash, telefone, endereco, tipo)
VALUES ('Administrador', 'admin@lumepet.com', '$2b$10$D2oL8jfafZMGb/0eF1i7uuxIJ579kKjiqLjym4N83rhzyHE7yonI2', '(00) 00000-0000', 'Sede Lume Pet', 'admin');

INSERT INTO usuarios (nome, email, senha_hash, tipo)
VALUES
('Admin Zambianco', 'admin1@lumepet.com', '$2b$10$57kf6x81Q0L9LIn5muimXuPmq9F19trsVrwCqDpzKgkSa2TicNnAq', 'admin'),
('Admin Loris', 'admin2@lumepet.com', '$2b$10$.7qpi7DMRSGAM/Vrtx1JWu/gCjDsXhNFiX9yJ7ZI9iSbSwc5wHlP2', 'admin'),
('Admin Miciano', 'admin3@lumepet.com', '$2b$10$VzJCKg62hXzMq3vnoZOStuqepEXz/oF0axxnANGgxda9450QS7YAe', 'admin'),
('Admin Duda', 'admin4@lumepet.com', '$2b$10$TQWDxhLcIsuR9lNtA46Zfuu8zbhuX4Wp44cLwpUkGiUM15bpEK/t6', 'admin'),
('Admin Couto', 'admin5@lumepet.com', '$2b$10$pjQXJ9WsA14eEfBqS6ykLu9DwhRRlFAj5GDR43Dz.vruT//d6aBfu', 'admin');

