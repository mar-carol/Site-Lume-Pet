<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exigirAdmin();
$pdo = getConexao();
$produtoId = filter_input(INPUT_GET, 'produto_id', FILTER_VALIDATE_INT);
$produtoId = $produtoId && $produtoId > 0 ? $produtoId : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Sessão expirada. Atualize a página e tente novamente.');
    }

    $avaliacaoId = filter_var($_POST['avaliacao_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$avaliacaoId || $avaliacaoId < 1) {
        http_response_code(400);
        exit('Avaliação inválida.');
    }

    $stmt = $pdo->prepare('DELETE FROM avaliacoes WHERE id = :id');
    $stmt->execute(['id' => $avaliacaoId]);
    redirecionar(BASE_URL . '/avaliacoes.php' . ($produtoId ? '?produto_id=' . $produtoId : ''));
}

$sql = 'SELECT a.id, a.nota, a.comentario, a.criado_em,
               p.id AS produto_id, p.nome AS produto_nome,
               u.nome AS usuario_nome, u.email AS usuario_email
        FROM avaliacoes a
        JOIN produtos p ON p.id = a.produto_id
        JOIN usuarios u ON u.id = a.usuario_id';
if ($produtoId) {
    $sql .= ' WHERE a.produto_id = :produto_id';
}
$sql .= ' ORDER BY a.criado_em DESC';
$stmt = $pdo->prepare($sql);
if ($produtoId) {
    $stmt->bindValue('produto_id', $produtoId, PDO::PARAM_INT);
}
$stmt->execute();
$avaliacoes = $stmt->fetchAll();

$totalAvaliacoes = (int) $pdo->query('SELECT COUNT(*) FROM avaliacoes')->fetchColumn();
$tituloPagina = 'Avaliações';
$paginaAtual = 'avaliacoes';
require __DIR__ . '/includes/admin_header.php';
?>

<section class="lp-panel">
    <h2 style="margin-top:0;">Avaliações recebidas: <?= $totalAvaliacoes ?></h2>
    <p>As avaliações ficam visíveis imediatamente na loja. Remova apenas as que não devem permanecer publicadas.</p>

    <?php if ($produtoId): ?>
        <p><a href="<?= BASE_URL ?>/avaliacoes.php">&larr; Ver avaliações de todos os produtos</a></p>
    <?php endif; ?>

    <?php if (!$avaliacoes): ?>
        <p>Nenhuma avaliação encontrada.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Cliente</th>
                    <th>Nota e comentário</th>
                    <th>Data</th>
                    <th>Ação</th>
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
                        <td>
                            <form method="post" action="<?= BASE_URL ?>/avaliacoes.php<?= $produtoId ? '?produto_id=' . $produtoId : '' ?>"
                                  onsubmit="return confirm('Remover esta avaliação da loja?')">
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="avaliacao_id" value="<?= (int) $avaliacao['id'] ?>">
                                <button type="submit" class="avaliacao-remover">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
