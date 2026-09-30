<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$erros = [];
$dados = ['nome' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, recarregue a página e tente novamente.';
    }

    $dados = [
        'nome'     => trim($_POST['nome'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
    ];
    $senha          = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if ($dados['nome'] === '') $erros[] = 'Informe seu nome completo.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if ($dados['telefone'] === '') $erros[] = 'Informe seu telefone.';
    if ($dados['endereco'] === '') $erros[] = 'Informe seu endereço.';
    if (strlen($senha) < 6) $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
    if ($senha !== $confirmarSenha) $erros[] = 'As senhas não conferem.';

    if (!$erros) {
        $pdo = getConexao();

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $dados['email']]);
        if ($stmt->fetch()) {
            $erros[] = 'Este e-mail já está cadastrado.';
        }
    }

    if (!$erros) {
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, telefone, endereco)
                                VALUES (:nome, :email, :senha_hash, :telefone, :endereco)');
        $stmt->execute([
            'nome'       => $dados['nome'],
            'email'      => $dados['email'],
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'telefone'   => $dados['telefone'],
            'endereco'   => $dados['endereco'],
        ]);

        $usuario = autenticarUsuario($dados['email'], $senha);
        iniciarSessaoUsuario($usuario);
        redirecionar('/lume_pet/perfil.php');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro - Lume Pet</title>
<link rel="stylesheet" href="../CSS/style.css?v=2">
</head>
<body>
<h1>Criar conta</h1>

<?php foreach ($erros as $erro): ?>
    <p style="color:red;"><?= limpar($erro) ?></p>
<?php endforeach; ?>

<form method="post" action="cadastro.php">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">

    <label>Nome completo</label>
    <input type="text" name="nome" value="<?= limpar($dados['nome']) ?>" required>

    <label>E-mail</label>
    <input type="email" name="email" value="<?= limpar($dados['email']) ?>" required>

    <label>Telefone</label>
    <input type="text" name="telefone" value="<?= limpar($dados['telefone']) ?>" required>

    <label>Endereço</label>
    <input type="text" name="endereco" value="<?= limpar($dados['endereco']) ?>" required>

    <label>Senha</label>
    <input type="password" name="senha" required minlength="6">

    <label>Confirmar senha</label>
    <input type="password" name="confirmar_senha" required minlength="6">

    <button type="submit">Cadastrar</button>
</form>

<p>Já possui uma conta? <a href="login.php">Entrar</a></p>
</body>
</html>