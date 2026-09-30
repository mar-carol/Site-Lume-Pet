<?php
require_once __DIR__ . '../includes/auth.php';
require_once __DIR__ . '../includes/functions.php';

exigirLogin();
$usuarioId = $_SESSION['usuario_id'];
$pdo = getConexao();

$stmt = $pdo->prepare('SELECT pontos FROM usuarios WHERE id = :id');
$stmt->execute(['id' => $usuarioId]);
$saldo = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM pontos_historico WHERE usuario_id = :uid ORDER BY criado_em DESC');
$stmt->execute(['uid' => $usuarioId]);
$historico = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus pontos - Lume Pet</title>
    <link rel="stylesheet" href="CSS/style.css?v=2">
</head>
<body>
<h1>Meus pontos de cashback</h1>
<p><a href="perfil.php">&larr; Voltar ao perfil</a></p>

<h2>Saldo atual: <?= $saldo ?> pontos</h2>

<table border="1" cellpadding="6">
<tr><th>Data</th><th>Tipo</th><th>Pontos</th><th>Descrição</th></tr>
<?php foreach ($historico as $h): ?>
<tr>
    <td><?= (new DateTime($h['criado_em']))->format('d/m/Y H:i') ?></td>
    <td><?= limpar(ucfirst($h['tipo'])) ?></td>
    <td><?= $h['pontos'] > 0 ? '+' : '' ?><?= (int) $h['pontos'] ?></td>
    <td><?= limpar($h['descricao'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>