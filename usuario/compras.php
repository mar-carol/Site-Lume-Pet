<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirLogin();
if (usuarioEhAdmin()) {
    header('Location: ' . BASE_URL . '/includes/index.php');
    exit;
}

$pdo = getConexao();
$usuarioId = (int) $_SESSION['usuario_id'];
$stmtUsuario = $pdo->prepare('SELECT nome, email FROM usuarios WHERE id = :id LIMIT 1');
$stmtUsuario->execute(['id' => $usuarioId]);
$usuario = $stmtUsuario->fetch();
if (!$usuario) {
    http_response_code(404);
    exit('Conta de usuário não encontrada.');
}

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
            'nome' => $linha['produto_nome'],
            'quantidade' => (int) $linha['quantidade'],
            'preco_unitario' => (float) $linha['preco_unitario'],
        ];
    }
}

function statusPedidoLabel(string $status): string
{
    return [
        'pendente' => 'Pendente de confirmação',
        'pago' => 'Pagamento confirmado',
        'enviado' => 'Em transporte',
        'entregue' => 'Entregue',
        'cancelado' => 'Cancelado',
    ][$status] ?? 'Status indisponível';
}

function formatarDataPedido(?string $data, bool $somenteData = false): string
{
    if (!$data) {
        return 'Ainda não confirmado';
    }
    return (new DateTime($data))->format($somenteData ? 'd/m/Y' : 'd/m/Y H:i');
}

function renderizarAndamentoPedido(string $status): string
{
    if ($status === 'cancelado') {
        return '<p class="pedido-cancelado">Este pedido foi cancelado.</p>';
    }
    $etapas = [
        'pendente' => ['Pedido registrado'],
        'pago' => ['Pedido registrado', 'Pagamento confirmado'],
        'enviado' => ['Pedido registrado', 'Pagamento confirmado', 'Em transporte'],
        'entregue' => ['Pedido registrado', 'Pagamento confirmado', 'Em transporte', 'Entregue'],
    ];
    $concluidas = $etapas[$status] ?? $etapas['pendente'];
    $todas = ['Pedido registrado', 'Pagamento confirmado', 'Em transporte', 'Entregue'];
    $html = '<ol class="pedido-etapas">';
    foreach ($todas as $etapa) {
        $classe = in_array($etapa, $concluidas, true) ? ' concluida' : '';
        $html .= '<li class="' . $classe . '">' . limpar($etapa) . '</li>';
    }
    return $html . '</ol>';
}

$usuarioJson = json_encode(
    ['id' => $usuarioId, 'email' => $usuario['email']],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minhas compras — Lume Pet</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=4">
</head>
<body>
<?php require __DIR__ . '/../includes/user_home_logo.php'; ?>
<main class="compras-page">
    <header class="compras-page-header">
        <div>
            <h1>Minhas compras</h1>
            <p>Acompanhe o status, pagamento e os detalhes dos seus pedidos.</p>
        </div>
        <a href="<?= BASE_URL ?>/usuario/dashboard.php">&larr; Voltar à minha conta</a>
    </header>

    <p id="sincronizacaoPedidos" class="pedido-sync-aviso" role="status" hidden></p>

    <?php if (!$compras): ?>
        <p class="compras-vazio">Você ainda não fez nenhuma compra.</p>
    <?php endif; ?>

    <?php foreach ($compras as $compra): ?>
        <article class="compra-detalhe">
            <header class="compra-detalhe-header">
                <div>
                    <strong>Pedido #<?= (int) $compra['id'] ?></strong>
                    <span><?= limpar(formatarDataPedido($compra['criado_em'], $compra['origem'] === 'historico_local')) ?></span>
                </div>
                <span class="compra-status status-<?= limpar($compra['status']) ?>"><?= limpar(statusPedidoLabel($compra['status'])) ?></span>
            </header>

            <?php if ($compra['origem'] === 'historico_local'): ?>
                <p class="pedido-observacao">Importado do histórico deste navegador. Os dados antigos não permitem confirmar pagamento ou endereço.</p>
            <?php endif; ?>

            <?= renderizarAndamentoPedido($compra['status']) ?>

            <section class="compra-detalhe-bloco">
                <h2>Itens do pedido</h2>
                <?php if (!$compra['itens']): ?>
                    <p>Não há itens disponíveis para este pedido.</p>
                <?php else: ?>
                    <ul class="compra-itens">
                        <?php foreach ($compra['itens'] as $item): ?>
                            <li>
                                <span><?= (int) $item['quantidade'] ?>x <?= limpar($item['nome']) ?> — <?= number_format($item['preco_unitario'], 2, ',', '.') ?> cada</span>
                                <strong>R$ <?= number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.') ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="compra-informacoes">
                <div>
                    <h2>Pagamento</h2>
                    <p><strong>Forma:</strong> <?= limpar($compra['metodo_pagamento']) ?></p>
                    <p><strong>Status:</strong> <?= limpar(statusPedidoLabel($compra['status'])) ?></p>
                    <p><strong>Confirmado em:</strong> <?= limpar(formatarDataPedido($compra['pago_em'])) ?></p>
                </div>
                <div>
                    <h2>Entrega</h2>
                    <p><?= limpar($compra['endereco_entrega'] ?: 'Endereço não informado no histórico antigo.') ?></p>
                </div>
                <div class="compra-valores">
                    <p>Subtotal <span>R$ <?= number_format($compra['subtotal'], 2, ',', '.') ?></span></p>
                    <p>Frete <span>R$ <?= number_format($compra['frete'], 2, ',', '.') ?></span></p>
                    <p>Desconto <span>− R$ <?= number_format($compra['desconto'], 2, ',', '.') ?></span></p>
                    <p class="compra-total">Total <strong>R$ <?= number_format($compra['total'], 2, ',', '.') ?></strong></p>
                </div>
            </section>
        </article>
    <?php endforeach; ?>
</main>
<script>
    window.usuarioSessao = <?= $usuarioJson ?>;
    window.csrfToken = <?= json_encode(gerarTokenCsrf(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.pedidosApiUrl = <?= json_encode(BASE_URL . '/api/finalizar-compra.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>/js/pedidos.js?v=20261022-orders"></script>
<script>
    window.sincronizarPedidosLegados().then(importados => {
        if (importados) window.location.reload();
    }).catch(erro => {
        const aviso = document.getElementById('sincronizacaoPedidos');
        aviso.textContent = erro.message;
        aviso.hidden = false;
    });
</script>
</body>
</html>