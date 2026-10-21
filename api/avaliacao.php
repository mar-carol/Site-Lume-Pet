<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function responderAvaliacao(array $dados, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $pdo = getConexao();
} catch (PDOException $e) {
    error_log('Falha ao conectar para avaliações: ' . $e->getMessage());
    responderAvaliacao(['sucesso' => false, 'erro' => 'Não foi possível carregar as avaliações no momento.'], 500);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $produtoId = filter_input(INPUT_GET, 'produto_id', FILTER_VALIDATE_INT);
    if (!$produtoId || $produtoId < 1) {
        responderAvaliacao(['sucesso' => false, 'erro' => 'Produto inválido.'], 400);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT a.id, a.nota, a.comentario, a.criado_em, u.nome AS autor
             FROM avaliacoes a
             JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.produto_id = :produto_id AND a.status = "aprovada"
             ORDER BY a.criado_em DESC'
        );
        $stmt->execute(['produto_id' => $produtoId]);
        responderAvaliacao(['sucesso' => true, 'avaliacoes' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        error_log('Falha ao carregar avaliações: ' . $e->getMessage());
        responderAvaliacao(['sucesso' => false, 'erro' => 'Não foi possível carregar as avaliações no momento.'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    responderAvaliacao(['sucesso' => false, 'erro' => 'Método não permitido.'], 405);
}

if (!usuarioEstaLogado()) {
    responderAvaliacao(['sucesso' => false, 'erro' => 'Entre na sua conta para avaliar.'], 401);
}

if (!validarTokenCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    responderAvaliacao(['sucesso' => false, 'erro' => 'Sessão expirada. Atualize a página e tente novamente.'], 403);
}

$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) {
    responderAvaliacao(['sucesso' => false, 'erro' => 'Dados inválidos.'], 400);
}

$produtoId = filter_var($dados['produto_id'] ?? null, FILTER_VALIDATE_INT);
$nota = filter_var($dados['nota'] ?? null, FILTER_VALIDATE_INT);
$comentarioInformado = $dados['comentario'] ?? null;
if (!is_string($comentarioInformado)) {
    responderAvaliacao(['sucesso' => false, 'erro' => 'Comentário inválido.'], 400);
}
$comentario = trim($comentarioInformado);

if (!$produtoId || $produtoId < 1 || !$nota || $nota < 1 || $nota > 5) {
    responderAvaliacao(['sucesso' => false, 'erro' => 'Produto ou nota inválidos.'], 400);
}

if (mb_strlen($comentario, 'UTF-8') < 10 || mb_strlen($comentario, 'UTF-8') > 1000) {
    responderAvaliacao(['sucesso' => false, 'erro' => 'O comentário deve ter entre 10 e 1.000 caracteres.'], 400);
}

$stmt = $pdo->prepare('SELECT id FROM produtos WHERE id = :id AND status = "ativo" LIMIT 1');
$stmt->execute(['id' => $produtoId]);
if (!$stmt->fetchColumn()) {
    responderAvaliacao(['sucesso' => false, 'erro' => 'Produto não encontrado.'], 404);
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO avaliacoes (produto_id, usuario_id, nota, comentario, status)
         VALUES (:produto_id, :usuario_id, :nota, :comentario, "pendente")'
    );
    $stmt->execute([
        'produto_id' => $produtoId,
        'usuario_id' => (int) $_SESSION['usuario_id'],
        'nota' => $nota,
        'comentario' => $comentario,
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        responderAvaliacao(['sucesso' => false, 'erro' => 'Você já enviou uma avaliação para este produto.'], 409);
    }
    error_log('Falha ao salvar avaliação: ' . $e->getMessage());
    responderAvaliacao(['sucesso' => false, 'erro' => 'Não foi possível salvar sua avaliação. Tente novamente.'], 500);
}

responderAvaliacao([
    'sucesso' => true,
    'mensagem' => 'Avaliação enviada para aprovação.',
], 201);
