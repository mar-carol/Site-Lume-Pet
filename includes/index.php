<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirAdmin();
$pdo = getConexao();

$totalUsuarios = $pdo->query('SELECT COUNT(*) FROM usuarios WHERE tipo="cliente"')->fetchColumn();
$totalProdutos = $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$totalAvaliacoes = $pdo->query('SELECT COUNT(*) FROM avaliacoes')->fetchColumn();
$totalOngs     = $pdo->query('SELECT COUNT(*) FROM ongs WHERE status="parceira"')->fetchColumn();
$totalAnimais  = $pdo->query('SELECT COUNT(*) FROM animais WHERE status="disponivel"')->fetchColumn();
$totalAdotados = $pdo->query('SELECT COUNT(*) FROM animais WHERE status="adotado"')->fetchColumn();
$totalDoado    = $pdo->query('SELECT COALESCE(SUM(valor),0) FROM doacoes WHERE status="confirmada"')->fetchColumn();
$doacoesPendentes = $pdo->query('SELECT COUNT(*) FROM doacoes WHERE status="pendente"')->fetchColumn();
$pedidosPendentes = $pdo->query('SELECT COUNT(*) FROM compras WHERE status="pendente"')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Painel administrativo - Lume Pet</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
	<link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css">
</head>
<body>
<h1>Painel administrativo</h1>

<nav>
	<a href="<?= BASE_URL ?>/includes/index.php">Dashboard</a> |
	<a href="<?= BASE_URL ?>/administrador/produtos.php">Produtos</a> |
	<a href="<?= BASE_URL ?>/administrador/avaliacoes.php">Avaliações</a> |
	<a href="<?= BASE_URL ?>/usuario/ongs.php">ONGs</a> |
	<a href="<?= BASE_URL ?>/administrador/animais.php">Animais</a> |
	<a href="<?= BASE_URL ?>/includes/logout.php">Sair</a>
</nav>

<ul>
	<li>Usuários cadastrados: <?= (int) $totalUsuarios ?></li>
	<li>Produtos cadastrados: <?= (int) $totalProdutos ?></li>
	<li><a href="<?= BASE_URL ?>/administrador/avaliacoes.php">Avaliações recebidas: <?= (int) $totalAvaliacoes ?> (ver e remover)</a></li>
	<li>ONGs parceiras: <?= (int) $totalOngs ?></li>
	<li>Animais disponíveis: <?= (int) $totalAnimais ?></li>
	<li>Animais já adotados: <?= (int) $totalAdotados ?></li>
	<li>Total marcado como confirmado (revise registros antigos): R$ <?= number_format($totalDoado, 2, ',', '.') ?></li>
	<li><a href="<?= BASE_URL ?>/administrador/doacoes.php">Doações aguardando conferência: <?= (int) $doacoesPendentes ?> (ver valores por ONG)</a></li>
	<li><a href="<?= BASE_URL ?>/administrador/pedidos.php">Pedidos pendentes: <?= (int) $pedidosPendentes ?> (acompanhar e atualizar)</a></li>
</ul>
</body>
</html>