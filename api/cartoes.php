<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!usuarioEstaLogado() || usuarioEhAdmin()) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Entre na sua conta de cliente para consultar os cartões.']);
    exit;
}

$stmt = getConexao()->prepare(
    'SELECT id, bandeira, ultimos_digitos, nome_titular, tipo_uso
     FROM cartoes WHERE usuario_id = :usuario_id ORDER BY criado_em DESC'
);
$stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
echo json_encode([
    'sucesso' => true,
    'cartoes' => $stmt->fetchAll(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
