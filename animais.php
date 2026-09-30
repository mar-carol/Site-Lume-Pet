<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exigirAdmin();
$pdo = getConexao();
$erros = [];
$editando = null;

if (($_GET['acao'] ?? '') === 'excluir' && isset($_GET['id'])) {
    $pdo->prepare('DELETE FROM animais WHERE id = :id')->execute(['id' => (int) $_GET['id']]);
    redirecionar(BASE_URL . '/animais.php');
}

if (($_GET['acao'] ?? '') === 'marcar_adotado' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('UPDATE animais SET status = "adotado", data_adocao = NOW(),
                            adotante_usuario_id = :usuario WHERE id = :id');
    $stmt->execute(['usuario' => !empty($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : null, 'id' => (int) $_GET['id']]);
    redirecionar(BASE_URL . '/animais.php');
}

if (($_GET['acao'] ?? '') === 'marcar_disponivel' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('UPDATE animais SET status = "disponivel", data_adocao = NULL,
                            adotante_usuario_id = NULL WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['id']]);
    redirecionar(BASE_URL . '/animais.php');
}

if (($_GET['acao'] ?? '') === 'editar' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM animais WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['id']]);
    $editando = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, tente novamente.';
    }

    $id       = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $ongId    = (int) ($_POST['ong_id'] ?? 0);
    $nome     = trim($_POST['nome'] ?? '');
    $especie  = trim($_POST['especie'] ?? '');
    $raca     = trim($_POST['raca'] ?? '');
    $idade    = trim($_POST['idade_aproximada'] ?? '');
    $porte    = $_POST['porte'] ?? null;
    $sexo     = $_POST['sexo'] ?? null;
    $descricao = trim($_POST['descricao'] ?? '');

    if ($ongId <= 0) $erros[] = 'Selecione a ONG responsável.';
    if ($nome === '') $erros[] = 'Informe o nome do animal.';
    if ($especie === '') $erros[] = 'Informe a espécie.';

    $foto = null;
    try {
        $foto = salvarImagem($_FILES['foto'] ?? [], __DIR__ . '/uploads/animais');
    } catch (RuntimeException $e) {
        $erros[] = $e->getMessage();
    }

    if (!$erros) {
        if ($id) {
            $sql = 'UPDATE animais SET ong_id=:ong_id, nome=:nome, especie=:especie, raca=:raca,
                    idade_aproximada=:idade, porte=:porte, sexo=:sexo, descricao=:descricao' .
                    ($foto ? ', foto=:foto' : '') . ' WHERE id=:id';
            $params = [
                'ong_id' => $ongId, 'nome' => $nome, 'especie' => $especie, 'raca' => $raca,
                'idade' => $idade, 'porte' => $porte ?: null, 'sexo' => $sexo ?: null,
                'descricao' => $descricao, 'id' => $id,
            ];
            if ($foto) $params['foto'] = $foto;
            $pdo->prepare($sql)->execute($params);
        } else {
            $stmt = $pdo->prepare('INSERT INTO animais (ong_id, nome, especie, raca, idade_aproximada, porte, sexo, descricao, foto, status)
                                    VALUES (:ong_id, :nome, :especie, :raca, :idade, :porte, :sexo, :descricao, :foto, "disponivel")');
            $stmt->execute([
                'ong_id' => $ongId, 'nome' => $nome, 'especie' => $especie, 'raca' => $raca,
                'idade' => $idade, 'porte' => $porte ?: null, 'sexo' => $sexo ?: null,
                'descricao' => $descricao, 'foto' => $foto,
            ]);
        }
        redirecionar(BASE_URL . '/animais.php');
    }
}

$ongs = $pdo->query('SELECT id, nome FROM ongs ORDER BY nome')->fetchAll();
$usuarios = $pdo->query('SELECT id, nome FROM usuarios WHERE tipo="cliente" ORDER BY nome')->fetchAll();

