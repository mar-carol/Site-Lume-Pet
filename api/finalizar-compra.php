<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function responderPedidos(array $dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function dinheiroValido($valor, float $maximo): ?float
{
    if (!is_numeric($valor)) {
        return null;
    }
    $valor = (float) $valor;
    return is_finite($valor) && $valor >= 0 && $valor <= $maximo ? round($valor, 2) : null;
}

function importarPedidosAntigos(PDO $pdo, int $usuarioId, array $pedidos): int
{
    if (count($pedidos) > 100) {
        throw new InvalidArgumentException('Há pedidos demais para importar de uma só vez.');
    }

    $stmtExiste = $pdo->prepare('SELECT id FROM compras WHERE chave_externa = :chave');
    $stmtProduto = $pdo->prepare('SELECT id FROM produtos WHERE nome = :nome ORDER BY id DESC LIMIT 1');
    $stmtCompra = $pdo->prepare(
        'INSERT INTO compras
            (usuario_id, chave_externa, origem, subtotal, frete, desconto, total, status, metodo_pagamento, pago_em, criado_em)
         VALUES
            (:usuario_id, :chave, "historico_local", :subtotal, :frete, :desconto, :total, "pendente", :metodo, NULL, :criado_em)'
    );
    $stmtItem = $pdo->prepare(
        'INSERT INTO compra_itens (compra_id, produto_id, nome_produto, quantidade, preco_unitario)
         VALUES (:compra_id, :produto_id, :nome_produto, :quantidade, :preco_unitario)'
    );

    $importados = 0;
    foreach ($pedidos as $pedido) {
        if (!is_array($pedido) || !isset($pedido['id']) || !is_array($pedido['itens'] ?? null) || !$pedido['itens']) {
            throw new InvalidArgumentException('Um pedido antigo não tem os dados necessários para ser importado.');
        }

        $identidadeLocal = json_encode($pedido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($identidadeLocal === false) {
            throw new InvalidArgumentException('Um pedido antigo contém dados inválidos.');
        }
        $chave = hash('sha256', $usuarioId . ':local:' . $identidadeLocal);
        $stmtExiste->execute(['chave' => $chave]);
        if ($stmtExiste->fetchColumn()) {
            continue;
        }

        if (count($pedido['itens']) > 50) {
            throw new InvalidArgumentException('Um pedido antigo tem itens demais para ser importado.');
        }

        $itensNormalizados = [];
        $subtotalCalculado = 0.0;
        foreach ($pedido['itens'] as $item) {
            $nome = trim((string) ($item['nome'] ?? ''));
            $quantidade = filter_var($item['qtd'] ?? null, FILTER_VALIDATE_INT);
            $preco = dinheiroValido($item['preco'] ?? null, 100000);
            if ($nome === '' || mb_strlen($nome, 'UTF-8') > 150 || !$quantidade || $quantidade < 1 || $quantidade > 100 || $preco === null) {
                throw new InvalidArgumentException('Um item do histórico antigo contém informações inválidas.');
            }

            $stmtProduto->execute(['nome' => $nome]);
            $produtoId = $stmtProduto->fetchColumn();
            $itensNormalizados[] = [
                'produto_id' => $produtoId ? (int) $produtoId : null,
                'nome' => $nome,
                'quantidade' => $quantidade,
                'preco' => $preco,
            ];
            $subtotalCalculado += $preco * $quantidade;
        }

        $subtotal = dinheiroValido($pedido['subtotal'] ?? $subtotalCalculado, 10000000);
        $frete = dinheiroValido($pedido['frete'] ?? 0, 100000);
        $desconto = dinheiroValido($pedido['desconto'] ?? 0, 100000);
        $total = dinheiroValido($pedido['total'] ?? ($subtotal + $frete - $desconto), 10000000);
        if ($subtotal === null || $frete === null || $desconto === null || $total === null) {
            throw new InvalidArgumentException('Um pedido antigo contém valores inválidos.');
        }

        $data = DateTime::createFromFormat('!d/m/Y', (string) ($pedido['data'] ?? ''));
        if (!$data) {
            $data = new DateTime();
        }
        $metodo = trim((string) ($pedido['metodo'] ?? 'Não informado'));
        $metodo = mb_substr($metodo !== '' ? $metodo : 'Não informado', 0, 40, 'UTF-8');

        $pdo->beginTransaction();
        try {
            $stmtCompra->execute([
                'usuario_id' => $usuarioId,
                'chave' => $chave,
                'subtotal' => $subtotal,
                'frete' => $frete,
                'desconto' => $desconto,
                'total' => $total,
                'metodo' => $metodo,
                'criado_em' => $data->format('Y-m-d H:i:s'),
            ]);
            $compraId = (int) $pdo->lastInsertId();

            foreach ($itensNormalizados as $item) {
                $stmtItem->execute([
                    'compra_id' => $compraId,
                    'produto_id' => $item['produto_id'],
                    'nome_produto' => $item['nome'],
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco'],
                ]);
            }
            $pdo->commit();
            $importados++;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    return $importados;
}

if (!usuarioEstaLogado() || usuarioEhAdmin()) {
    responderPedidos(['sucesso' => false, 'erro' => 'Entre na sua conta de cliente para acessar os pedidos.'], 401);
}

$pdo = getConexao();
$usuarioId = (int) $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT c.id, c.total, c.subtotal, c.frete, c.desconto, c.status, c.origem,
                c.metodo_pagamento, c.endereco_entrega, c.criado_em, c.pago_em,
                ci.id AS item_id, ci.produto_id,
                COALESCE(ci.nome_produto, p.nome, "Produto removido") AS produto_nome,
                ci.quantidade, ci.preco_unitario
         FROM compras c
         LEFT JOIN compra_itens ci ON ci.compra_id = c.id
         LEFT JOIN produtos p ON p.id = ci.produto_id
         WHERE c.usuario_id = :usuario_id
         ORDER BY c.criado_em DESC, c.id DESC, ci.id ASC'
    );
    $stmt->execute(['usuario_id' => $usuarioId]);
    $compras = [];
    foreach ($stmt->fetchAll() as $linha) {
        $compraId = (int) $linha['id'];
        if (!isset($compras[$compraId])) {
            $compras[$compraId] = [
                'id' => $compraId,
                'total' => (float) $linha['total'],
                'subtotal' => (float) $linha['subtotal'],
                'frete' => (float) $linha['frete'],
                'desconto' => (float) $linha['desconto'],
                'status' => $linha['status'],
                'origem' => $linha['origem'],
                'metodo_pagamento' => $linha['metodo_pagamento'],
                'endereco_entrega' => $linha['endereco_entrega'],
                'criado_em' => $linha['criado_em'],
                'pago_em' => $linha['pago_em'],
                'itens' => [],
            ];
        }
        if ($linha['item_id'] !== null) {
            $compras[$compraId]['itens'][] = [
                'produto_id' => $linha['produto_id'] ? (int) $linha['produto_id'] : null,
                'nome' => $linha['produto_nome'],
                'quantidade' => (int) $linha['quantidade'],
                'preco_unitario' => (float) $linha['preco_unitario'],
            ];
        }
    }
    responderPedidos(['sucesso' => true, 'compras' => array_values($compras)]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    responderPedidos(['sucesso' => false, 'erro' => 'Método não permitido.'], 405);
}

if (!validarTokenCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    responderPedidos(['sucesso' => false, 'erro' => 'Sessão expirada. Atualize a página e tente novamente.'], 403);
}

$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) {
    responderPedidos(['sucesso' => false, 'erro' => 'Dados inválidos.'], 400);
}

try {
    if (($dados['acao'] ?? '') === 'importar_historico') {
        $pedidos = $dados['pedidos'] ?? null;
        if (!is_array($pedidos)) {
            responderPedidos(['sucesso' => false, 'erro' => 'Histórico de pedidos inválido.'], 400);
        }
        responderPedidos([
            'sucesso' => true,
            'importados' => importarPedidosAntigos($pdo, $usuarioId, $pedidos),
        ]);
    }

    if (($dados['acao'] ?? '') !== 'finalizar') {
        responderPedidos(['sucesso' => false, 'erro' => 'Ação inválida.'], 400);
    }

    $itens = $dados['itens'] ?? null;
    if (!is_array($itens) || !$itens || count($itens) > 50) {
        responderPedidos(['sucesso' => false, 'erro' => 'O carrinho está vazio ou contém itens demais.'], 400);
    }

    $metodos = [
        'pix' => 'PIX',
        'credito' => 'Cartão de Crédito',
        'debito' => 'Cartão de Débito',
    ];
    $metodo = $metodos[$dados['metodo'] ?? ''] ?? null;
    $cep = preg_replace('/\D/', '', (string) ($dados['cep'] ?? ''));
    $numero = trim((string) ($dados['numero'] ?? ''));
    $complemento = trim((string) ($dados['complemento'] ?? ''));
    $cupom = strtoupper(trim((string) ($dados['cupom'] ?? '')));
    $uf = strtoupper(trim((string) ($dados['uf'] ?? '')));
    $logradouro = trim((string) ($dados['logradouro'] ?? ''));
    $bairro = trim((string) ($dados['bairro'] ?? ''));
    $localidade = trim((string) ($dados['localidade'] ?? ''));
    $fretesPorUf = [
        'SP' => 12.90, 'RJ' => 18.90, 'MG' => 18.90, 'ES' => 18.90,
        'PR' => 22.90, 'SC' => 22.90, 'RS' => 24.90,
    ];
    if (
        !$metodo || strlen($cep) !== 8 || $numero === ''
        || !preg_match('/^[A-Z]{2}$/', $uf)
        || mb_strlen($numero, 'UTF-8') > 30
        || mb_strlen($complemento, 'UTF-8') > 100
        || mb_strlen($logradouro, 'UTF-8') > 100
        || mb_strlen($bairro, 'UTF-8') > 80
        || mb_strlen($localidade, 'UTF-8') > 80
    ) {
        responderPedidos(['sucesso' => false, 'erro' => 'Revise os dados de entrega e pagamento.'], 400);
    }
    if (!isset($fretesPorUf[$uf]) && !in_array($uf, ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'GO', 'MA', 'MT', 'MS', 'PA', 'PB', 'PE', 'PI', 'RN', 'RO', 'RR', 'TO'], true)) {
        responderPedidos(['sucesso' => false, 'erro' => 'Estado de entrega inválido.'], 400);
    }

    $stmtProduto = $pdo->prepare('SELECT id, nome, preco FROM produtos WHERE id = :id AND status = "ativo"');
    $itensNormalizados = [];
    $subtotal = 0.0;
    foreach ($itens as $item) {
        if (!is_array($item)) {
            throw new InvalidArgumentException('Um item do carrinho é inválido.');
        }
        $produtoId = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
        $quantidade = filter_var($item['qtd'] ?? null, FILTER_VALIDATE_INT);
        if (!$produtoId || $produtoId < 1 || !$quantidade || $quantidade < 1 || $quantidade > 100) {
            throw new InvalidArgumentException('Um dos produtos ou quantidades do pedido é inválido.');
        }
        $stmtProduto->execute(['id' => $produtoId]);
        $produto = $stmtProduto->fetch();
        if (!$produto) {
            throw new InvalidArgumentException('Um produto do pedido não está mais disponível.');
        }
        $preco = (float) $produto['preco'];
        $subtotal += $preco * $quantidade;
        $itensNormalizados[] = [
            'produto_id' => (int) $produto['id'],
            'nome' => $produto['nome'],
            'quantidade' => $quantidade,
            'preco' => $preco,
        ];
    }

    $frete = $fretesPorUf[$uf] ?? 29.90;
    $desconto = 0.0;
    $cupons = [
        'PET10' => ['tipo' => 'percentual', 'valor' => 10],
        'PET20' => ['tipo' => 'percentual', 'valor' => 20],
        'BEMVINDO15' => ['tipo' => 'fixo', 'valor' => 15],
        'FRETEGRATIS' => ['tipo' => 'frete', 'valor' => 0],
    ];
    if ($cupom !== '') {
        if (!isset($cupons[$cupom])) {
            throw new InvalidArgumentException('Cupom inválido. Confira o código e tente novamente.');
        }
        if ($cupons[$cupom]['tipo'] === 'percentual') {
            $desconto = $subtotal * ($cupons[$cupom]['valor'] / 100);
        } elseif ($cupons[$cupom]['tipo'] === 'fixo') {
            $desconto = min($subtotal, (float) $cupons[$cupom]['valor']);
        } else {
            $frete = 0;
        }
    }
    $subtotal = round($subtotal, 2);
    $desconto = round($desconto, 2);
    $total = round(max(0, $subtotal - $desconto) + $frete, 2);
    $partesEndereco = [];
    if ($logradouro !== '') {
        $partesEndereco[] = $logradouro;
    }
    if ($numero !== '') {
        $partesEndereco[] = 'nº ' . $numero;
    }
    if ($complemento !== '') {
        $partesEndereco[] = $complemento;
    }
    if ($bairro !== '') {
        $partesEndereco[] = $bairro;
    }
    if ($localidade !== '') {
        $partesEndereco[] = $localidade . '/' . $uf;
    } else {
        $partesEndereco[] = $uf;
    }
    $partesEndereco[] = 'CEP ' . substr($cep, 0, 5) . '-' . substr($cep, 5);
    $endereco = implode(', ', $partesEndereco);

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO compras
            (usuario_id, origem, subtotal, frete, desconto, total, status, metodo_pagamento, endereco_entrega)
         VALUES
            (:usuario_id, "checkout_simulado", :subtotal, :frete, :desconto, :total, "pendente", :metodo, :endereco)'
    );
    $stmt->execute([
        'usuario_id' => $usuarioId,
        'subtotal' => $subtotal,
        'frete' => $frete,
        'desconto' => $desconto,
        'total' => $total,
        'metodo' => $metodo,
        'endereco' => $endereco,
    ]);
    $compraId = (int) $pdo->lastInsertId();

    $stmtItem = $pdo->prepare(
        'INSERT INTO compra_itens (compra_id, produto_id, nome_produto, quantidade, preco_unitario)
         VALUES (:compra_id, :produto_id, :nome_produto, :quantidade, :preco_unitario)'
    );
    foreach ($itensNormalizados as $item) {
        $stmtItem->execute([
            'compra_id' => $compraId,
            'produto_id' => $item['produto_id'],
            'nome_produto' => $item['nome'],
            'quantidade' => $item['quantidade'],
            'preco_unitario' => $item['preco'],
        ]);
    }
    $pdo->commit();

    responderPedidos(['sucesso' => true, 'compra_id' => $compraId], 201);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    responderPedidos(['sucesso' => false, 'erro' => $e->getMessage()], 400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Falha ao processar pedido: ' . $e->getMessage());
    responderPedidos(['sucesso' => false, 'erro' => 'Não foi possível salvar o pedido. Tente novamente.'], 500);
}
