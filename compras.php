<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exigirLogin();
$usuarioId = $_SESSION['usuario_id'];
$pdo = getConexao();

$stmt = $pdo->prepare('SELECT * FROM compras WHERE usuario_id = :uid ORDER BY criado_em DESC');
$stmt->execute(['uid' => $usuarioId]);
$compras = $stmt->fetchAll();

// Busca os itens de cada compra
$stmtItens = $pdo->prepare('SELECT ci.*, p.nome AS produto_nome
                             FROM compra_itens ci
                             JOIN produtos p ON p.id = ci.produto_id
                             WHERE ci.compra_id = :compra_id');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head><meta charset="UTF-8"><title>Minhas compras - Lume Pet</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<h1>Minhas compras</h1>
<p><a href="perfil.php">&larr; Voltar ao perfil</a></p>

<?php if (!$compras): ?>
    <p>Você ainda não fez nenhuma compra.</p>
<?php endif; ?>

<?php foreach ($compras as $compra): ?>
    <?php
        $stmtItens->execute(['compra_id' => $compra['id']]);
        $itens = $stmtItens->fetchAll();
    ?>
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <strong>Pedido #<?= $compra['id'] ?></strong> —
        Status: <?= limpar(ucfirst($compra['status'])) ?> —
        Total: R$ <?= number_format($compra['total'], 2, ',', '.') ?> —
        Data: <?= (new DateTime($compra['criado_em']))->format('d/m/Y H:i') ?>

        <ul>
        <?php foreach ($itens as $item): ?>
            <li><?= (int) $item['quantidade'] ?>x <?= limpar($item['produto_nome']) ?>
                — R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?> cada</li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endforeach; ?>
</body>
</html>