$animais = $pdo->query('SELECT a.*, o.nome AS ong_nome, u.nome AS adotante_nome
                         FROM animais a
                         JOIN ongs o ON o.id = a.ong_id
                         LEFT JOIN usuarios u ON u.id = a.adotante_usuario_id
                         ORDER BY a.criado_em DESC')->fetchAll();

$tituloPagina = 'Animais para adoção';
$paginaAtual  = 'animais';
require __DIR__ . '/includes/admin_header.php';
?>

<?php foreach ($erros as $erro): ?>
    <div class="lp-alert lp-alert-erro"><?= limpar($erro) ?></div>
<?php endforeach; ?>

<div class="lp-panel">
    <h2 style="margin-top:0;"><?= $editando ? 'Editar animal' : 'Novo animal' ?></h2>
    <form method="post" action="<?= BASE_URL ?>/animais.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <?php if ($editando): ?><input type="hidden" name="id" value="<?= $editando['id'] ?>"><?php endif; ?>

        <label>ONG responsável</label>
        <select name="ong_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($ongs as $o): ?>
                <option value="<?= $o['id'] ?>" <?= ($editando['ong_id'] ?? null) == $o['id'] ? 'selected' : '' ?>><?= limpar($o['nome']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Nome do animal</label>
        <input type="text" name="nome" value="<?= limpar($editando['nome'] ?? '') ?>" required>

        <label>Espécie</label>
        <input type="text" name="especie" value="<?= limpar($editando['especie'] ?? '') ?>" placeholder="Cachorro, Gato..." required>

        <label>Raça</label>
        <input type="text" name="raca" value="<?= limpar($editando['raca'] ?? '') ?>">

        <label>Idade aproximada</label>
        <input type="text" name="idade_aproximada" value="<?= limpar($editando['idade_aproximada'] ?? '') ?>" placeholder="Ex: 2 anos">

        <label>Porte</label>
        <select name="porte">
            <option value="">Selecione</option>
            <option value="pequeno" <?= ($editando['porte'] ?? '') === 'pequeno' ? 'selected' : '' ?>>Pequeno</option>
            <option value="medio" <?= ($editando['porte'] ?? '') === 'medio' ? 'selected' : '' ?>>Médio</option>
            <option value="grande" <?= ($editando['porte'] ?? '') === 'grande' ? 'selected' : '' ?>>Grande</option>
        </select>

        <label>Sexo</label>
        <select name="sexo">
            <option value="">Selecione</option>
            <option value="macho" <?= ($editando['sexo'] ?? '') === 'macho' ? 'selected' : '' ?>>Macho</option>
            <option value="femea" <?= ($editando['sexo'] ?? '') === 'femea' ? 'selected' : '' ?>>Fêmea</option>
        </select>

        <label>Descrição</label>
        <textarea name="descricao"><?= limpar($editando['descricao'] ?? '') ?></textarea>

        <label>Foto</label>
        <input type="file" name="foto" accept="image/*">
        <?php if (!empty($editando['foto'])): ?>
            <img src="<?= BASE_URL ?>/uploads/animais/<?= limpar($editando['foto']) ?>" width="80" style="margin-top:8px;">
        <?php endif; ?>

        <button type="submit"><?= $editando ? 'Atualizar' : 'Cadastrar' ?></button>
        <?php if ($editando): ?><a href="<?= BASE_URL ?>/animais.php" class="lp-cancel">Cancelar</a><?php endif; ?>
    </form>
</div>

<h2>Animais cadastrados</h2>
<table>
<tr><th>ID</th><th>Nome</th><th>Espécie</th><th>ONG</th><th>Status</th><th>Adotante</th><th>Ações</th></tr>
<?php foreach ($animais as $a): ?>
<tr>
    <td>#<?= $a['id'] ?></td>
    <td><?= limpar($a['nome']) ?></td>
    <td><?= limpar($a['especie']) ?></td>
    <td><?= limpar($a['ong_nome']) ?></td>
    <td><span class="lp-badge lp-badge-<?= limpar($a['status']) ?>"><?= limpar($a['status']) ?></span></td>
    <td><?= limpar($a['adotante_nome'] ?? '—') ?></td>
    <td>
        <a href="?acao=editar&id=<?= $a['id'] ?>">Editar</a> ·
        <?php if ($a['status'] !== 'adotado'): ?>
            <a href="?acao=marcar_adotado&id=<?= $a['id'] ?>" onclick="return confirm('Marcar como adotado?')">Marcar adotado</a> ·
        <?php else: ?>
            <a href="?acao=marcar_disponivel&id=<?= $a['id'] ?>" onclick="return confirm('Voltar para disponível?')">Reverter adoção</a> ·
        <?php endif; ?>
        <a href="?acao=excluir&id=<?= $a['id'] ?>" onclick="return confirm('Excluir este animal?')">Excluir</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>