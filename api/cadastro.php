<?php

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function responderCadastro(bool $sucesso, string $erro = '', ?array $usuario = null, int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'sucesso' => $sucesso,
        'erro' => $erro,
        'usuario' => $usuario
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$senha = $_POST['senha'] ?? '';
$senha2 = $_POST['senha2'] ?? '';

if ($nome === '' || $email === '' || $senha === '' || $senha2 === '') {
    responderCadastro(false, 'Preencha todos os campos.', null, 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderCadastro(false, 'Informe um e-mail válido.', null, 422);
}

if (strlen($senha) < 6) {
    responderCadastro(false, 'A senha deve ter no mínimo 6 caracteres.', null, 422);
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
        'INSERT INTO usuarios (nome, email, senha_hash, telefone, endereco)
         VALUES (:nome, :email, :senha_hash, :telefone, :endereco)'
    );

    $stmt->execute([
        'nome' => $nome,
        'email' => $email,
        'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        'telefone' => '',
        'endereco' => ''
    ]);

    $id = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare(
        'SELECT id, nome, email, tipo FROM usuarios WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $usuario = $stmt->fetch();

    iniciarSessaoUsuario($usuario);

    responderCadastro(true, '', $usuario);
} catch (Throwable $e) {
    error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
    responderCadastro(false, 'Não foi possível criar a conta agora. Tente novamente.', null, 500);
}