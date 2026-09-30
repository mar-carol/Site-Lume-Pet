<?php
require_once __DIR__ . '../includes/auth.php';
require_once __DIR__ . '../includes/functions.php';

exigirLogin();
$usuarioId = $_SESSION['usuario_id'];
$pdo = getConexao();
$erros = [];

// Nova doação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'doar') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, tente novamente.';
    }

    $ongId    = (int) ($_POST['ong_id'] ?? 0);
    $valor    = (float) str_replace(',', '.', $_POST['valor'] ?? '0');
    $cartaoId = !empty($_POST['cartao_id']) ? (int) $_POST['cartao_id'] : null;

    if ($ongId <= 0) $erros[] = 'Selecione uma ONG.';
    if ($valor <= 0) $erros[] = 'Informe um valor de doação válido.';

    if (!$erros) {
        $pdo->beginTransaction();
        try {
            $pontos = calcularPontosPorDoacao($valor);

            $stmt = $pdo->prepare('INSERT INTO doacoes (usuario_id, ong_id, cartao_id, valor, pontos_gerados, status)
                                    VALUES (:usuario_id, :ong_id, :cartao_id, :valor, :pontos, "confirmada")');
            $stmt->execute([
                'usuario_id' => $usuarioId,
                'ong_id'     => $ongId,
                'cartao_id'  => $cartaoId,
                'valor'      => $valor,
                'pontos'     => $pontos,
            ]);
            $doacaoId = (int) $pdo->lastInsertId();

            concederPontos($usuarioId, $pontos, 'doacao', $doacaoId, 'Cashback por doação #' . $doacaoId);

            $pdo->commit();
            redirecionar('/lume_pet/doacoes.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $erros[] = 'Não foi possível registrar a doação. Tente novamente.';
        }
    }
}

$ongs = $pdo->query('SELECT id, nome FROM ongs WHERE status = "parceira" ORDER BY nome')->fetchAll();

$stmt = $pdo->prepare('SELECT d.*, o.nome AS ong_nome
                        FROM doacoes d JOIN ongs o ON o.id = d.ong_id
                        WHERE d.usuario_id = :uid ORDER BY d.criado_em DESC');
$stmt->execute(['uid' => $usuarioId]);
$doacoes = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM cartoes WHERE usuario_id = :uid');
$stmt->execute(['uid' => $usuarioId]);
$cartoes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas doações - Lume Pet</title>
    <link rel="stylesheet" href="CSS/style.css?v=2">
</head>
<body>
<h1>Minhas doações</h1>
<p><a href="perfil.php">&larr; Voltar ao perfil</a></p>

<?php foreach ($erros as $erro): ?><p style="color:red;"><?= limpar($erro) ?></p><?php endforeach; ?>

<h2>Fazer uma doação</h2>
<form method="post" action="doacoes.php">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
    <input type="hidden" name="acao" value="doar">

    <label>ONG</label>
    <select name="ong_id" required>
        <option value="">Selecione...</option>
        <?php foreach ($ongs as $ong): ?>
            <option value="<?= $ong['id'] ?>"><?= limpar($ong['nome']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Valor (R$)</label>
    <input type="number" step="0.01" min="1" name="valor" required>

    <label>Cartão</label>
    <select name="cartao_id">
        <option value="">Selecione um cartão salvo</option>
        <?php foreach ($cartoes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= limpar($c['bandeira']) ?> final <?= limpar($c['ultimos_digitos']) ?></option>
        <?php endforeach; ?>
    </select>
    <p>adicionar um novo cartão em <a href="cartoes.php">Meus cartões</a>.</p>

    <button type="submit">Doar</button>
</form>
<p><small>Você ganha 1 ponto de cashback para cada R$ 1,00 doado.</small></p>

<h2>Histórico</h2>
<ul>
<?php foreach ($doacoes as $d): ?>
    <li>
        R$ <?= number_format($d['valor'], 2, ',', '.') ?> para <?= limpar($d['ong_nome']) ?>
        — <?= (int) $d['pontos_gerados'] ?> pontos —
        <?= (new DateTime($d['criado_em']))->format('d/m/Y H:i') ?>
        (<?= limpar($d['status']) ?>)
    </li>
<?php endforeach; ?>
</ul>
</body>
</html>