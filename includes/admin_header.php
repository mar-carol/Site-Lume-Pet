<?php
/**
 * Cabeçalho/layout compartilhado do painel administrativo.
 * Uso: defina $tituloPagina (e opcionalmente $paginaAtual) antes de
 * dar require neste arquivo. Todos os caminhos aqui usam a constante
 * BASE_URL (definida em config/database.php) em vez de caminho fixo
 * escrito à mão — se a pasta do projeto mudar de nome, só se ajusta
 * BASE_URL uma vez, em um lugar só.
 */
$paginaAtual = $paginaAtual ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($tituloPagina) ? htmlspecialchars($tituloPagina) . ' — ' : '' ?>Painel Lume Pet</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet"
      href="<?= BASE_URL ?>/css/admin.css?v=2">
</head>
<body>
<div class="lp-app">
    <aside class="lp-sidebar">
        <div class="lp-brand">
            <span class="lp-brand-mark">🐾</span>
            Lume Pet <small style="font-weight:400;opacity:.7;">admin</small>
        </div>
        <ul class="lp-nav">
            <a href="<?= BASE_URL ?>/index.php">Dashboard</a>

<a href="<?= BASE_URL ?>/produtos.php">Produtos</a>

<a href="<?= BASE_URL ?>/ongs.php">ONGs parceiras</a>

<a href="<?= BASE_URL ?>/animais.php">Animais</a>

<a href="<?= BASE_URL ?>/includes/logout.php">Sair</a>

            <li><a href="<?= BASE_URL ?>/index.php" class="<?= $paginaAtual === 'dashboard' ? 'active' : '' ?>"> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/produtos.php" class="<?= $paginaAtual === 'produtos' ? 'active' : '' ?>"> Produtos</a></li>
            <li><a href="<?= BASE_URL ?>/ongs.php" class="<?= $paginaAtual === 'ongs' ? 'active' : '' ?>"> ONGs parceiras</a></li>
            <li><a href="<?= BASE_URL ?>/animais.php" class="<?= $paginaAtual === 'animais' ? 'active' : '' ?>"> Animais</a></li>
            <li class="lp-nav-sair"><a href="<?= BASE_URL ?>/includes/logout.php">Sair</a></li>
        </ul>
    </aside>

    <main class="lp-main">
        <div class="lp-topbar">
            <h1><?= isset($tituloPagina) ? htmlspecialchars($tituloPagina) : '' ?></h1>
            <div class="lp-user-chip">
                <span class="lp-avatar"><?= strtoupper(substr($_SESSION['usuario_nome'] ?? 'A', 0, 1)) ?></span>
                <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Admin') ?>
            </div>
        </div>