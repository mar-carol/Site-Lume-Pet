USE lume_pet;

ALTER TABLE cartoes
    ADD COLUMN IF NOT EXISTS tipo_uso ENUM('credito','debito','ambos') NOT NULL DEFAULT 'ambos';

ALTER TABLE doacoes
    ADD COLUMN IF NOT EXISTS metodo_pagamento VARCHAR(40) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS enderecos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    apelido VARCHAR(60) NOT NULL DEFAULT 'Meu endereço',
    cep CHAR(8) NOT NULL,
    logradouro VARCHAR(150) NOT NULL,
    numero VARCHAR(30) NOT NULL,
    complemento VARCHAR(100) DEFAULT NULL,
    bairro VARCHAR(80) DEFAULT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado CHAR(2) NOT NULL,
    padrao TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_enderecos_usuario_padrao (usuario_id, padrao),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO enderecos
    (usuario_id, apelido, cep, logradouro, numero, cidade, estado, padrao)
SELECT u.id, 'Meu endereço', REPLACE(REPLACE(u.cep, '-', ''), ' ', ''),
       u.endereco, '', u.cidade, u.estado, 1
FROM usuarios u
WHERE COALESCE(u.cep, '') <> ''
  AND COALESCE(u.endereco, '') <> ''
  AND COALESCE(u.cidade, '') <> ''
  AND COALESCE(u.estado, '') <> ''
  AND NOT EXISTS (SELECT 1 FROM enderecos e WHERE e.usuario_id = u.id);

UPDATE enderecos
SET apelido = CONVERT(0x4D657520656E64657265C3A76F USING utf8mb4)
WHERE HEX(apelido) = '4D657520656E646572653F6F';

UPDATE enderecos
SET numero = ''
WHERE UPPER(TRIM(numero)) IN ('S/N', 'SN', 'S N');
