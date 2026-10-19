<?php
require_once __DIR__ . '/includes/auth.php';

if (!usuarioEstaLogado()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$destino = usuarioEhAdmin()
    ? BASE_URL . '/includes/index.php'
    : BASE_URL . '/usuario/dashboard.php';

header('Location: ' . $destino);
exit;
