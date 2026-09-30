<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exigirAdmin();
$pdo = getConexao();
$erros = [];
$editando = null;

// ---- EXCLUIR ----
if (($_GET['acao'] ?? '') === 'excluir' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $produto = $stmt->fetch();

    if ($produto) {
        $pdo->prepare('DELETE FROM produtos WHERE id = :id')->execute(['id' => $id]);
        registrarHistoricoProduto($id, $_SESSION['usuario_id'], 'excluido', 'Produto excluído', $produto, null);
    }
    redirecionar(BASE_URL . '/produtos.php');
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
        // (!) Sem "/../" — produtos.php já está na raiz do projeto,
        // então a pasta de upload é só __DIR__ . '/uploads/produtos'.
        $nomeImagem = salvarImagem($_FILES['imagem'] ?? [], __DIR__ . '/uploads/produtos');
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
        redirecionar(BASE_URL . '/produtos.php');
    }
}

$produtos = $pdo->query('SELECT * FROM produtos ORDER BY criado_em DESC')->fetchAll();

$tituloPagina = 'Produtos';
$paginaAtual  = 'produtos';
require __DIR__ . '/includes/admin_header.php';
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
        <p style="margin-top:14px;"><a href="<?= BASE_URL ?>/produtos.php">&larr; Voltar à lista</a></p>
    </div>
<?php endif; ?>

<div class="lp-panel">
    <h2 style="margin-top:0;"><?= $editando ? 'Editar produto' : 'Novo produto' ?></h2>
    <form method="post" action="<?= BASE_URL ?>/produtos.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <?php if ($editando): ?><input type="hidden" name="id" value="<?= $editando['id'] ?>"><?php endif; ?>

        <label>Nome</label>
        <input type="text" name="nome" value="<?= limpar($editando['nome'] ?? '') ?>" required>

        <label>Descrição</label>
        <textarea name="descricao"><?= limpar($editando['descricao'] ?? '') ?></textarea>

        <label>Categoria</label>
        <input type="text" name="categoria" value="<?= limpar($editando['categoria'] ?? '') ?>">

        <label>Preço (R$)</label>
        <input type="number" step="0.01" name="preco" value="<?= $editando['preco'] ?? '' ?>" required>

        <label>Estoque</label>
        <input type="number" name="estoque" value="<?= $editando['estoque'] ?? 0 ?>">

        <label>Imagem</label>
        <input type="file" name="imagem" accept="image/*">
        <?php if (!empty($editando['imagem'])): ?>
            <img src="<?= BASE_URL ?>/uploads/produtos/<?= limpar($editando['imagem']) ?>" width="80" style="margin-top:8px;">
        <?php endif; ?>

        <label>Status</label>
        <select name="status">
            <option value="ativo" <?= ($editando['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
            <option value="inativo" <?= ($editando['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
        </select>

        <button type="submit"><?= $editando ? 'Atualizar' : 'Cadastrar' ?></button>
        <?php if ($editando): ?><a href="<?= BASE_URL ?>/produtos.php" class="lp-cancel">Cancelar</a><?php endif; ?>
    </form>
</div>

<h2>Produtos cadastrados</h2>
<table>
<tr><th>ID</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ações</th></tr>
<?php foreach ($produtos as $p): ?>
<tr>
    <td>#<?= $p['id'] ?></td>
    <td><?= limpar($p['nome']) ?></td>
    <td><?= limpar($p['categoria']) ?></td>
    <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
    <td><?= (int) $p['estoque'] ?></td>
    <td><span class="lp-badge lp-badge-<?= limpar($p['status']) ?>"><?= limpar($p['status']) ?></span></td>
    <td>
        <a href="?acao=editar&id=<?= $p['id'] ?>">Editar</a> ·
        <a href="?acao=historico&id=<?= $p['id'] ?>">Histórico</a> ·
        <a href="?acao=excluir&id=<?= $p['id'] ?>" onclick="return confirm('Excluir este produto?')">Excluir</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>