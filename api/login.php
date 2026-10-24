<?php

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$email = strtolower(trim($_POST['email'] ?? ''));
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Preencha e-mail e senha.'
    ]);
    exit;
}

$usuario = autenticarUsuario($email, $senha);

if (!$usuario) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'E-mail ou senha incorretos.'
    ]);
    exit;
}

if (empty($usuario['email_verificado_em'])) {
    $_SESSION['cadastro_pendente_id'] = (int) $usuario['id'];
    echo json_encode([
        'sucesso' => false,
        'verificacao_pendente' => true,
        'url_verificacao' => BASE_URL . '/pages/verificar-email.php',
        'erro' => 'Confirme seu e-mail para entrar.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

iniciarSessaoUsuario($usuario);

echo json_encode([
    'sucesso' => true,
    'usuario' => [
        'id' => $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'tipo' => $usuario['tipo']
    ]
]);