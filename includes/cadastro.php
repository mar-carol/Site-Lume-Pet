<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

$erros = [];
$dados = ['nome' => '', 'email' => ''];
$aceitouPolitica = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, recarregue a página e tente novamente.';
    }

    $dados = [
        'nome'     => trim($_POST['nome'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
    ];
    $senha          = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $aceitouPolitica = isset($_POST['aceitou_politica']) && $_POST['aceitou_politica'] === '1';

    if ($dados['nome'] === '') $erros[] = 'Informe seu nome completo.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (!$aceitouPolitica) $erros[] = 'Aceite a Política de Privacidade para continuar.';
    $erroSenha = validarSenhaCadastro($senha);
    if ($erroSenha !== null) $erros[] = $erroSenha;
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
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, telefone, endereco, email_verificado_em)
                                VALUES (:nome, :email, :senha_hash, :telefone, :endereco, NULL)');
        $stmt->execute([
            'nome'       => $dados['nome'],
            'email'      => $dados['email'],
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'telefone'   => '',
            'endereco'   => '',
        ]);

        $usuarioId = (int) $pdo->lastInsertId();
        $_SESSION['cadastro_pendente_id'] = $usuarioId;
        $codigo = gerarCodigoVerificacaoEmail();

        $stmt = $pdo->prepare(
            'INSERT INTO codigos_verificacao_email (usuario_id, codigo_hash, expira_em)
             VALUES (:usuario_id, :codigo_hash, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
             ON DUPLICATE KEY UPDATE codigo_hash = VALUES(codigo_hash),
                 expira_em = VALUES(expira_em), tentativas = 0, criado_em = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'codigo_hash' => hash('sha256', $codigo),
        ]);

        $enviado = enviarEmailResend(
            $dados['email'],
            'Confirme seu e-mail - Lume Pet',
            "Seu código de confirmação é: {$codigo}\n\n"
                . "Digite esse código na página de confirmação em até 15 minutos. "
                . "Se você não criou uma conta, ignore esta mensagem."
        );
        if (!$enviado) {
            $stmt = $pdo->prepare('DELETE FROM codigos_verificacao_email WHERE usuario_id = :usuario_id');
            $stmt->execute(['usuario_id' => $usuarioId]);
        }

        redirecionar(BASE_URL . '/pages/verificar-email.php?'
            . ($enviado ? 'enviado=1' : 'erro=envio'));
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

    <label for="cadastroSenha">Senha</label>
    <input id="cadastroSenha" type="password" name="senha" autocomplete="new-password" required minlength="8">
    <small class="cadastro-regras-senha">
        Mín. 8 caracteres: use letras sem acento, maiúsculas e minúsculas, números e pelo menos um destes símbolos: !@#$%&amp;*?^_.
        Não use espaços nem sequências de números, como 123, 321 ou 111.
    </small>

    <label for="cadastroConfirmarSenha">Confirmar senha</label>
    <input id="cadastroConfirmarSenha" type="password" name="confirmar_senha" autocomplete="new-password" required minlength="8">

    <div class="cadastro-politica">
        <input id="cadastroPolitica" type="checkbox" name="aceitou_politica" value="1" required <?= $aceitouPolitica ? 'checked' : '' ?>>
        <label for="cadastroPolitica">Li e concordo com a <a href="<?= BASE_URL ?>/pages/politica.php" target="_blank" rel="noopener noreferrer">Política de Privacidade</a>.</label>
    </div>

    <button type="submit">Cadastrar</button>
</form>

<p>Já possui uma conta? <a href="<?= BASE_URL ?>/includes/login.php">Entrar</a></p>
</section>
</main>
</body>
</html>