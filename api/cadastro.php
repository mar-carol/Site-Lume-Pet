<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

header('Content-Type: application/json; charset=utf-8');

function responderCadastro(
    bool $sucesso,
    string $erro = '',
    ?array $usuario = null,
    int $status = 200,
    ?string $urlVerificacao = null
): void
{
    http_response_code($status);
    echo json_encode([
        'sucesso' => $sucesso,
        'erro' => $erro,
        'usuario' => $usuario,
        'verificacao_pendente' => $urlVerificacao !== null,
        'url_verificacao' => $urlVerificacao,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$senha = $_POST['senha'] ?? '';
$senha2 = $_POST['senha2'] ?? '';
$aceitouPolitica = ($_POST['aceitou_politica'] ?? '') === '1';

if ($nome === '' || $email === '' || $senha === '' || $senha2 === '') {
    responderCadastro(false, 'Preencha todos os campos.', null, 422);
}

if (!$aceitouPolitica) {
    responderCadastro(false, 'Aceite a Política de Privacidade para continuar.', null, 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderCadastro(false, 'Informe um e-mail válido.', null, 422);
}

$erroSenha = validarSenhaCadastro($senha);
if ($erroSenha !== null) {
    responderCadastro(false, $erroSenha, null, 422);
}

if ($senha !== $senha2) {
    responderCadastro(false, 'As senhas não coincidem.', null, 422);
}

try {
    $pdo = getConexao();

    $stmt = $pdo->prepare(
        'SELECT id FROM usuarios WHERE email = :email LIMIT 1'
    );

    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
        responderCadastro(false, 'Já existe uma conta com este e-mail.', null, 409);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nome, email, senha_hash, telefone, endereco, email_verificado_em)
         VALUES (:nome, :email, :senha_hash, :telefone, :endereco, NULL)'
    );

    $stmt->execute([
        'nome' => $nome,
        'email' => $email,
        'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        'telefone' => '',
        'endereco' => ''
    ]);

    $id = (int) $pdo->lastInsertId();
    $_SESSION['cadastro_pendente_id'] = $id;
    $codigo = gerarCodigoVerificacaoEmail();
    $stmt = $pdo->prepare(
        'INSERT INTO codigos_verificacao_email (usuario_id, codigo_hash, expira_em)
         VALUES (:usuario_id, :codigo_hash, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
         ON DUPLICATE KEY UPDATE codigo_hash = VALUES(codigo_hash),
             expira_em = VALUES(expira_em), tentativas = 0, criado_em = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        'usuario_id' => $id,
        'codigo_hash' => hash('sha256', $codigo),
    ]);
    $enviado = enviarEmailResend(
        $email,
        'Confirme seu e-mail - Lume Pet',
        "Seu código de confirmação é: {$codigo}\n\n"
            . 'Digite esse código na página de confirmação em até 15 minutos. '
            . 'Se você não criou uma conta, ignore esta mensagem.'
    );
    if (!$enviado) {
        $stmt = $pdo->prepare('DELETE FROM codigos_verificacao_email WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $id]);
    }

    $urlVerificacao = BASE_URL . '/pages/verificar-email.php'
        . ($enviado ? '?enviado=1' : '?erro=envio');
    responderCadastro(
        true,
        $enviado ? '' : 'A conta foi criada, mas não foi possível enviar o código. Tente novamente pela página de confirmação.',
        null,
        201,
        $urlVerificacao
    );
} catch (Throwable $e) {
    error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
    responderCadastro(false, 'Não foi possível criar a conta agora. Tente novamente.', null, 500);
}