<?php
/**
 * Cadastro de usuários da plataforma Lume Pet.
 * Realiza a validação dos dados, o registro no banco
 * e a inicialização da sessão do novo usuário.
 */

// Importação dos arquivos de autenticação e funções auxiliares
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Inicialização das variáveis utilizadas no cadastro
$erros = [];
$dados = ['nome' => '', 'email' => ''];
$aceitouPolitica = false;

// Verifica se o formulário foi enviado pelo método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validação do token CSRF para proteger o envio do formulário
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, recarregue a página e tente novamente.';
    }

    // Recebimento e organização dos dados informados pelo usuário
    $dados = [
        'nome'     => trim($_POST['nome'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
    ];
    $senha          = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $aceitouPolitica = isset($_POST['aceitou_politica']) && $_POST['aceitou_politica'] === '1';

    // Validação do nome, e-mail e aceitação da Política de Privacidade
    if ($dados['nome'] === '') $erros[] = 'Informe seu nome completo.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (!$aceitouPolitica) $erros[] = 'Aceite a Política de Privacidade para continuar.';

    // Verificação dos requisitos de segurança e confirmação da senha
    $erroSenha = validarSenhaCadastro($senha);
    if ($erroSenha !== null) $erros[] = $erroSenha;
    if ($senha !== $confirmarSenha) $erros[] = 'As senhas não conferem.';

    // Consulta ao banco de dados para verificar a existência do e-mail
    if (!$erros) {
        $pdo = getConexao();

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $dados['email']]);
        if ($stmt->fetch()) {
            $erros[] = 'Este e-mail já está cadastrado.';
        }
    }

    // Cadastro do novo usuário caso nenhuma validação apresente erros
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

        // Recuperação do ID gerado e inicialização da sessão do usuário
        $usuarioId = (int) $pdo->lastInsertId();
        iniciarSessaoUsuario([
            'id' => $usuarioId,
            'nome' => $dados['nome'],
            'tipo' => 'cliente',
        ]);

        // Redirecionamento para o painel do usuário após o cadastro
        redirecionar(BASE_URL . '/usuario/dashboard.php');
    }
}
?>

<!-- Estrutura HTML da página de cadastro de usuários -->
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

<!-- Logotipo com acesso à página inicial da plataforma -->
<a class="login-pagina-logo" href="<?= BASE_URL ?>/index.php" aria-label="Voltar para a página inicial da Lume Pet">
    <img src="<?= BASE_URL ?>/assets/images/marcad.png" alt="Lume Pet">
</a>

<!-- Área principal do formulário de cadastro -->
<section class="login-pagina-card" aria-labelledby="cadastroTitulo">
<h1 id="cadastroTitulo">Criar conta</h1>

<!-- Exibição das mensagens de erro identificadas nas validações -->
<?php foreach ($erros as $erro): ?>
    <p class="login-pagina-erro" role="alert"><?= limpar($erro) ?></p>
<?php endforeach; ?>

<!-- Formulário responsável pelo envio dos dados do novo usuário -->
<form class="login-pagina-form" method="post" action="<?= BASE_URL ?>/includes/cadastro.php">

    <!-- Campo oculto contendo o token de segurança CSRF -->
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">

    <!-- Campos para preenchimento do nome completo e e-mail -->
    <label for="cadastroNome">Nome completo</label>
    <input id="cadastroNome" type="text" name="nome" value="<?= limpar($dados['nome']) ?>" autocomplete="name" required>

    <label for="cadastroEmail">E-mail</label>
    <input id="cadastroEmail" type="email" name="email" value="<?= limpar($dados['email']) ?>" autocomplete="email" required>

    <!-- Campo de criação da senha e apresentação dos requisitos de segurança -->
    <label for="cadastroSenha">Senha</label>
    <input id="cadastroSenha" type="password" name="senha" autocomplete="new-password" required minlength="8">
    <small class="cadastro-regras-senha">
        Mín. 8 caracteres: use letras sem acento, maiúsculas e minúsculas, números e pelo menos um destes símbolos: !@#$%&amp;*?^_.
        Não use espaços nem sequências de números, como 123, 321 ou 111.
    </small>

    <!-- Campo para confirmação da senha informada -->
    <label for="cadastroConfirmarSenha">Confirmar senha</label>
    <input id="cadastroConfirmarSenha" type="password" name="confirmar_senha" autocomplete="new-password" required minlength="8">

    <!-- Confirmação obrigatória da aceitação da Política de Privacidade -->
    <div class="cadastro-politica">
        <input id="cadastroPolitica" type="checkbox" name="aceitou_politica" value="1" required <?= $aceitouPolitica ? 'checked' : '' ?>>
        <label for="cadastroPolitica">Li e concordo com a <a href="<?= BASE_URL ?>/pages/politica.php" target="_blank" rel="noopener noreferrer">Política de Privacidade</a>.</label>
    </div>

    <!-- Botão responsável pelo envio do formulário -->
    <button type="submit">Cadastrar</button>
</form>

<!-- Redirecionamento para a página de login de usuários cadastrados -->
<p>Já possui uma conta? <a href="<?= BASE_URL ?>/includes/login.php">Entrar</a></p>
</section>
</main>
</body>
</html>