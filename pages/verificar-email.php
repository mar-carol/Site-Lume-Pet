<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

$usuarioId = filter_var($_SESSION['cadastro_pendente_id'] ?? null, FILTER_VALIDATE_INT);
if (!$usuarioId || $usuarioId < 1) {
    redirecionar(BASE_URL . '/includes/login.php');
}

$pdo = getConexao();
$stmt = $pdo->prepare('SELECT id, nome, email, email_verificado_em FROM usuarios WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $usuarioId]);
$usuario = $stmt->fetch();
if (!$usuario) {
    unset($_SESSION['cadastro_pendente_id']);
    redirecionar(BASE_URL . '/includes/login.php');
}
if (!empty($usuario['email_verificado_em'])) {
    unset($_SESSION['cadastro_pendente_id']);
    redirecionar(BASE_URL . '/includes/login.php?verificado=1');
}

$erro = '';
$mensagem = isset($_GET['enviado'])
    ? 'Enviamos um código de 6 caracteres para o seu e-mail. Ele expira em 15 minutos.'
    : '';

if (isset($_GET['erro']) && $_GET['erro'] === 'envio') {
    $erro = 'Não foi possível enviar o código agora. Confira a configuração do serviço de e-mail e tente novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    $acao = $_POST['acao'] ?? '';
    if (!is_string($csrfToken) || !validarTokenCsrf($csrfToken)) {
        $erro = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif ($acao === 'verificar') {
        $codigoEntrada = $_POST['codigo'] ?? '';
        $codigo = is_string($codigoEntrada) ? strtoupper(trim($codigoEntrada)) : '';
        if (!preg_match('/^[A-Z0-9]{6}$/', $codigo)) {
            $erro = 'Digite o código de 6 letras e números recebido por e-mail.';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare(
                    'SELECT codigo_hash, (expira_em > NOW()) AS ainda_valido, tentativas
                     FROM codigos_verificacao_email
                     WHERE usuario_id = :usuario_id LIMIT 1 FOR UPDATE'
                );
                $stmt->execute(['usuario_id' => $usuarioId]);
                $registro = $stmt->fetch();

                if (!$registro || !(int) $registro['ainda_valido']) {
                    $erro = 'O código expirou ou não existe. Solicite um novo código.';
                } elseif ((int) $registro['tentativas'] >= 5) {
                    $erro = 'O limite de tentativas foi atingido. Solicite um novo código.';
                } elseif (!hash_equals($registro['codigo_hash'], hash('sha256', $codigo))) {
                    $stmt = $pdo->prepare(
                        'UPDATE codigos_verificacao_email
                         SET tentativas = tentativas + 1 WHERE usuario_id = :usuario_id'
                    );
                    $stmt->execute(['usuario_id' => $usuarioId]);
                    $erro = 'Código incorreto. Confira o e-mail e tente novamente.';
                } else {
                    $stmt = $pdo->prepare('UPDATE usuarios SET email_verificado_em = NOW() WHERE id = :id');
                    $stmt->execute(['id' => $usuarioId]);
                    $stmt = $pdo->prepare('DELETE FROM codigos_verificacao_email WHERE usuario_id = :usuario_id');
                    $stmt->execute(['usuario_id' => $usuarioId]);
                    $pdo->commit();

                    unset($_SESSION['cadastro_pendente_id']);
                    $usuario['email_verificado_em'] = date('Y-m-d H:i:s');
                    iniciarSessaoUsuario($usuario);
                    redirecionar(BASE_URL . '/usuario/dashboard.php');
                }

                if ($pdo->inTransaction()) {
                    $pdo->commit();
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Falha ao verificar e-mail do usuário #' . $usuarioId . ': ' . $e->getMessage());
                $erro = 'Não foi possível confirmar o e-mail agora. Tente novamente.';
            }
        }
    } elseif ($acao === 'reenviar') {
        $stmt = $pdo->prepare(
            'SELECT criado_em > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
             FROM codigos_verificacao_email WHERE usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        if ((int) $stmt->fetchColumn() === 1) {
            $erro = 'Aguarde um minuto antes de solicitar outro código.';
        } else {
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
            if (enviarEmailResend(
                $usuario['email'],
                'Seu novo código de confirmação - Lume Pet',
                "Seu novo código de confirmação é: {$codigo}\n\n"
                    . 'Digite esse código na página de confirmação em até 15 minutos.'
            )) {
                redirecionar(BASE_URL . '/pages/verificar-email.php?enviado=1');
            }

            $stmt = $pdo->prepare('DELETE FROM codigos_verificacao_email WHERE usuario_id = :usuario_id');
            $stmt->execute(['usuario_id' => $usuarioId]);
            $erro = 'Não foi possível enviar o código agora. Confira a configuração do serviço de e-mail e tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmar e-mail - Lume Pet</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body class="pagina-login">
<main class="login-pagina-conteudo">
    <a class="login-pagina-logo" href="<?= BASE_URL ?>/index.php" aria-label="Voltar para a página inicial da Lume Pet">
        <img src="<?= BASE_URL ?>/assets/images/marcad.png" alt="Lume Pet">
    </a>
    <section class="login-pagina-card" aria-labelledby="verificarEmailTitulo">
        <h1 id="verificarEmailTitulo">Confirme seu e-mail</h1>
        <p>Enviamos um código para <strong><?= limpar($usuario['email']) ?></strong>. Confirme o endereço para liberar sua conta.</p>
        <?php if ($mensagem !== ''): ?>
            <p class="login-pagina-sucesso" role="status"><?= limpar($mensagem) ?></p>
        <?php endif; ?>
        <?php if ($erro !== ''): ?>
            <p class="login-pagina-erro" role="alert"><?= limpar($erro) ?></p>
        <?php endif; ?>
        <form class="login-pagina-form" method="post" action="<?= BASE_URL ?>/pages/verificar-email.php">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
            <input type="hidden" name="acao" value="verificar">
            <label for="codigoVerificacao">Código de 6 caracteres</label>
            <input id="codigoVerificacao" class="codigo-verificacao" type="text" name="codigo" minlength="6" maxlength="6"
                   pattern="[A-Za-z0-9]{6}" autocomplete="one-time-code" required>
            <button type="submit">Confirmar e-mail</button>
        </form>
        <form class="login-pagina-form" method="post" action="<?= BASE_URL ?>/pages/verificar-email.php">
            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
            <input type="hidden" name="acao" value="reenviar">
            <button type="submit">Enviar outro código</button>
        </form>
        <p><a href="<?= BASE_URL ?>/includes/logout.php">Sair e voltar para o login</a></p>
    </section>
</main>
</body>
</html>
