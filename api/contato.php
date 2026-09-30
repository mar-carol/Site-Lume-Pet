<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function responderContato(bool $sucesso, string $erro = '', int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'sucesso' => $sucesso,
        'erro' => $erro
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderContato(false, 'Método não permitido.', 405);
}

$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) {
    $dados = $_POST;
}

$nome = trim($dados['nome'] ?? '');
$email = strtolower(trim($dados['email'] ?? ''));
$telefone = trim($dados['telefone'] ?? '');
$assunto = trim($dados['assunto'] ?? '');
$mensagem = trim($dados['mensagem'] ?? '');

if ($nome === '') {
    responderContato(false, 'Informe seu nome.', 422);
}

if (strlen(preg_replace('/\D/', '', $telefone)) < 10) {
    responderContato(false, 'Informe um telefone válido.', 422);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderContato(false, 'Informe um e-mail válido.', 422);
}

try {
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'INSERT INTO mensagens_contato (nome, email, telefone, assunto, mensagem)
         VALUES (:nome, :email, :telefone, :assunto, :mensagem)'
    );
    $stmt->execute([
        'nome' => $nome,
        'email' => $email !== '' ? $email : null,
        'telefone' => $telefone,
        'assunto' => $assunto !== '' ? $assunto : null,
        'mensagem' => $mensagem !== '' ? $mensagem : null
    ]);

    responderContato(true);
} catch (Throwable $e) {
    error_log('Erro ao salvar mensagem de contato: ' . $e->getMessage());
    responderContato(false, 'Não foi possível enviar sua mensagem agora. Tente novamente.', 500);
}