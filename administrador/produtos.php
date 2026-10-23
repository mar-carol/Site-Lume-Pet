<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirAdmin();
$pdo = getConexao();
$erros = [];
$editando = null;

// ---- REMOVER DA LOJA ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Sessão expirada. Atualize a página e tente novamente.');
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        http_response_code(400);
        exit('Produto inválido.');
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        $produto = $stmt->fetch();

        if ($produto && $produto['status'] !== 'inativo') {
            $pdo->prepare("UPDATE produtos SET status = 'inativo' WHERE id = :id")
                ->execute(['id' => $id]);
            $depois = array_merge($produto, ['status' => 'inativo']);
            registrarHistoricoProduto(
                $id,
                (int) $_SESSION['usuario_id'],
                'editado',
                'Produto removido da loja; histórico de pedidos preservado',
                $produto,
                $depois
            );
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    redirecionar(BASE_URL . '/administrador/produtos.php');
}

// ---- CARREGAR PARA EDIÇÃO ----
if (($_GET['acao'] ?? '') === 'editar' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['id']]);
    $editando = $stmt->fetch();
}

// ---- HISTÓRICO DE UM PRODUTO ----
$historicoProduto = null;
if (($_GET['acao'] ?? '') === 'historico' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT ph.*, u.nome AS admin_nome
                            FROM produto_historico ph
                            LEFT JOIN usuarios u ON u.id = ph.usuario_id
                            WHERE produto_id = :id ORDER BY criado_em DESC');
    $stmt->execute(['id' => (int) $_GET['id']]);
    $historicoProduto = $stmt->fetchAll();
}

