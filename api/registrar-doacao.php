<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function responderDoacao(array $dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!usuarioEstaLogado() || usuarioEhAdmin()) {
    responderDoacao(['sucesso' => false, 'erro' => 'Entre na sua conta de cliente para registrar uma doação.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderDoacao(['sucesso' => false, 'erro' => 'Método não permitido.'], 405);
}

$tokenCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($tokenCsrf) || !validarTokenCsrf($tokenCsrf)) {
    responderDoacao(['sucesso' => false, 'erro' => 'Sua sessão expirou. Atualize a página e tente novamente.'], 403);
}

$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) {
    responderDoacao(['sucesso' => false, 'erro' => 'Dados de doação inválidos.'], 400);
}

$ongId = filter_var($dados['ong_id'] ?? null, FILTER_VALIDATE_INT);
$valorTexto = $dados['valor'] ?? null;
$metodo = $dados['metodo'] ?? '';
$cartaoId = filter_var($dados['cartao_id'] ?? null, FILTER_VALIDATE_INT);
if (!$ongId || $ongId < 1 || !is_numeric($valorTexto)) {
    responderDoacao(['sucesso' => false, 'erro' => 'Selecione uma instituição e informe um valor válido.'], 400);
}
$valor = (float) $valorTexto;
if (!is_finite($valor) || $valor < 1 || $valor > 1000000) {
    responderDoacao(['sucesso' => false, 'erro' => 'O valor deve ser entre R$ 1,00 e R$ 1.000.000,00.'], 400);
}
if (!in_array($metodo, ['pix', 'credito', 'debito'], true)) {
    responderDoacao(['sucesso' => false, 'erro' => 'Selecione uma forma de pagamento válida.'], 400);
}

$pdo = getConexao();
$stmt = $pdo->prepare('SELECT id FROM ongs WHERE id = :id AND status = "parceira"');
$stmt->execute(['id' => $ongId]);
if (!$stmt->fetchColumn()) {
    responderDoacao(['sucesso' => false, 'erro' => 'A instituição selecionada não está disponível.'], 400);
}

$cartaoSalvoId = null;
$nomeMetodo = 'PIX';
if ($metodo !== 'pix') {
    if (!$cartaoId || $cartaoId < 1) {
        responderDoacao(['sucesso' => false, 'erro' => 'Escolha um cartão salvo compatível.'], 400);
    }
    $stmt = $pdo->prepare(
        'SELECT id FROM cartoes
         WHERE id = :id AND usuario_id = :usuario_id
           AND (tipo_uso = :tipo OR tipo_uso = "ambos")'
    );
    $stmt->execute([
        'id' => $cartaoId,
        'usuario_id' => $_SESSION['usuario_id'],
        'tipo' => $metodo,
    ]);
    $cartaoSalvoId = $stmt->fetchColumn();
    if (!$cartaoSalvoId) {
        responderDoacao(['sucesso' => false, 'erro' => 'O cartão escolhido não pertence à sua conta ou não aceita essa modalidade.'], 400);
    }
    $nomeMetodo = $metodo === 'credito' ? 'Cartão de Crédito' : 'Cartão de Débito';
}

$stmt = $pdo->prepare(
    'INSERT INTO doacoes (usuario_id, ong_id, cartao_id, metodo_pagamento, valor, pontos_gerados, status)
     VALUES (:usuario_id, :ong_id, :cartao_id, :metodo, :valor, 0, "pendente")'
);
$stmt->execute([
    'usuario_id' => $_SESSION['usuario_id'],
    'ong_id' => $ongId,
    'cartao_id' => $cartaoSalvoId,
    'metodo' => $nomeMetodo,
    'valor' => round($valor, 2),
]);

responderDoacao([
    'sucesso' => true,
    'doacao_id' => (int) $pdo->lastInsertId(),
    'metodo' => $nomeMetodo,
]);
