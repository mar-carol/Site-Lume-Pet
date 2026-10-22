<?php
require_once __DIR__ . '/functions.php';
/**
 * Header padrão - Lume Pet
 *
 * Use nas páginas do projeto:
 * require_once __DIR__ . '/header.php';
 *
 * Se a página estiver dentro de uma subpasta, ajuste o caminho:
 * require_once __DIR__ . '/../header.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= isset($tituloPagina) ? limpar($tituloPagina) . ' - Lume Pet' : 'Lume Pet' ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=20261022-header-logo">
</head>

<body class="lp-public lp-cards-page">

<header class="lp-topnav">
    <a class="lp-home-link" href="<?= BASE_URL ?>/index.php" aria-label="Voltar para o início do site Lume Pet">
        <img src="<?= BASE_URL ?>/assets/images/marcad.png" alt="Lume Pet" width="52" height="32">
    </a>

    <nav aria-label="Navegação principal">
        <a href="<?= BASE_URL ?>/usuario/dashboard.php">Minha conta</a>
        <a href="<?= BASE_URL ?>/usuario/perfil.php">Perfil</a>
        <a href="<?= BASE_URL ?>/usuario/compras.php">Compras</a>
        <a href="<?= BASE_URL ?>/usuario/doacoes.php">Doações</a>
        <a href="<?= BASE_URL ?>/usuario/pontos.php">Pontos</a>
        <a href="<?= BASE_URL ?>/usuario/cartoes.php">Cartões</a>
        <a href="<?= BASE_URL ?>/includes/logout.php">Sair</a>
    </nav>
</header>
