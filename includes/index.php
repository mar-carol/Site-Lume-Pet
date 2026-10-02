<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirAdmin();
$pdo = getConexao();

$totalUsuarios = $pdo->query('SELECT COUNT(*) FROM usuarios WHERE tipo="cliente"')->fetchColumn();
$totalProdutos = $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$totalOngs     = $pdo->query('SELECT COUNT(*) FROM ongs WHERE status="parceira"')->fetchColumn();
$totalAnimais  = $pdo->query('SELECT COUNT(*) FROM animais WHERE status="disponivel"')->fetchColumn();
$totalAdotados = $pdo->query('SELECT COUNT(*) FROM animais WHERE status="adotado"')->fetchColumn();
$totalDoado    = $pdo->query('SELECT COALESCE(SUM(valor),0) FROM doacoes WHERE status="confirmada"')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Painel administrativo - Lume Pet</title>
	<link rel="stylesheet" href="../CSS/style.css">
	<link rel="stylesheet" href="../CSS/dashboard.css">
</head>
<body>
<h1>Painel administrativo</h1>

<nav>
	<a href="index.php">Dashboard</a> |
	<a href="../produtos.php">Produtos</a> |
	<a href="../ongs.php">ONGs</a> |
	<a href="../animais.php">Animais</a> |
	<a href="logout.php">Sair</a>
</nav>

<ul>
	<li>Usuários cadastrados: <?= (int) $totalUsuarios ?></li>
	<li>Produtos cadastrados: <?= (int) $totalProdutos ?></li>
	<li>ONGs parceiras: <?= (int) $totalOngs ?></li>
	<li>Animais disponíveis: <?= (int) $totalAnimais ?></li>
	<li>Animais já adotados: <?= (int) $totalAdotados ?></li>
	<li>Total doado: R$ <?= number_format($totalDoado, 2, ',', '.') ?></li>
</ul>
</body>
</html>