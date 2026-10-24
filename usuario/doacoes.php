<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirLogin();
$usuarioId = $_SESSION['usuario_id'];
$pdo = getConexao();
$erros = [];

// Nova doação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'doar') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, tente novamente.';
    }

    $ongId = filter_var($_POST['ong_id'] ?? null, FILTER_VALIDATE_INT);
    $valorTexto = str_replace(',', '.', trim((string) ($_POST['valor'] ?? '')));
    $valor = is_numeric($valorTexto) ? (float) $valorTexto : 0.0;

    if (!$ongId || $ongId <= 0) $erros[] = 'Selecione uma ONG.';
    if (!is_finite($valor) || $valor < 1 || $valor > 1000000) $erros[] = 'Informe um valor de doação válido.';

    if (!$erros) {
        $stmt = $pdo->prepare('SELECT id FROM ongs WHERE id = :id AND status = "parceira"');
        $stmt->execute(['id' => $ongId]);
        if (!$stmt->fetchColumn()) {
            $erros[] = 'A ONG selecionada não está disponível.';
        }
    }

    if (!$erros) {
        $stmt = $pdo->prepare('INSERT INTO doacoes (usuario_id, ong_id, valor, pontos_gerados, status)
                                VALUES (:usuario_id, :ong_id, :valor, 0, "pendente")');
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'ong_id'     => $ongId,
            'valor'      => round($valor, 2),
        ]);
        redirecionar(BASE_URL . '/usuario/doacoes.php?registrada=1');
    }
}

$ongs = $pdo->query('SELECT id, nome FROM ongs WHERE status = "parceira" ORDER BY nome')->fetchAll();

$stmt = $pdo->prepare('SELECT d.*, o.nome AS ong_nome
                        FROM doacoes d JOIN ongs o ON o.id = d.ong_id
                        WHERE d.usuario_id = :uid ORDER BY d.criado_em DESC');
$stmt->execute(['uid' => $usuarioId]);
$doacoes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas doações - Lume Pet</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=3">
</head>
<body>
<?php require __DIR__ . '/../includes/user_home_logo.php'; ?>
<h1>Minhas doações</h1>
<p><a href="<?= BASE_URL ?>/usuario/dashboard.php">&larr; Voltar à minha conta</a></p>

<?php if (isset($_GET['registrada'])): ?>
    <p role="status">Sua intenção de doação foi registrada. Ela ficará pendente até a equipe confirmar o recebimento.</p>
<?php endif; ?>

<?php foreach ($erros as $erro): ?><p style="color:red;"><?= limpar($erro) ?></p><?php endforeach; ?>

<h2>Registrar uma doação</h2>
<p>Este site ainda não processa pagamentos. Registre a ONG e o valor; a equipe confirmará a doação após verificar o recebimento por fora do site.</p>
<form method="post" action="<?= BASE_URL ?>/usuario/doacoes.php">
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

    <button type="submit">Registrar intenção de doação</button>
</form>
<p><small>Após a confirmação do recebimento, você recebe 1 ponto por real inteiro doado.</small></p>

<h2>Histórico</h2>
<ul>
<?php foreach ($doacoes as $d): ?>
    <li>
        R$ <?= number_format($d['valor'], 2, ',', '.') ?> para <?= limpar($d['ong_nome']) ?>
        — <?= $d['status'] === 'confirmada'
            ? (int) $d['pontos_gerados'] . ' pontos creditados'
            : number_format(calcularPontosPorDoacao((float) $d['valor']), 0, ',', '.') . ' pontos após confirmação' ?> —
        <?= (new DateTime($d['criado_em']))->format('d/m/Y H:i') ?>
        (<?= limpar($d['status']) ?>)
    </li>
<?php endforeach; ?>
</ul>
</body>
</html>