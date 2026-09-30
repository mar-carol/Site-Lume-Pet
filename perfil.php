<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exigirLogin();
$usuarioId = $_SESSION['usuario_id'];
$pdo = getConexao();
$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, tente novamente.';
    }

    $nome     = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade   = trim($_POST['cidade'] ?? '');
    $estado   = trim($_POST['estado'] ?? '');
    $cep      = trim($_POST['cep'] ?? '');

    if ($nome === '') $erros[] = 'Informe seu nome.';
    if ($telefone === '') $erros[] = 'Informe seu telefone.';
    if ($endereco === '') $erros[] = 'Informe seu endereço.';

    if (!$erros) {
        $stmt = $pdo->prepare('UPDATE usuarios SET nome=:nome, telefone=:telefone, endereco=:endereco,
                                cidade=:cidade, estado=:estado, cep=:cep WHERE id=:id');
        $stmt->execute([
            'nome' => $nome, 'telefone' => $telefone, 'endereco' => $endereco,
            'cidade' => $cidade, 'estado' => $estado, 'cep' => $cep, 'id' => $usuarioId,
        ]);
        $_SESSION['usuario_nome'] = $nome;
        $sucesso = true;
    }
}

$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
$stmt->execute(['id' => $usuarioId]);
$usuario = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu perfil - Lume Pet</title>
    <link rel="stylesheet" href="CSS/style.css?v=2">
</head>
<body>
<h1>Olá, <?= limpar($usuario['nome']) ?></h1>

<nav>
    <a href="perfil.php">Meu perfil</a> |
    <a href="compras.php">Minhas compras</a> |
    <a href="doacoes.php">Minhas doações</a> |
    <a href="pontos.php">Meus pontos (<?= (int) $usuario['pontos'] ?>)</a> |
    <a href="cartoes.php">Meus cartões</a> |
    <a href="INCLUDES/logout.php">Sair</a>
</nav>

<?php if ($sucesso): ?><p style="color:green;">Dados atualizados com sucesso!</p><?php endif; ?>
<?php foreach ($erros as $erro): ?><p style="color:red;"><?= limpar($erro) ?></p><?php endforeach; ?>

<form method="post" action="perfil.php">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">

    <label>Nome</label>
    <input type="text" name="nome" value="<?= limpar($usuario['nome']) ?>" required>

    <label>E-mail</label>
    <input type="email" value="<?= limpar($usuario['email']) ?>" disabled>

    <label>Telefone</label>
    <input type="text" name="telefone" value="<?= limpar($usuario['telefone']) ?>" required>

    <label>Endereço</label>
    <input type="text" name="endereco" value="<?= limpar($usuario['endereco']) ?>" required>

    <label>Cidade</label>
    <input type="text" name="cidade" value="<?= limpar($usuario['cidade'] ?? '') ?>">

    <label>Estado</label>
    <input type="text" name="estado" maxlength="2" value="<?= limpar($usuario['estado'] ?? '') ?>">

    <label>CEP</label>
    <input type="text" name="cep" value="<?= limpar($usuario['cep'] ?? '') ?>">

    <button type="submit">Salvar</button>
</form>
</body>
</html>