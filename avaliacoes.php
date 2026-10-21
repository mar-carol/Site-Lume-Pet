<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exigirAdmin();
$pdo = getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Sessão expirada. Atualize a página e tente novamente.');
    }

    $avaliacaoId = filter_var($_POST['avaliacao_id'] ?? null, FILTER_VALIDATE_INT);
    $acao = $_POST['acao'] ?? '';
    if (!$avaliacaoId || $avaliacaoId < 1) {
        http_response_code(400);
        exit('Avaliação inválida.');
    }

    if (in_array($acao, ['aprovar', 'rejeitar'], true)) {
        $status = $acao === 'aprovar' ? 'aprovada' : 'rejeitada';
        $stmt = $pdo->prepare('UPDATE avaliacoes SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $avaliacaoId]);
    } elseif ($acao === 'excluir') {
        $stmt = $pdo->prepare('DELETE FROM avaliacoes WHERE id = :id');
        $stmt->execute(['id' => $avaliacaoId]);
    } else {
        http_response_code(400);
        exit('Ação inválida.');
    }

    redirecionar(BASE_URL . '/avaliacoes.php');
}

$avaliacoes = $pdo->query(
    'SELECT a.id, a.nota, a.comentario, a.status, a.criado_em,
            p.nome AS produto_nome, u.nome AS usuario_nome, u.email AS usuario_email
     FROM avaliacoes a
     JOIN produtos p ON p.id = a.produto_id
     JOIN usuarios u ON u.id = a.usuario_id
     ORDER BY FIELD(a.status, "pendente", "aprovada", "rejeitada"), a.criado_em DESC'
)->fetchAll();

$tituloPagina = 'Gerenciar avaliações';
$paginaAtual = 'avaliacoes';
require __DIR__ . '/includes/admin_header.php';
?>

<section class="lp-panel">
    <h2 style="margin-top:0;">Avaliações recebidas</h2>
    <p>Aprove ou rejeite os comentários antes de exibi-los na loja.</p>

    <?php if (!$avaliacoes): ?>
        <p>Nenhuma avaliação recebida até o momento.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Cliente</th>
                    <th>Avaliação</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avaliacoes as $avaliacao): ?>
                    <tr>
                        <td><?= limpar($avaliacao['produto_nome']) ?></td>
                        <td>
                            <?= limpar($avaliacao['usuario_nome']) ?><br>
                            <small><?= limpar($avaliacao['usuario_email']) ?></small>
                        </td>
                        <td>
                            <strong><?= (int) $avaliacao['nota'] ?>/5</strong><br>
                            <?= nl2br(limpar($avaliacao['comentario'])) ?>
                        </td>
                        <td><?= (new DateTime($avaliacao['criado_em']))->format('d/m/Y H:i') ?></td>
                        <td><span class="lp-badge lp-badge-<?= limpar($avaliacao['status']) ?>"><?= limpar(ucfirst($avaliacao['status'])) ?></span></td>
                        <td class="avaliacao-acoes">
                            <?php if ($avaliacao['status'] !== 'aprovada'): ?>
                                <form method="post" action="<?= BASE_URL ?>/avaliacoes.php">
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="avaliacao_id" value="<?= (int) $avaliacao['id'] ?>">
                                    <input type="hidden" name="acao" value="aprovar">
                                    <button type="submit">Aprovar</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($avaliacao['status'] !== 'rejeitada'): ?>
                                <form method="post" action="<?= BASE_URL ?>/avaliacoes.php">
                                    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                    <input type="hidden" name="avaliacao_id" value="<?= (int) $avaliacao['id'] ?>">
                                    <input type="hidden" name="acao" value="rejeitar">
                                    <button type="submit" class="lp-cancel">Rejeitar</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= BASE_URL ?>/avaliacoes.php" onsubmit="return confirm('Excluir esta avaliação permanentemente?')">
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="avaliacao_id" value="<?= (int) $avaliacao['id'] ?>">
                                <input type="hidden" name="acao" value="excluir">
                                <button type="submit" class="lp-cancel">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
