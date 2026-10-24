<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirAdmin();
$pdo = getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenCsrf = $_POST['csrf_token'] ?? null;
    if (!is_string($tokenCsrf) || !validarTokenCsrf($tokenCsrf)) {
        http_response_code(403);
        exit('Sessão expirada. Atualize a página e tente novamente.');
    }

    $pedidoId = filter_var($_POST['pedido_id'] ?? null, FILTER_VALIDATE_INT);
    $novoStatus = $_POST['status'] ?? '';
    if (!$pedidoId || $pedidoId < 1 || !in_array($novoStatus, ['pago', 'enviado', 'entregue', 'cancelado'], true)) {
        http_response_code(400);
        exit('Ação de pedido inválida.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, status FROM compras WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $pedidoId]);
        $pedido = $stmt->fetch();
        if (!$pedido) {
            throw new DomainException('Pedido não encontrado.');
        }

        $proximosStatus = [
            'pendente' => ['pago', 'cancelado'],
            'pago' => ['enviado'],
            'enviado' => ['entregue'],
            'entregue' => [],
            'cancelado' => [],
        ];
        if (!in_array($novoStatus, $proximosStatus[$pedido['status']] ?? [], true)) {
            throw new DomainException('A mudança de status solicitada não é permitida.');
        }

        if ($novoStatus === 'pago') {
            $stmt = $pdo->prepare('UPDATE compras SET status = :status, pago_em = NOW() WHERE id = :id');
        } else {
            $stmt = $pdo->prepare('UPDATE compras SET status = :status WHERE id = :id');
        }
        $stmt->execute(['status' => $novoStatus, 'id' => $pedidoId]);

        $pdo->commit();
        redirecionar(BASE_URL . '/administrador/pedidos.php?atualizado=1');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e instanceof DomainException) {
            http_response_code(409);
            exit(limpar($e->getMessage()));
        }
        error_log('Não foi possível atualizar o pedido #' . $pedidoId . ': ' . $e->getMessage());
        http_response_code(500);
        exit('Não foi possível atualizar o pedido. Tente novamente.');
    }
}

$linhas = $pdo->query(
    'SELECT c.id, c.total, c.status, c.metodo_pagamento, c.endereco_entrega, c.criado_em, c.pago_em,
            u.nome AS usuario_nome, u.email AS usuario_email,
            ci.nome_produto, ci.quantidade
     FROM compras c
     JOIN usuarios u ON u.id = c.usuario_id
     LEFT JOIN compra_itens ci ON ci.compra_id = c.id
     ORDER BY c.criado_em DESC, c.id DESC, ci.id ASC'
)->fetchAll();

$pedidos = [];
foreach ($linhas as $linha) {
    $id = (int) $linha['id'];
    if (!isset($pedidos[$id])) {
        $pedidos[$id] = [
            'id' => $id,
            'total' => (float) $linha['total'],
            'status' => $linha['status'],
            'metodo_pagamento' => $linha['metodo_pagamento'],
            'endereco_entrega' => $linha['endereco_entrega'],
            'criado_em' => $linha['criado_em'],
            'pago_em' => $linha['pago_em'],
            'usuario_nome' => $linha['usuario_nome'],
            'usuario_email' => $linha['usuario_email'],
            'itens' => [],
        ];
    }
    if ($linha['nome_produto'] !== null) {
        $pedidos[$id]['itens'][] = (int) $linha['quantidade'] . 'x ' . $linha['nome_produto'];
    }
}

$rotulosStatus = [
    'pendente' => 'Pendente',
    'pago' => 'Pagamento confirmado',
    'enviado' => 'Em transporte',
    'entregue' => 'Entregue',
    'cancelado' => 'Cancelado',
];
$tituloPagina = 'Pedidos';
$paginaAtual = 'pedidos';
require __DIR__ . '/../includes/admin_header.php';
?>

<section class="lp-panel">
    <p>O checkout atual é demonstrativo: não processa PIX nem cartão. Confirme o pagamento apenas após verificar o recebimento por fora do site. Atualize o envio manualmente conforme o pedido avançar.</p>
    <?php if (isset($_GET['atualizado'])): ?>
        <div class="lp-alert lp-alert-sucesso" role="status">O pedido foi atualizado.</div>
    <?php endif; ?>

    <?php if (!$pedidos): ?>
        <p>Nenhum pedido registrado.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Pedido/data</th>
                    <th>Cliente</th>
                    <th>Produtos</th>
                    <th>Pagamento/entrega</th>
                    <th>Total/status</th>
                    <th>Atualizar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td>#<?= $pedido['id'] ?><br><?= (new DateTime($pedido['criado_em']))->format('d/m/Y H:i') ?></td>
                        <td><?= limpar($pedido['usuario_nome']) ?><br><small><?= limpar($pedido['usuario_email']) ?></small></td>
                        <td><?= $pedido['itens'] ? nl2br(limpar(implode("\n", $pedido['itens']))) : 'Sem itens detalhados' ?></td>
                        <td><?= limpar($pedido['metodo_pagamento']) ?><br><small><?= limpar($pedido['endereco_entrega'] ?? 'Endereço não informado') ?></small></td>
                        <td>R$ <?= number_format($pedido['total'], 2, ',', '.') ?><br><?= limpar($rotulosStatus[$pedido['status']] ?? $pedido['status']) ?></td>
                        <td>
                            <?php
                            $proximosStatus = [
                                'pendente' => ['pago', 'cancelado'],
                                'pago' => ['enviado'],
                                'enviado' => ['entregue'],
                            ];
                            foreach ($proximosStatus[$pedido['status']] ?? [] as $status):
                            ?>
                                <form method="post" action="<?= BASE_URL ?>/administrador/pedidos.php"
                                      onsubmit="return confirm('Atualizar este pedido para <?= limpar($rotulosStatus[$status]) ?>?')"
                                      style="margin-bottom:6px;">
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                    <input type="hidden" name="status" value="<?= limpar($status) ?>">
                                    <button type="submit" class="<?= $status === 'cancelado' ? 'avaliacao-remover' : '' ?>">
                                        <?= limpar($rotulosStatus[$status]) ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                            <?php if ($pedido['pago_em']): ?>
                                <small>Pago em <?= (new DateTime($pedido['pago_em']))->format('d/m/Y H:i') ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
