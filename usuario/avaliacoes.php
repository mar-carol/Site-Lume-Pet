<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirLogin();
if (usuarioEhAdmin()) {
    redirecionar(BASE_URL . '/administrador/avaliacoes.php');
}

$pdo = getConexao();
$stmt = $pdo->prepare(
    'SELECT a.nota, a.comentario, a.criado_em, p.nome AS produto_nome
     FROM avaliacoes a
     JOIN produtos p ON p.id = a.produto_id
     WHERE a.usuario_id = :usuario_id
     ORDER BY a.criado_em DESC'
);
$stmt->execute(['usuario_id' => (int) $_SESSION['usuario_id']]);
$avaliacoes = $stmt->fetchAll();

$tituloPagina = 'Minhas avaliações';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $tituloPagina ?> — Lume Pet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css?v=3">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/usuario-dashboard.css?v=2">
</head>
<body>
<div class="lp-app">
    <aside class="lp-sidebar">
        <a class="lp-brand user-brand" href="<?= BASE_URL ?>/index.php">
            <img class="user-brand-logo" src="<?= BASE_URL ?>/assets/images/marcad.png" alt="Lume Pet" width="90" height="50">
            <small>minha conta</small>
        </a>
        <nav aria-label="Menu da conta">
            <ul class="lp-nav">
                <li><a href="<?= BASE_URL ?>/usuario/dashboard.php">Visão geral</a></li>
                <li><a href="<?= BASE_URL ?>/usuario/perfil.php">Meu perfil</a></li>
                <li><a href="<?= BASE_URL ?>/usuario/compras.php">Minhas compras</a></li>
                <li><a href="<?= BASE_URL ?>/usuario/doacoes.php">Minhas doações</a></li>
                <li><a class="active" href="<?= BASE_URL ?>/usuario/avaliacoes.php">Minhas avaliações</a></li>
                <li><a href="<?= BASE_URL ?>/usuario/pontos.php">Meus pontos</a></li>
                <li><a href="<?= BASE_URL ?>/usuario/cartoes.php">Meus cartões</a></li>
                <li class="lp-nav-sair"><a href="<?= BASE_URL ?>/includes/logout.php">Sair</a></li>
            </ul>
        </nav>
    </aside>

    <main class="lp-main">
        <header class="lp-topbar">
            <div>
                <h1><?= $tituloPagina ?></h1>
                <p class="user-welcome">Veja as avaliações que você enviou para os produtos.</p>
            </div>
            <a href="<?= BASE_URL ?>/usuario/dashboard.php">Voltar à minha conta</a>
        </header>

        <?php if (!$avaliacoes): ?>
            <section class="lp-panel">
                <h2>Nenhuma avaliação por enquanto</h2>
                <p>Depois de avaliar um produto, você poderá consultar sua avaliação nesta página.</p>
                <a href="<?= BASE_URL ?>/index.php#loja">Ir para a loja</a>
            </section>
        <?php else: ?>
            <section class="lp-panel">
                <h2>Minhas avaliações (<?= count($avaliacoes) ?>)</h2>
                <div class="user-reviews">
                    <?php foreach ($avaliacoes as $avaliacao): ?>
                        <article class="user-review">
                            <div class="user-review-header">
                                <h3><?= limpar($avaliacao['produto_nome']) ?></h3>
                                <span class="user-review-rating"><?= (int) $avaliacao['nota'] ?>/5 estrelas</span>
                            </div>
                            <p><?= nl2br(limpar($avaliacao['comentario'])) ?></p>
                            <time datetime="<?= limpar($avaliacao['criado_em']) ?>">
                                Enviada em <?= (new DateTime($avaliacao['criado_em']))->format('d/m/Y H:i') ?>
                            </time>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
