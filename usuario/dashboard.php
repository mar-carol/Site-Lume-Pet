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

$stmt = $pdo->prepare('SELECT nome, pontos FROM usuarios WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $usuarioId]);
$usuario = $stmt->fetch();

if (!$usuario) {
    http_response_code(404);
    exit('Conta de usuário não encontrada.');
}

$contadores = [];
foreach ([
    'cartoes' => 'SELECT COUNT(*) FROM cartoes WHERE usuario_id = :id',
    'compras' => 'SELECT COUNT(*) FROM compras WHERE usuario_id = :id',
    'doacoes' => 'SELECT COUNT(*) FROM doacoes WHERE usuario_id = :id',
] as $chave => $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $usuarioId]);
    $contadores[$chave] = (int) $stmt->fetchColumn();
}

$nomeUsuario = limpar($usuario['nome']);
$tituloPagina = 'Minha conta';
$paginaAtual = 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $tituloPagina ?> — Lume Pet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css?v=2">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/usuario-dashboard.css?v=1">
</head>
<body>
<div class="lp-app">
    <aside class="lp-sidebar">
        <a class="lp-brand user-brand" href="<?= BASE_URL ?>/index.php">
            <span class="lp-brand-mark">L</span>
            Lume Pet <small>minha conta</small>
        </a>
        <nav aria-label="Menu da conta">
            <ul class="lp-nav">
                <li><a class="active" href="<?= BASE_URL ?>/usuario/dashboard.php">Visão geral</a></li>
                <li><a href="<?= BASE_URL ?>/perfil.php">Meu perfil</a></li>
                <li><a href="<?= BASE_URL ?>/compras.php">Minhas compras</a></li>
                <li><a href="<?= BASE_URL ?>/doacoes.php">Minhas doações</a></li>
                <li><a href="<?= BASE_URL ?>/pontos.php">Meus pontos</a></li>
                <li><a href="<?= BASE_URL ?>/cartoes.php">Meus cartões</a></li>
                <li class="lp-nav-sair"><a href="<?= BASE_URL ?>/includes/logout.php">Sair</a></li>
            </ul>
        </nav>
    </aside>

    <main class="lp-main">
        <header class="lp-topbar">
            <div>
                <h1>Olá, <?= $nomeUsuario ?>!</h1>
                <p class="user-welcome">Acompanhe seus pontos, cartões e atividades na Lume Pet.</p>
            </div>
            <div class="lp-user-chip">
                <span class="lp-avatar"><?= limpar(mb_strtoupper(mb_substr($usuario['nome'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                <?= $nomeUsuario ?>
            </div>
        </header>

        <section class="user-stats" aria-label="Resumo da conta">
            <a class="user-stat" href="<?= BASE_URL ?>/pontos.php">
                <span class="user-stat-label">Meus pontos</span>
                <strong><?= number_format((int) $usuario['pontos'], 0, ',', '.') ?></strong>
                <span>Ver histórico de pontos</span>
            </a>
            <a class="user-stat" href="<?= BASE_URL ?>/cartoes.php">
                <span class="user-stat-label">Cartões salvos</span>
                <strong><?= $contadores['cartoes'] ?></strong>
                <span>Gerenciar cartões</span>
            </a>
            <a class="user-stat" href="<?= BASE_URL ?>/compras.php">
                <span class="user-stat-label">Compras</span>
                <strong><?= $contadores['compras'] ?></strong>
                <span>Acompanhar pedidos</span>
            </a>
            <a class="user-stat" href="<?= BASE_URL ?>/doacoes.php">
                <span class="user-stat-label">Doações</span>
                <strong><?= $contadores['doacoes'] ?></strong>
                <span>Ver minhas doações</span>
            </a>
        </section>

        <section class="lp-panel user-account-panel">
            <h2>Atalhos da conta</h2>
            <div class="user-shortcuts">
                <a href="<?= BASE_URL ?>/perfil.php">Atualizar meus dados</a>
                <a href="<?= BASE_URL ?>/pontos.php">Consultar meus pontos</a>
                <a href="<?= BASE_URL ?>/cartoes.php">Ver meus cartões</a>
                <a href="<?= BASE_URL ?>/index.php">Voltar para a loja</a>
            </div>
        </section>
    </main>
</div>
</body>
</html>
