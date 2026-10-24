<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (usuarioEstaLogado()) {
    redirecionar(usuarioEhAdmin()
        ? BASE_URL . '/includes/index.php'
        : BASE_URL . '/usuario/dashboard.php');
}

$erro = '';
$retorno = in_array($_GET['retorno'] ?? $_POST['retorno'] ?? '', ['checkout', 'doacao'], true)
    ? ($_GET['retorno'] ?? $_POST['retorno'])
    : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada, recarregue a página e tente novamente.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $usuario = autenticarUsuario($email, $senha);

        if ($usuario) {
            if (empty($usuario['email_verificado_em'])) {
                $_SESSION['cadastro_pendente_id'] = (int) $usuario['id'];
                redirecionar(BASE_URL . '/pages/verificar-email.php');
            }
            iniciarSessaoUsuario($usuario);
            if ($retorno !== '' && $usuario['tipo'] === 'cliente') {
                redirecionar(BASE_URL . '/index.php?retorno=' . rawurlencode($retorno)
                    . ($retorno === 'doacao' ? '#doacao' : '#loja'));
            }
            redirecionar($usuario['tipo'] === 'admin'
                ? BASE_URL . '/includes/index.php'
                : BASE_URL . '/usuario/dashboard.php');
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
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body class="pagina-login">
<main class="login-pagina-conteudo">
<a class="login-pagina-logo" href="<?= BASE_URL ?>/index.php" aria-label="Voltar para a página inicial da Lume Pet">
    <img src="<?= BASE_URL ?>/assets/images/marcad.png" alt="Lume Pet">
</a>
<section class="login-pagina-card" aria-labelledby="loginTitulo">
<h1 id="loginTitulo">Entrar</h1>

<?php if ($erro): ?>
    <p class="login-pagina-erro" role="alert"><?= limpar($erro) ?></p>
<?php endif; ?>

<form class="login-pagina-form" method="post" action="<?= BASE_URL ?>/includes/login.php">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
    <input type="hidden" name="retorno" value="<?= limpar($retorno) ?>">

    <label for="loginEmail">E-mail</label>
    <input id="loginEmail" type="email" name="email" autocomplete="email" required>

    <label for="loginSenha">Senha</label>
    <input id="loginSenha" type="password" name="senha" autocomplete="current-password" required>

    <button type="submit">Entrar</button>
</form>

<p>Não tem conta? <a href="<?= BASE_URL ?>/includes/cadastro.php">Cadastre-se</a></p>
<p><a href="<?= BASE_URL ?>/pages/recuperar.php">Esqueci minha senha</a></p>
</section>
</main>
</body>
</html>