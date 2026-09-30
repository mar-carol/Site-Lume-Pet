<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (usuarioEstaLogado()) {
    redirecionar(usuarioEhAdmin() ? '/lume_pet/INCLUDES/index.php' : '/lume_pet/perfil.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada, recarregue a página e tente novamente.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $usuario = autenticarUsuario($email, $senha);

        if ($usuario) {
            iniciarSessaoUsuario($usuario);
            redirecionar($usuario['tipo'] === 'admin' ? '/lume_pet/INCLUDES/index.php' : '/lume_pet/perfil.php');
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Lume Pet</title>
<link rel="stylesheet" href="../CSS/style.css?v=2">
</head>
<body>
<h1>Entrar</h1>

<?php if ($erro): ?>
    <p style="color:red;"><?= limpar($erro) ?></p>
<?php endif; ?>

<form method="post" action="login.php">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">

    <label>E-mail</label>
    <input type="email" name="email" required>

    <label>Senha</label>
    <input type="password" name="senha" required>

    <button type="submit">Entrar</button>
</form>

<p>Não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
</body>
</html>