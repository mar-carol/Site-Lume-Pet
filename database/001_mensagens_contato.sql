CREATE TABLE IF NOT EXISTS mensagens_contato (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    telefone VARCHAR(20) NOT NULL,
    assunto VARCHAR(100) NULL,
    mensagem TEXT NULL,
    status ENUM('nova', 'lida', 'respondida') NOT NULL DEFAULT 'nova',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_mensagens_contato_status_criado (status, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;