<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirLogin();
$usuarioId = $_SESSION['usuario_id'];
$pdo = getConexao();
$erros = [];
$cartoes = [];

// Nova doação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'doar') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, tente novamente.';
    }

    $ongId = filter_var($_POST['ong_id'] ?? null, FILTER_VALIDATE_INT);
    $valorTexto = str_replace(',', '.', trim((string) ($_POST['valor'] ?? '')));
    $valor = is_numeric($valorTexto) ? (float) $valorTexto : 0.0;
    $metodo = $_POST['metodo'] ?? '';
    $cartaoId = filter_var($_POST['cartao_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$ongId || $ongId <= 0) $erros[] = 'Selecione uma ONG.';
    if (!is_finite($valor) || $valor < 1 || $valor > 1000000) $erros[] = 'Informe um valor de doação válido.';
    if (!in_array($metodo, ['PIX', 'credito', 'debito'], true)) $erros[] = 'Selecione uma forma de pagamento válida.';

    $cartaoSalvoId = null;
    if (in_array($metodo, ['credito', 'debito'], true)) {
        if (!$cartaoId || $cartaoId < 1) {
            $erros[] = 'Selecione um cartão salvo compatível.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id FROM cartoes
                 WHERE id = :id AND usuario_id = :usuario_id
                   AND (tipo_uso = :tipo OR tipo_uso = "ambos")'
            );
            $stmt->execute(['id' => $cartaoId, 'usuario_id' => $usuarioId, 'tipo' => $metodo]);
            $cartaoSalvoId = $stmt->fetchColumn();
            if (!$cartaoSalvoId) $erros[] = 'O cartão selecionado não pertence à sua conta ou não aceita essa modalidade.';
        }
    }

    if (!$erros) {
        $stmt = $pdo->prepare('SELECT id FROM ongs WHERE id = :id AND status = "parceira"');
        $stmt->execute(['id' => $ongId]);
        if (!$stmt->fetchColumn()) {
            $erros[] = 'A ONG selecionada não está disponível.';
        }
    }

    if (!$erros) {
        $nomeMetodo = $metodo === 'PIX' ? 'PIX' : ($metodo === 'credito' ? 'Cartão de Crédito' : 'Cartão de Débito');
        $stmt = $pdo->prepare('INSERT INTO doacoes (usuario_id, ong_id, cartao_id, metodo_pagamento, valor, pontos_gerados, status)
                                VALUES (:usuario_id, :ong_id, :cartao_id, :metodo, :valor, 0, "pendente")');
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'ong_id'     => $ongId,
            'cartao_id'  => $cartaoSalvoId,
            'metodo'     => $nomeMetodo,
            'valor'      => round($valor, 2),
        ]);
        redirecionar(BASE_URL . '/usuario/doacoes.php?registrada=1');
    }
}

$ongs = $pdo->query('SELECT id, nome FROM ongs WHERE status = "parceira" ORDER BY nome')->fetchAll();
$stmt = $pdo->prepare('SELECT id, bandeira, ultimos_digitos, nome_titular, tipo_uso FROM cartoes WHERE usuario_id = :uid ORDER BY criado_em DESC');
$stmt->execute(['uid' => $usuarioId]);
$cartoes = $stmt->fetchAll();

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

    <label>Forma de pagamento</label>
    <select name="metodo" id="metodo-doacao-conta" required>
        <option value="PIX">PIX</option>
        <option value="credito">Cartão de crédito</option>
        <option value="debito">Cartão de débito</option>
    </select>

    <label for="cartao-doacao-conta">Cartão salvo (para crédito/débito)</label>
    <select name="cartao_id" id="cartao-doacao-conta">
        <option value="">Selecione um cartão</option>
        <?php foreach ($cartoes as $cartao): ?>
            <option value="<?= (int) $cartao['id'] ?>"
                    data-tipo="<?= limpar($cartao['tipo_uso']) ?>">
                <?= limpar(ucfirst($cartao['bandeira'])) ?> •••• <?= limpar($cartao['ultimos_digitos']) ?>
                — <?= limpar($cartao['nome_titular']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p><a href="<?= BASE_URL ?>/usuario/cartoes.php">Cadastrar ou gerenciar cartões</a></p>

    <button type="submit">Registrar intenção de doação</button>
</form>
<p><small>Após a confirmação do recebimento, você recebe 1 ponto por real inteiro doado.</small></p>

<h2>Histórico</h2>
<ul>
<?php foreach ($doacoes as $d): ?>
    <li>
        R$ <?= number_format($d['valor'], 2, ',', '.') ?> para <?= limpar($d['ong_nome']) ?>
        — pagamento: <?= limpar($d['metodo_pagamento'] ?: 'Não informado') ?>
        — <?= $d['status'] === 'confirmada'
            ? (int) $d['pontos_gerados'] . ' pontos creditados'
            : number_format(calcularPontosPorDoacao((float) $d['valor']), 0, ',', '.') . ' pontos após confirmação' ?> —
        <?= (new DateTime($d['criado_em']))->format('d/m/Y H:i') ?>
        (<?= limpar($d['status']) ?>)
    </li>
<?php endforeach; ?>
</ul>
<script>
document.getElementById('metodo-doacao-conta').addEventListener('change', function () {
    const cartao = document.getElementById('cartao-doacao-conta');
    const metodo = this.value;
    cartao.disabled = metodo === 'PIX';
    [...cartao.options].forEach(opcao => {
        if (!opcao.value) return;
        opcao.hidden = metodo !== 'PIX' && opcao.dataset.tipo !== metodo && opcao.dataset.tipo !== 'ambos';
    });
});
document.getElementById('metodo-doacao-conta').dispatchEvent(new Event('change'));
</script>
</body>
</html>