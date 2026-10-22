USE lume_pet;

ALTER TABLE compras
    ADD COLUMN IF NOT EXISTS chave_externa CHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS origem ENUM('checkout_simulado','historico_local') NOT NULL DEFAULT 'checkout_simulado',
    ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS frete DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS desconto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS metodo_pagamento VARCHAR(40) NOT NULL DEFAULT 'Não informado',
    ADD COLUMN IF NOT EXISTS pago_em DATETIME DEFAULT NULL,
    MODIFY COLUMN endereco_entrega VARCHAR(500) DEFAULT NULL;

ALTER TABLE compras
    ADD UNIQUE KEY IF NOT EXISTS uq_compras_chave_externa (chave_externa);

ALTER TABLE compra_itens
    MODIFY COLUMN produto_id INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS nome_produto VARCHAR(150) DEFAULT NULL;

UPDATE compra_itens ci
JOIN produtos p ON p.id = ci.produto_id
SET ci.nome_produto = p.nome
WHERE ci.nome_produto IS NULL;

UPDATE compras c
JOIN (
    SELECT compra_id, SUM(quantidade * preco_unitario) AS subtotal_itens
    FROM compra_itens
    GROUP BY compra_id
) itens ON itens.compra_id = c.id
SET c.subtotal = itens.subtotal_itens
WHERE c.subtotal = 0;

UPDATE compras
SET pago_em = criado_em
WHERE status IN ('pago', 'enviado', 'entregue') AND pago_em IS NULL;
