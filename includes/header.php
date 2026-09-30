<?php
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

    <link rel="stylesheet" href="CSS/style.css">
</head>

<body class="lp-public">

<header class="lp-topnav">
    <div class="lp-brand">
        <span class="lp-brand-mark">🐾</span>
        <span>Lume Pet</span>
    </div>

    <nav aria-label="Navegação principal">
        <a href="perfil.php">Perfil</a>
        <a href="compras.php">Compras</a>
        <a href="doacoes.php">Doações</a>
        <a href="pontos.php">Pontos</a>
        <a href="cartoes.php">Cartões</a>
        <a href="/auth/logout.php">Sair</a>
    </nav>
</header>
