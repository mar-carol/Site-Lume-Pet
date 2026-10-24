<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

$pdo = getConexao();
$mensagem = '';
$erro = '';
$acao = $_POST['acao'] ?? '';
$tokenInput = $_POST['token'] ?? $_GET['token'] ?? '';
$token = is_string($tokenInput) ? $tokenInput : '';
$tokenValido = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!is_string($csrfToken) || !validarTokenCsrf($csrfToken)) {
        $erro = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif ($acao === 'solicitar') {
        $emailInput = $_POST['email'] ?? '';
        $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um e-mail válido.';
        } else {
            $mensagem = 'Se o e-mail estiver cadastrado, enviaremos um link para redefinir sua senha.';
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email AND status = "ativo" LIMIT 1');
            $stmt->execute(['email' => $email]);
            $usuarioId = $stmt->fetchColumn();

            if ($usuarioId) {
                $stmt = $pdo->prepare(
                    'SELECT id FROM password_reset_tokens
                     WHERE usuario_id = :usuario_id AND criado_em > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                     LIMIT 1'
                );
                $stmt->execute(['usuario_id' => $usuarioId]);
                $solicitacaoRecente = (bool) $stmt->fetchColumn();

                if (!$solicitacaoRecente) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);

                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE usuario_id = :usuario_id');
                        $stmt->execute(['usuario_id' => $usuarioId]);
                        $stmt = $pdo->prepare(
                            'INSERT INTO password_reset_tokens (usuario_id, token_hash, expires_at)
                             VALUES (:usuario_id, :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
                        );
                        $stmt->execute(['usuario_id' => $usuarioId, 'token_hash' => $tokenHash]);
                        $pdo->commit();
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log('Falha ao criar token de recuperação de senha: ' . $e->getMessage());
                        $erro = 'Não foi possível processar a solicitação agora. Tente novamente mais tarde.';
                    }

                    if ($erro === '') {
                        $urlPublica = rtrim(getenv('LUME_PET_PUBLIC_URL') ?: ('http://localhost' . BASE_URL), '/');
                        $link = $urlPublica . '/pages/recuperar.php?token=' . rawurlencode($token);
                        $corpo = "Recebemos uma solicitação para redefinir a senha da sua conta Lume Pet.\n\n"
                            . "Use o link abaixo em até 1 hora. Se você não solicitou a redefinição, ignore este e-mail.\n\n"
                            . $link . "\n";

                        if (!enviarEmailResend($email, 'Redefinição de senha - Lume Pet', $corpo)) {
                            $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE token_hash = :token_hash');
                            $stmt->execute(['token_hash' => $tokenHash]);
                            $erro = 'Não foi possível enviar o link agora. Confira a configuração do serviço de e-mail e tente novamente mais tarde.';
                            $mensagem = '';
                        }
                    }
                }
            }
        }
    } elseif ($acao === 'redefinir') {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $erro = 'Este link de recuperação é inválido ou expirou. Solicite um novo.';
        } else {
            $senhaInput = $_POST['senha'] ?? '';
            $confirmacaoInput = $_POST['confirmacao'] ?? '';
            $senha = is_string($senhaInput) ? $senhaInput : '';
            $confirmacao = is_string($confirmacaoInput) ? $confirmacaoInput : '';
            $erroSenha = validarSenhaCadastro($senha);

            if ($erroSenha !== null) {
                $erro = $erroSenha;
            } elseif ($senha !== $confirmacao) {
                $erro = 'As senhas não coincidem.';
            } else {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare(
                        'SELECT id, usuario_id FROM password_reset_tokens
                         WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW()
                         LIMIT 1 FOR UPDATE'
                    );
                    $stmt->execute(['token_hash' => hash('sha256', $token)]);
                    $registro = $stmt->fetch();

                    if (!$registro) {
                        $pdo->rollBack();
                        $erro = 'Este link de recuperação é inválido ou expirou. Solicite um novo.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :usuario_id');
                        $stmt->execute([
                            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
                            'usuario_id' => $registro['usuario_id'],
                        ]);
                        $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE usuario_id = :usuario_id');
                        $stmt->execute(['usuario_id' => $registro['usuario_id']]);
                        $pdo->commit();
                        $mensagem = 'Sua senha foi redefinida. Já pode entrar na sua conta.';
                        $token = '';
                    }
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('Falha ao redefinir senha: ' . $e->getMessage());
                    $erro = 'Não foi possível redefinir sua senha agora. Tente novamente.';
                }
            }
        }
    }
}

if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = $pdo->prepare(
        'SELECT id FROM password_reset_tokens
         WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute(['token_hash' => hash('sha256', $token)]);
    $tokenValido = (bool) $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha - Lume Pet</title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/imgicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body class="pagina-login">
<main class="login-pagina-conteudo">
    <a class="login-pagina-logo" href="<?= BASE_URL ?>/index.php" aria-label="Voltar ao início da Lume Pet">
        <img src="<?= BASE_URL ?>/assets/images/marcad.png" alt="Lume Pet">
    </a>
    <section class="login-pagina-card" aria-labelledby="recuperarTitulo">
        <h1 id="recuperarTitulo"><?= $tokenValido ? 'Criar nova senha' : 'Recuperar senha' ?></h1>

        <?php if ($erro !== ''): ?>
            <p class="login-pagina-erro" role="alert"><?= limpar($erro) ?></p>
        <?php endif; ?>
        <?php if ($mensagem !== ''): ?>
            <p class="recuperar-mensagem" role="status"><?= limpar($mensagem) ?></p>
        <?php endif; ?>

        <?php if ($mensagem === '' && $tokenValido): ?>
            <form class="login-pagina-form" method="post" action="<?= BASE_URL ?>/pages/recuperar.php">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="redefinir">
                <input type="hidden" name="token" value="<?= limpar($token) ?>">

                <label for="senha">Nova senha</label>
                <input id="senha" type="password" name="senha" autocomplete="new-password" minlength="8" required>
                <small class="cadastro-regras-senha">
                    Mín. 8 caracteres, com letras maiúsculas e minúsculas, números e um símbolo (!@#$%&amp;*?^_).
                    Não use espaços nem sequências numéricas como 123, 321 ou 111.
                </small>

                <label for="confirmacao">Confirme a nova senha</label>
                <input id="confirmacao" type="password" name="confirmacao" autocomplete="new-password" minlength="8" required>
                <button type="submit">Redefinir senha</button>
            </form>
        <?php elseif ($mensagem === '' && $token !== ''): ?>
            <p>O link é inválido, já foi usado ou expirou. Solicite um novo link abaixo.</p>
            <form class="login-pagina-form" method="post" action="<?= BASE_URL ?>/pages/recuperar.php">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="solicitar">
                <label for="email">E-mail cadastrado</label>
                <input id="email" type="email" name="email" autocomplete="email" required>
                <button type="submit">Enviar novo link</button>
            </form>
        <?php elseif ($mensagem === ''): ?>
            <p>Informe o e-mail da sua conta. Se estiver cadastrado, enviaremos um link válido por 1 hora.</p>
            <form class="login-pagina-form" method="post" action="<?= BASE_URL ?>/pages/recuperar.php">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="solicitar">
                <label for="email">E-mail cadastrado</label>
                <input id="email" type="email" name="email" autocomplete="email" required>
                <button type="submit">Enviar link de recuperação</button>
            </form>
        <?php endif; ?>

        <p><a href="<?= BASE_URL ?>/includes/login.php">Voltar para o login</a></p>
    </section>
</main>
</body>
</html>
