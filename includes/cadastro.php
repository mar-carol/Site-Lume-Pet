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
        redirecionar(BASE_URL . '/usuario/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro - Lume Pet</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body class="pagina-login">
<main class="login-pagina-conteudo">
<a class="login-pagina-logo" href="<?= BASE_URL ?>/index.php" aria-label="Voltar para a página inicial da Lume Pet">
    <img src="<?= BASE_URL ?>/assets/images/marcad.png" alt="Lume Pet">
</a>
<section class="login-pagina-card" aria-labelledby="cadastroTitulo">
<h1 id="cadastroTitulo">Criar conta</h1>

<?php foreach ($erros as $erro): ?>
    <p class="login-pagina-erro" role="alert"><?= limpar($erro) ?></p>
<?php endforeach; ?>

<form class="login-pagina-form" method="post" action="<?= BASE_URL ?>/includes/cadastro.php">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">

    <label for="cadastroNome">Nome completo</label>
    <input id="cadastroNome" type="text" name="nome" value="<?= limpar($dados['nome']) ?>" autocomplete="name" required>

    <label for="cadastroEmail">E-mail</label>
    <input id="cadastroEmail" type="email" name="email" value="<?= limpar($dados['email']) ?>" autocomplete="email" required>

    <label for="cadastroTelefone">Telefone</label>
    <input id="cadastroTelefone" type="text" name="telefone" value="<?= limpar($dados['telefone']) ?>" autocomplete="tel" required>

    <label for="cadastroEndereco">Endereço</label>
    <input id="cadastroEndereco" type="text" name="endereco" value="<?= limpar($dados['endereco']) ?>" autocomplete="street-address" required>

    <label for="cadastroSenha">Senha</label>
    <input id="cadastroSenha" type="password" name="senha" autocomplete="new-password" required minlength="6">

    <label for="cadastroConfirmarSenha">Confirmar senha</label>
    <input id="cadastroConfirmarSenha" type="password" name="confirmar_senha" autocomplete="new-password" required minlength="6">

    <button type="submit">Cadastrar</button>
</form>

<p>Já possui uma conta? <a href="<?= BASE_URL ?>/includes/login.php">Entrar</a></p>
</section>
</main>
</body>
</html>