<?php
/**
 * Funções utilitárias compartilhadas pelo sistema.
 */

require_once __DIR__ . '/../config/database.php';

/** Gera/retorna token CSRF da sessão atual. */
function gerarTokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Valida o token CSRF enviado em um formulário. */
function validarTokenCsrf(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function limpar(string $valor): string
{
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Faz upload de uma imagem enviada via $_FILES e retorna o nome do arquivo salvo.
 * Retorna null se nenhum arquivo válido foi enviado.
 */
function salvarImagem(array $arquivo, string $pastaDestino): ?string
{
    if (empty($arquivo['name']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas, true)) {
        throw new RuntimeException('Formato de imagem inválido. Use JPG, PNG ou WEBP.');
    }

    if ($arquivo['size'] > 5 * 1024 * 1024) { // 5MB
        throw new RuntimeException('Imagem muito grande (máximo 5MB).');
    }

    $nomeArquivo = uniqid('img_', true) . '.' . $extensao;
    $caminhoCompleto = rtrim($pastaDestino, '/') . '/' . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        throw new RuntimeException('Falha ao salvar a imagem.');
    }

    return $nomeArquivo;
}

/**
 * Registra uma alteração no histórico do produto (auditoria).
 */
function registrarHistoricoProduto(int $produtoId, ?int $usuarioId, string $acao, ?string $descricao = null, ?array $antes = null, ?array $depois = null): void
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('INSERT INTO produto_historico (produto_id, usuario_id, acao, descricao, dados_antes, dados_depois)
                            VALUES (:produto_id, :usuario_id, :acao, :descricao, :antes, :depois)');
    $stmt->execute([
        'produto_id' => $produtoId,
        'usuario_id' => $usuarioId,
        'acao'       => $acao,
        'descricao'  => $descricao,
        'antes'      => $antes ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
        'depois'     => $depois ? json_encode($depois, JSON_UNESCAPED_UNICODE) : null,
    ]);
}

/**
 * Concede pontos de cashback a um usuário e registra no histórico.
 * Regra padrão: 1 ponto a cada R$ 1,00 doado (ajuste a regra de negócio como preferir).
 */
function concederPontos(int $usuarioId, int $pontos, string $tipo, ?int $referenciaId = null, ?string $descricao = null): void
{
    $pdo = getConexao();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('UPDATE usuarios SET pontos = pontos + :pontos WHERE id = :id');
        $stmt->execute(['pontos' => $pontos, 'id' => $usuarioId]);

        $stmt = $pdo->prepare('INSERT INTO pontos_historico (usuario_id, tipo, referencia_id, pontos, descricao)
                                VALUES (:usuario_id, :tipo, :referencia_id, :pontos, :descricao)');
        $stmt->execute([
            'usuario_id'    => $usuarioId,
            'tipo'          => $tipo,
            'referencia_id' => $referenciaId,
            'pontos'        => $pontos,
            'descricao'     => $descricao,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Calcula pontos de cashback a partir de um valor doado. Ex.: R$1 = 1 ponto. */
function calcularPontosPorDoacao(float $valor): int
{
    return (int) floor($valor); // 1 ponto por real doado
}