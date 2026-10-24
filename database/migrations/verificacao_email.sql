ALTER TABLE usuarios
    ADD COLUMN email_verificado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    AFTER status;

CREATE TABLE IF NOT EXISTS codigos_verificacao_email (
    usuario_id INT NOT NULL PRIMARY KEY,
    codigo_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    KEY idx_codigos_verificacao_expira (expira_em)
) ENGINE=InnoDB;
