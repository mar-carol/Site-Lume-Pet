<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exigirAdmin();
$pdo = getConexao();
$erros = [];
$editando = null;

if (($_GET['acao'] ?? '') === 'excluir' && isset($_GET['id'])) {
    $pdo->prepare('DELETE FROM ongs WHERE id = :id')->execute(['id' => (int) $_GET['id']]);
    redirecionar(BASE_URL . '/ongs.php');
}

if (($_GET['acao'] ?? '') === 'editar' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM ongs WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['id']]);
    $editando = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, tente novamente.';
    }

    $id          = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $nome        = trim($_POST['nome'] ?? '');
    $cnpj        = trim($_POST['cnpj'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $telefone    = trim($_POST['telefone'] ?? '');
    $endereco    = trim($_POST['endereco'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '');
    $descricao   = trim($_POST['descricao'] ?? '');
    $status      = $_POST['status'] ?? 'pendente';

    if ($nome === '') $erros[] = 'Informe o nome da ONG.';
    if (!in_array($status, ['pendente', 'parceira', 'inativa'], true)) $erros[] = 'Status inválido.';

    $logo = null;
    try {
        // Logo da ONG tem pasta própria: uploads/ongs (não mistura com produtos/animais).
        $logo = salvarImagem($_FILES['logo'] ?? [], __DIR__ . '/uploads/ongs');
    } catch (RuntimeException $e) {
        $erros[] = $e->getMessage();
    }

    if (!$erros) {
        if ($id) {
            $sql = 'UPDATE ongs SET nome=:nome, cnpj=:cnpj, email=:email, telefone=:telefone,
                    endereco=:endereco, responsavel=:responsavel, descricao=:descricao, status=:status' .
                    ($logo ? ', logo=:logo' : '') . ' WHERE id=:id';
            $params = compact('nome', 'cnpj', 'email', 'telefone', 'endereco', 'responsavel', 'descricao', 'status');
            $params['id'] = $id;
            if ($logo) $params['logo'] = $logo;
            $pdo->prepare($sql)->execute($params);
        } else {
            $stmt = $pdo->prepare('INSERT INTO ongs (nome, cnpj, email, telefone, endereco, responsavel, descricao, logo, status)
                                    VALUES (:nome, :cnpj, :email, :telefone, :endereco, :responsavel, :descricao, :logo, :status)');
            $stmt->execute(array_merge(
                compact('nome', 'cnpj', 'email', 'telefone', 'endereco', 'responsavel', 'descricao', 'status'),
                ['logo' => $logo]
            ));
        }
        redirecionar(BASE_URL . '/ongs.php');
    }
}

$ongs = $pdo->query('SELECT * FROM ongs ORDER BY criado_em DESC')->fetchAll();

$tituloPagina = 'ONGs parceiras';
$paginaAtual  = 'ongs';
require __DIR__ . '/includes/admin_header.php';
?>

<?php foreach ($erros as $erro): ?>
    <div class="lp-alert lp-alert-erro"><?= limpar($erro) ?></div>
<?php endforeach; ?>

<div class="lp-panel">
    <h2 style="margin-top:0;"><?= $editando ? 'Editar ONG' : 'Nova ONG' ?></h2>
    <form method="post" action="<?= BASE_URL ?>/ongs.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <?php if ($editando): ?><input type="hidden" name="id" value="<?= $editando['id'] ?>"><?php endif; ?>

        <label>Nome</label>
        <input type="text" name="nome" value="<?= limpar($editando['nome'] ?? '') ?>" required>

        <label>CNPJ</label>
        <input type="text" name="cnpj" value="<?= limpar($editando['cnpj'] ?? '') ?>">

        <label>E-mail</label>
        <input type="email" name="email" value="<?= limpar($editando['email'] ?? '') ?>">

        <label>Telefone</label>
        <input type="text" name="telefone" value="<?= limpar($editando['telefone'] ?? '') ?>">

        <label>Endereço</label>
        <input type="text" name="endereco" value="<?= limpar($editando['endereco'] ?? '') ?>">

        <label>Responsável</label>
        <input type="text" name="responsavel" value="<?= limpar($editando['responsavel'] ?? '') ?>">

        <label>Descrição</label>
        <textarea name="descricao"><?= limpar($editando['descricao'] ?? '') ?></textarea>

        <label>Logo</label>
        <input type="file" name="logo" accept="image/*">
        <?php if (!empty($editando['logo'])): ?>
            <img src="<?= BASE_URL ?>/uploads/ongs/<?= limpar($editando['logo']) ?>" width="60" style="margin-top:8px;">
        <?php endif; ?>

        <label>Status da parceria</label>
        <select name="status">
            <option value="pendente" <?= ($editando['status'] ?? '') === 'pendente' ? 'selected' : '' ?>>Pendente</option>
            <option value="parceira" <?= ($editando['status'] ?? '') === 'parceira' ? 'selected' : '' ?>>Parceira</option>
            <option value="inativa" <?= ($editando['status'] ?? '') === 'inativa' ? 'selected' : '' ?>>Inativa</option>
        </select>

        <button type="submit"><?= $editando ? 'Atualizar' : 'Cadastrar' ?></button>
        <?php if ($editando): ?><a href="<?= BASE_URL ?>/ongs.php" class="lp-cancel">Cancelar</a><?php endif; ?>
    </form>
</div>

<h2>ONGs cadastradas</h2>
<table>
<tr><th>ID</th><th>Nome</th><th>Status</th><th>Contato</th><th>Ações</th></tr>
<?php foreach ($ongs as $o): ?>
<tr>
    <td>#<?= $o['id'] ?></td>
    <td><?= limpar($o['nome']) ?></td>
    <td><span class="lp-badge lp-badge-<?= limpar($o['status']) ?>"><?= limpar($o['status']) ?></span></td>
    <td><?= limpar($o['email']) ?> <?= $o['telefone'] ? '· ' . limpar($o['telefone']) : '' ?></td>
    <td>
        <a href="?acao=editar&id=<?= $o['id'] ?>">Editar</a> ·
        <a href="?acao=excluir&id=<?= $o['id'] ?>" onclick="return confirm('Excluir esta ONG?')">Excluir</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>