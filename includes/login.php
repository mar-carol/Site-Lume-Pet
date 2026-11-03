<?php
/* Dependências de autenticação e funções gerais */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

/* Redireciona usuário já logado para o painel correspondente */
if (usuarioEstaLogado()) {
    redirecionar(usuarioEhAdmin()
        ? BASE_URL . '/includes/index.php'
        : BASE_URL . '/usuario/dashboard.php');
}

/* Mensagem de erro do formulário */
$erro = '';
/* Página de retorno após o login (checkout ou doação) */
$retorno = in_array($_GET['retorno'] ?? $_POST['retorno'] ?? '', ['checkout', 'doacao'], true)
    ? ($_GET['retorno'] ?? $_POST['retorno'])
    : '';

/* Processamento do formulário de login */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* Validação do token CSRF */
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada, recarregue a página e tente novamente.';
    } else {
        /* Dados enviados pelo usuário */
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        /* Verificação das credenciais */
        $usuario = autenticarUsuario($email, $senha);

        if ($usuario) {
            /* Início da sessão do usuário */
            iniciarSessaoUsuario($usuario);
            /* Volta para a loja ou doação se o cliente veio de lá */
            if ($retorno !== '' && $usuario['tipo'] === 'cliente') {
                redirecionar(BASE_URL . '/index.php?retorno=' . rawurlencode($retorno)
                    . ($retorno === 'doacao' ? '#doacao' : '#loja'));
            }
            /* Redirecionamento para o painel (admin ou cliente) */
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
<!-- Estilos (o filemtime evita cache desatualizado) -->
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body class="pagina-login">
<main class="login-pagina-conteudo">
<!-- Logo com link para a página inicial -->
<a class="login-pagina-logo" href="<?= BASE_URL ?>/index.php" aria-label="Voltar para a página inicial da Lume Pet">
    <img src="<?= BASE_URL ?>/assets/images/marcad.png" alt="Lume Pet">
</a>
<!-- Card de login -->
<section class="login-pagina-card" aria-labelledby="loginTitulo">
<h1 id="loginTitulo">Entrar</h1>

<!-- Mensagem de erro -->
<?php if ($erro): ?>
    <p class="login-pagina-erro" role="alert"><?= limpar($erro) ?></p>
<?php endif; ?>

<!-- Formulário de login -->
<form class="login-pagina-form" method="post" action="<?= BASE_URL ?>/includes/login.php">
    <!-- Campos ocultos: token CSRF e página de retorno -->
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
    <input type="hidden" name="retorno" value="<?= limpar($retorno) ?>">

    <!-- Campo de e-mail -->
    <label for="loginEmail">E-mail</label>
    <input id="loginEmail" type="email" name="email" autocomplete="email" required>

    <!-- Campo de senha -->
    <label for="loginSenha">Senha</label>
    <input id="loginSenha" type="password" name="senha" autocomplete="current-password" required>

    <!-- Botão de envio -->
    <button type="submit">Entrar</button>
</form>

<!-- Links de cadastro e recuperação de senha -->
<p>Não tem conta? <a href="<?= BASE_URL ?>/includes/cadastro.php">Cadastre-se</a></p>
<p><a href="<?= BASE_URL ?>/pages/recuperar.php">Esqueci minha senha</a></p>
</section>
</main>
</body>
</html>