// ---- SALVAR (CRIAR OU ATUALIZAR) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, tente novamente.';
    }

    $id         = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $nome       = trim($_POST['nome'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    $categoria  = trim($_POST['categoria'] ?? '');
    $preco      = (float) str_replace(',', '.', $_POST['preco'] ?? '0');
    $estoque    = (int) ($_POST['estoque'] ?? 0);
    $status     = ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

    if ($nome === '') $erros[] = 'Informe o nome do produto.';
    if ($preco <= 0) $erros[] = 'Informe um preço válido.';

    $nomeImagem = null;
    try {
        $nomeImagem = salvarImagem($_FILES['imagem'] ?? [], dirname(__DIR__) . '/uploads/produtos');
    } catch (RuntimeException $e) {
        $erros[] = $e->getMessage();
    }

    if (!$erros) {
        if ($id) {
            // Atualizar
            $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $antes = $stmt->fetch();

            $sql = 'UPDATE produtos SET nome=:nome, descricao=:descricao, categoria=:categoria,
                    preco=:preco, estoque=:estoque, status=:status' .
                    ($nomeImagem ? ', imagem=:imagem' : '') . ' WHERE id=:id';
            $params = [
                'nome' => $nome, 'descricao' => $descricao, 'categoria' => $categoria,
                'preco' => $preco, 'estoque' => $estoque, 'status' => $status, 'id' => $id,
            ];
            if ($nomeImagem) $params['imagem'] = $nomeImagem;

            $pdo->prepare($sql)->execute($params);

            $depois = array_merge($antes, $params);
            registrarHistoricoProduto($id, $_SESSION['usuario_id'], 'editado', 'Produto atualizado', $antes, $depois);
        } else {
            // Criar
            $stmt = $pdo->prepare('INSERT INTO produtos (nome, descricao, categoria, preco, estoque, imagem, status)
                                    VALUES (:nome, :descricao, :categoria, :preco, :estoque, :imagem, :status)');
            $stmt->execute([
                'nome' => $nome, 'descricao' => $descricao, 'categoria' => $categoria,
                'preco' => $preco, 'estoque' => $estoque, 'imagem' => $nomeImagem, 'status' => $status,
            ]);
            $novoId = (int) $pdo->lastInsertId();
            registrarHistoricoProduto($novoId, $_SESSION['usuario_id'], 'criado', 'Produto criado');
        }
        redirecionar(BASE_URL . '/administrador/produtos.php');
    }
}

$produtos = $pdo->query(
    'SELECT p.*,
            (SELECT COUNT(*) FROM avaliacoes a WHERE a.produto_id = p.id) AS total_avaliacoes
     FROM produtos p
     ORDER BY p.criado_em DESC'
)->fetchAll();

$categoriasProduto = [
    'higiene' => 'Higiene',
    'alimento' => 'Alimentos',
    'brinquedo' => 'Brinquedos',
    'conforto' => 'Conforto',
    'remedios' => 'Remédios',
];
$categoriaAtual = strtolower(trim($editando['categoria'] ?? ''));
$categoriaAtualNormalizada = strtr($categoriaAtual, [
    'á' => 'a', 'ã' => 'a', 'â' => 'a',
    'é' => 'e', 'ê' => 'e', 'í' => 'i',
    'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u',
]);

$tituloPagina = 'Produtos';
$paginaAtual  = 'produtos';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php foreach ($erros as $erro): ?>
    <div class="lp-alert lp-alert-erro"><?= limpar($erro) ?></div>
<?php endforeach; ?>

<?php if ($historicoProduto !== null): ?>
    <div class="lp-panel">
        <h2 style="margin-top:0;">Histórico do produto #<?= (int) $_GET['id'] ?></h2>
        <table>
        <tr><th>Data</th><th>Ação</th><th>Por</th><th>Detalhes</th></tr>
        <?php foreach ($historicoProduto as $h): ?>
            <tr>
                <td><?= (new DateTime($h['criado_em']))->format('d/m/Y H:i') ?></td>
                <td><?= limpar(ucfirst($h['acao'])) ?></td>
                <td><?= limpar($h['admin_nome'] ?? 'sistema') ?></td>
                <td><?= limpar($h['descricao'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </table>
        <p style="margin-top:14px;"><a href="<?= BASE_URL ?>/administrador/produtos.php">&larr; Voltar à lista</a></p>
    </div>
<?php endif; ?>

<div class="lp-panel">
    <h2 style="margin-top:0;"><?= $editando ? 'Editar produto' : 'Novo produto' ?></h2>
    <form method="post" action="<?= BASE_URL ?>/administrador/produtos.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <?php if ($editando): ?><input type="hidden" name="id" value="<?= $editando['id'] ?>"><?php endif; ?>

        <label>Nome</label>
        <input type="text" name="nome" value="<?= limpar($editando['nome'] ?? '') ?>" required>

        <label>Descrição</label>
        <textarea name="descricao"><?= limpar($editando['descricao'] ?? '') ?></textarea>

        <label>Categoria</label>
        <select name="categoria">
            <option value="">Selecione...</option>
            <?php foreach ($categoriasProduto as $valorCategoria => $rotuloCategoria): ?>
                <option value="<?= limpar($valorCategoria) ?>" <?= $categoriaAtualNormalizada === $valorCategoria ? 'selected' : '' ?>>
                    <?= limpar($rotuloCategoria) ?>
                </option>
            <?php endforeach; ?>
            <?php if ($categoriaAtual !== '' && !isset($categoriasProduto[$categoriaAtualNormalizada])): ?>
                <option value="<?= limpar($editando['categoria']) ?>" selected><?= limpar($editando['categoria']) ?></option>
            <?php endif; ?>
        </select>

        <label>Preço (R$)</label>
        <input type="number" step="0.01" name="preco" value="<?= $editando['preco'] ?? '' ?>" required>

        <label>Estoque</label>
        <input type="number" name="estoque" value="<?= $editando['estoque'] ?? 0 ?>">

        <label>Imagem</label>
        <input type="file" name="imagem" accept="image/*">
        <?php if (!empty($editando['imagem'])): ?>
            <?php
                $nomeImagemAtual = basename($editando['imagem']);
                $imagemNova = dirname(__DIR__) . '/uploads/produtos/' . $nomeImagemAtual;
                $imagemAntiga = __DIR__ . '/uploads/produtos/' . $nomeImagemAtual;
            ?>
            <?php if (is_file($imagemNova) || is_file($imagemAntiga)): ?>
                <?php
                    $urlImagemAtual = is_file($imagemNova)
                        ? BASE_URL . '/uploads/produtos/' . rawurlencode($nomeImagemAtual)
                        : BASE_URL . '/administrador/uploads/produtos/' . rawurlencode($nomeImagemAtual);
                ?>
                <img src="<?= htmlspecialchars($urlImagemAtual, ENT_QUOTES, 'UTF-8') ?>" width="80" style="margin-top:8px;" alt="Imagem atual do produto">
            <?php endif; ?>
        <?php endif; ?>

        <label>Status</label>
        <select name="status">
            <option value="ativo" <?= ($editando['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
            <option value="inativo" <?= ($editando['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
        </select>

        <button type="submit"><?= $editando ? 'Atualizar' : 'Cadastrar' ?></button>
        <?php if ($editando): ?><a href="<?= BASE_URL ?>/administrador/produtos.php" class="lp-cancel">Cancelar</a><?php endif; ?>
    </form>
</div>

<h2>Produtos cadastrados</h2>
<table>
<tr><th>ID</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Avaliações</th><th>Status</th><th>Ações</th></tr>
<?php foreach ($produtos as $p): ?>
<tr>
    <td>#<?= $p['id'] ?></td>
    <td><?= limpar($p['nome']) ?></td>
    <td><?= limpar($p['categoria']) ?></td>
    <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
    <td><?= (int) $p['estoque'] ?></td>
    <td><a href="<?= BASE_URL ?>/administrador/avaliacoes.php?produto_id=<?= (int) $p['id'] ?>"><?= (int) $p['total_avaliacoes'] ?> ver</a></td>
    <td><span class="lp-badge lp-badge-<?= limpar($p['status']) ?>"><?= limpar($p['status']) ?></span></td>
    <td>
        <a href="?acao=editar&id=<?= $p['id'] ?>">Editar</a> ·
        <a href="?acao=historico&id=<?= $p['id'] ?>">Histórico</a>
        <?php if ($p['status'] !== 'inativo'): ?> ·
            <form method="post" action="<?= BASE_URL ?>/administrador/produtos.php" style="display:inline;"
                  onsubmit="return confirm('Remover este produto da loja? O histórico de pedidos será preservado.')">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button type="submit">Remover</button>
            </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>