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

function validarSenhaCadastro(string $senha): ?string
{
    if (strlen($senha) < 8) return 'A senha deve ter no mínimo 8 caracteres.';
    if (preg_match('/\s/', $senha)) return 'A senha não pode conter espaços.';
    if (!preg_match('/^[A-Za-z0-9!@#$%&*?^_]+$/D', $senha)) {
        return 'A senha contém caracteres não permitidos. Use apenas letras, números e: !@#$%&*?^_.';
    }
    if (!preg_match('/[A-Z]/', $senha)) return 'A senha deve ter pelo menos 1 letra maiúscula.';
    if (!preg_match('/[a-z]/', $senha)) return 'A senha deve ter pelo menos 1 letra minúscula.';
    if (!preg_match('/\d/', $senha)) return 'A senha deve ter pelo menos 1 número.';
    if (!preg_match('/[!@#$%&*?^_]/', $senha)) {
        return 'A senha deve ter pelo menos 1 caractere especial (!@#$%&*?^_).';
    }

    for ($i = 0, $tamanho = strlen($senha); $i < $tamanho - 2; $i++) {
        $a = ord($senha[$i]);
        $b = ord($senha[$i + 1]);
        $c = ord($senha[$i + 2]);
        $saoDigitos = $a >= 48 && $a <= 57
            && $b >= 48 && $b <= 57
            && $c >= 48 && $c <= 57;

        if ($saoDigitos && (($b - $a === 1 && $c - $b === 1)
            || ($a - $b === 1 && $b - $c === 1)
            || ($a === $b && $b === $c))) {
            return 'A senha não pode ter números seguidos (ex: 123, 321, 111).';
        }
    }

    return null;
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
 * Cria a pasta de destino automaticamente se ela ainda não existir.
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

    $pastaDestino = rtrim($pastaDestino, '/\\');

    // (!) Cria a pasta de destino (e pastas-pai) se ainda não existirem,
    // em vez de deixar o move_uploaded_file falhar silenciosamente.
    if (!is_dir($pastaDestino)) {
        if (!mkdir($pastaDestino, 0755, true) && !is_dir($pastaDestino)) {
            throw new RuntimeException('Não foi possível criar a pasta de upload: ' . $pastaDestino);
        }
    }

    if (!is_writable($pastaDestino)) {
        throw new RuntimeException('Sem permissão de escrita na pasta: ' . $pastaDestino);
    }

    $nomeArquivo = uniqid('img_', true) . '.' . $extensao;
    $caminhoCompleto = $pastaDestino . DIRECTORY_SEPARATOR . $nomeArquivo;

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
    $iniciaTransacao = !$pdo->inTransaction();

    if ($iniciaTransacao) {
        $pdo->beginTransaction();
    }

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

        if ($iniciaTransacao) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($iniciaTransacao && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Calcula pontos de cashback a partir de um valor doado. Ex.: R$1 = 1 ponto. */
function calcularPontosPorDoacao(float $valor): int
{
    return (int) floor($valor); // 1 ponto por real doado
}