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

    $doacaoId = filter_var($_POST['doacao_id'] ?? null, FILTER_VALIDATE_INT);
    $novoStatus = $_POST['status'] ?? '';
    if (!$doacaoId || $doacaoId < 1 || !in_array($novoStatus, ['confirmada', 'cancelada'], true)) {
        http_response_code(400);
        exit('Ação de doação inválida.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, usuario_id, valor, status FROM doacoes WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $doacaoId]);
        $doacao = $stmt->fetch();
        if (!$doacao) {
            throw new DomainException('Doação não encontrada.');
        }
        if ($doacao['status'] !== 'pendente') {
            throw new DomainException('Esta doação já foi processada e não pode ser alterada novamente.');
        }

        if ($novoStatus === 'confirmada') {
            $pontos = calcularPontosPorDoacao((float) $doacao['valor']);
            $stmt = $pdo->prepare('UPDATE doacoes SET status = "confirmada", pontos_gerados = :pontos WHERE id = :id');
            $stmt->execute(['pontos' => $pontos, 'id' => $doacaoId]);
            concederPontos((int) $doacao['usuario_id'], $pontos, 'doacao', $doacaoId, 'Doação confirmada #' . $doacaoId);
        } else {
            $stmt = $pdo->prepare('UPDATE doacoes SET status = "cancelada" WHERE id = :id');
            $stmt->execute(['id' => $doacaoId]);
        }

        $pdo->commit();
        redirecionar(BASE_URL . '/administrador/doacoes.php?atualizada=1');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e instanceof DomainException) {
            http_response_code(409);
            exit(limpar($e->getMessage()));
        }
        error_log('Não foi possível atualizar a doação #' . $doacaoId . ': ' . $e->getMessage());
        http_response_code(500);
        exit('Não foi possível atualizar a doação. Tente novamente.');
    }
}

$resumoOngs = $pdo->query(
    'SELECT o.id, o.nome,
            COUNT(CASE WHEN d.status = "confirmada" THEN 1 END) AS doacoes_confirmadas,
            COALESCE(SUM(CASE WHEN d.status = "confirmada" THEN d.valor ELSE 0 END), 0) AS total_confirmado,
            COUNT(CASE WHEN d.status = "pendente" THEN 1 END) AS doacoes_pendentes,
            COALESCE(SUM(CASE WHEN d.status = "pendente" THEN d.valor ELSE 0 END), 0) AS total_pendente
     FROM ongs o
     LEFT JOIN doacoes d ON d.ong_id = o.id
     WHERE o.status = "parceira"
     GROUP BY o.id, o.nome
     ORDER BY total_confirmado DESC, o.nome'
)->fetchAll();

$doacoes = $pdo->query(
    'SELECT d.id, d.valor, d.pontos_gerados, d.status, d.metodo_pagamento, d.criado_em,
            u.nome AS usuario_nome, u.email AS usuario_email, o.nome AS ong_nome
     FROM doacoes d
     JOIN usuarios u ON u.id = d.usuario_id
     JOIN ongs o ON o.id = d.ong_id
     ORDER BY FIELD(d.status, "pendente", "confirmada", "cancelada"), d.criado_em DESC'
)->fetchAll();

$totalConfirmado = (float) $pdo->query('SELECT COALESCE(SUM(valor), 0) FROM doacoes WHERE status = "confirmada"')->fetchColumn();
$totalPendente = (float) $pdo->query('SELECT COALESCE(SUM(valor), 0) FROM doacoes WHERE status = "pendente"')->fetchColumn();
$tituloPagina = 'Doações';
$paginaAtual = 'doacoes';
require __DIR__ . '/../includes/admin_header.php';
?>

<section class="lp-panel">
    <h2 style="margin-top:0;">Resumo por instituição</h2>
    <p>Valores confirmados só devem ser considerados após conferência do recebimento fora do site. Cada doação fica vinculada à ONG escolhida pelo cliente; não existe rateio automático.</p>
    <p>Registros confirmados antes da mudança do fluxo também aparecem no total e precisam ser conferidos manualmente: o sistema antigo os confirmava sem validar o pagamento.</p>
    <p><strong>Total confirmado:</strong> R$ <?= number_format($totalConfirmado, 2, ',', '.') ?>
        &nbsp; <strong>Aguardando conferência:</strong> R$ <?= number_format($totalPendente, 2, ',', '.') ?></p>

    <?php if (!$resumoOngs): ?>
        <p>Não há ONGs parceiras cadastradas.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ONG parceira</th>
                    <th>Doações confirmadas</th>
                    <th>Total confirmado</th>
                    <th>Doações pendentes</th>
                    <th>Total pendente</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resumoOngs as $ong): ?>
                    <tr>
                        <td><?= limpar($ong['nome']) ?></td>
                        <td><?= (int) $ong['doacoes_confirmadas'] ?></td>
                        <td>R$ <?= number_format((float) $ong['total_confirmado'], 2, ',', '.') ?></td>
                        <td><?= (int) $ong['doacoes_pendentes'] ?></td>
                        <td>R$ <?= number_format((float) $ong['total_pendente'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section class="lp-panel" style="margin-top:24px;">
    <h2 style="margin-top:0;">Registros de doações</h2>
    <?php if (isset($_GET['atualizada'])): ?>
        <div class="lp-alert lp-alert-sucesso" role="status">O status da doação foi atualizado.</div>
    <?php endif; ?>

    <?php if (!$doacoes): ?>
        <p>Nenhuma doação registrada.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>ONG</th>
                    <th>Valor</th>
                    <th>Forma de pagamento</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doacoes as $doacao): ?>
                    <tr>
                        <td><?= (new DateTime($doacao['criado_em']))->format('d/m/Y H:i') ?></td>
                        <td><?= limpar($doacao['usuario_nome']) ?><br><small><?= limpar($doacao['usuario_email']) ?></small></td>
                        <td><?= limpar($doacao['ong_nome']) ?></td>
                        <td>R$ <?= number_format((float) $doacao['valor'], 2, ',', '.') ?></td>
                        <td><?= limpar($doacao['metodo_pagamento'] ?: 'Não informado') ?></td>
                        <td><?= limpar(ucfirst($doacao['status'])) ?>
                            <?php if ($doacao['status'] === 'confirmada'): ?>
                                <br><small><?= (int) $doacao['pontos_gerados'] ?> pontos creditados</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($doacao['status'] === 'pendente'): ?>
                                <div class="avaliacao-acoes">
                                    <form method="post" action="<?= BASE_URL ?>/administrador/doacoes.php"
                                          onsubmit="return confirm('Confirma que o recebimento externo foi verificado?')">
                                        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                        <input type="hidden" name="doacao_id" value="<?= (int) $doacao['id'] ?>">
                                        <input type="hidden" name="status" value="confirmada">
                                        <button type="submit">Confirmar recebimento</button>
                                    </form>
                                    <form method="post" action="<?= BASE_URL ?>/administrador/doacoes.php"
                                          onsubmit="return confirm('Cancelar esta intenção de doação?')">
                                        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                        <input type="hidden" name="doacao_id" value="<?= (int) $doacao['id'] ?>">
                                        <input type="hidden" name="status" value="cancelada">
                                        <button type="submit" class="avaliacao-remover">Cancelar</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
