<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirLogin();
$usuarioId = $_SESSION['usuario_id'];
$pdo = getConexao();
$erros = [];
$sucesso = false;
$errosEndereco = [];
$sucessoEndereco = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !in_array($_POST['acao'] ?? '', ['salvar_endereco', 'definir_padrao', 'excluir_endereco'], true)) {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, tente novamente.';
    }

    $nome     = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade   = trim($_POST['cidade'] ?? '');
    $estado   = trim($_POST['estado'] ?? '');
    $cep      = trim($_POST['cep'] ?? '');

    if ($nome === '') $erros[] = 'Informe seu nome.';
    if ($telefone === '') $erros[] = 'Informe seu telefone.';
    if ($endereco === '') $erros[] = 'Informe seu endereço.';

    if (!$erros) {
        $stmt = $pdo->prepare('UPDATE usuarios SET nome=:nome, telefone=:telefone, endereco=:endereco,
                                cidade=:cidade, estado=:estado, cep=:cep WHERE id=:id');
        $stmt->execute([
            'nome' => $nome, 'telefone' => $telefone, 'endereco' => $endereco,
            'cidade' => $cidade, 'estado' => $estado, 'cep' => $cep, 'id' => $usuarioId,
        ]);
        $_SESSION['usuario_nome'] = $nome;
        $sucesso = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['acao'] ?? '', ['salvar_endereco', 'definir_padrao', 'excluir_endereco'], true)) {
    $acaoEndereco = $_POST['acao'];
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $errosEndereco[] = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif ($acaoEndereco === 'salvar_endereco') {
        $apelido = trim((string) ($_POST['apelido'] ?? ''));
        $cepNovo = preg_replace('/\D/', '', (string) ($_POST['cep_novo'] ?? ''));
        $logradouroNovo = trim((string) ($_POST['logradouro_novo'] ?? ''));
        $numeroNovo = trim((string) ($_POST['numero_novo'] ?? ''));
        $complementoNovo = trim((string) ($_POST['complemento_novo'] ?? ''));
        $bairroNovo = trim((string) ($_POST['bairro_novo'] ?? ''));
        $cidadeNovo = trim((string) ($_POST['cidade_nova'] ?? ''));
        $estadoNovo = strtoupper(trim((string) ($_POST['estado_novo'] ?? '')));
        $padraoNovo = isset($_POST['padrao_novo']);

        if ($apelido === '' || mb_strlen($apelido, 'UTF-8') > 60) $errosEndereco[] = 'Informe um nome curto para identificar o endereço.';
        if (strlen($cepNovo) !== 8) $errosEndereco[] = 'Informe um CEP válido com 8 números.';
        if ($logradouroNovo === '' || $numeroNovo === '' || $cidadeNovo === '' || !preg_match('/^[A-Z]{2}$/', $estadoNovo)) {
            $errosEndereco[] = 'Preencha rua, número, cidade e estado do endereço.';
        }

        if (!$errosEndereco) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM enderecos WHERE usuario_id = :usuario_id');
            $stmt->execute(['usuario_id' => $usuarioId]);
            $padraoNovo = $padraoNovo || (int) $stmt->fetchColumn() === 0;

            $pdo->beginTransaction();
            try {
                if ($padraoNovo) {
                    $stmt = $pdo->prepare('UPDATE enderecos SET padrao = 0 WHERE usuario_id = :usuario_id');
                    $stmt->execute(['usuario_id' => $usuarioId]);
                }
                $stmt = $pdo->prepare(
                    'INSERT INTO enderecos
                        (usuario_id, apelido, cep, logradouro, numero, complemento, bairro, cidade, estado, padrao)
                     VALUES
                        (:usuario_id, :apelido, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado, :padrao)'
                );
                $stmt->execute([
                    'usuario_id' => $usuarioId,
                    'apelido' => $apelido,
                    'cep' => $cepNovo,
                    'logradouro' => $logradouroNovo,
                    'numero' => $numeroNovo,
                    'complemento' => $complementoNovo ?: null,
                    'bairro' => $bairroNovo ?: null,
                    'cidade' => $cidadeNovo,
                    'estado' => $estadoNovo,
                    'padrao' => $padraoNovo ? 1 : 0,
                ]);
                if ($padraoNovo) {
                    $stmt = $pdo->prepare(
                        'UPDATE usuarios
                         SET endereco = :endereco, cidade = :cidade, estado = :estado, cep = :cep
                         WHERE id = :usuario_id'
                    );
                    $stmt->execute([
                        'endereco' => $logradouroNovo . ', ' . $numeroNovo,
                        'cidade' => $cidadeNovo,
                        'estado' => $estadoNovo,
                        'cep' => substr($cepNovo, 0, 5) . '-' . substr($cepNovo, 5),
                        'usuario_id' => $usuarioId,
                    ]);
                }
                $pdo->commit();
                $sucessoEndereco = 'Endereço salvo com sucesso.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Falha ao salvar endereço do cliente #' . $usuarioId . ': ' . $e->getMessage());
                $errosEndereco[] = 'Não foi possível salvar o endereço. Tente novamente.';
            }
        }
    } else {
        $enderecoId = filter_var($_POST['endereco_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$enderecoId || $enderecoId < 1) {
            $errosEndereco[] = 'Endereço inválido.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM enderecos WHERE id = :id AND usuario_id = :usuario_id');
            $stmt->execute(['id' => $enderecoId, 'usuario_id' => $usuarioId]);
            $enderecoAcao = $stmt->fetch();
            if (!$enderecoAcao) {
                $errosEndereco[] = 'Este endereço não pertence à sua conta.';
            } elseif ($acaoEndereco === 'definir_padrao') {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('UPDATE enderecos SET padrao = 0 WHERE usuario_id = :usuario_id');
                    $stmt->execute(['usuario_id' => $usuarioId]);
                    $stmt = $pdo->prepare('UPDATE enderecos SET padrao = 1 WHERE id = :id AND usuario_id = :usuario_id');
                    $stmt->execute(['id' => $enderecoId, 'usuario_id' => $usuarioId]);
                    $stmt = $pdo->prepare(
                        'UPDATE usuarios SET endereco = :endereco, cidade = :cidade, estado = :estado, cep = :cep
                         WHERE id = :usuario_id'
                    );
                    $stmt->execute([
                        'endereco' => $enderecoAcao['logradouro'] . ', ' . $enderecoAcao['numero'],
                        'cidade' => $enderecoAcao['cidade'],
                        'estado' => $enderecoAcao['estado'],
                        'cep' => substr($enderecoAcao['cep'], 0, 5) . '-' . substr($enderecoAcao['cep'], 5),
                        'usuario_id' => $usuarioId,
                    ]);
                    $pdo->commit();
                    $sucessoEndereco = 'Endereço padrão atualizado.';
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log('Falha ao definir endereço padrão do cliente #' . $usuarioId . ': ' . $e->getMessage());
                    $errosEndereco[] = 'Não foi possível atualizar o endereço padrão.';
                }
            } else {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('DELETE FROM enderecos WHERE id = :id AND usuario_id = :usuario_id');
                    $stmt->execute(['id' => $enderecoId, 'usuario_id' => $usuarioId]);
                    if ((int) $enderecoAcao['padrao'] === 1) {
                        $stmt = $pdo->prepare('SELECT id FROM enderecos WHERE usuario_id = :usuario_id ORDER BY criado_em DESC LIMIT 1');
                        $stmt->execute(['usuario_id' => $usuarioId]);
                        $novoPadraoId = $stmt->fetchColumn();
                        if ($novoPadraoId) {
                            $stmt = $pdo->prepare('UPDATE enderecos SET padrao = 1 WHERE id = :id');
                            $stmt->execute(['id' => $novoPadraoId]);
                            $stmt = $pdo->prepare('SELECT * FROM enderecos WHERE id = :id');
                            $stmt->execute(['id' => $novoPadraoId]);
                            $enderecoNovoPadrao = $stmt->fetch();
                            $stmt = $pdo->prepare(
                                'UPDATE usuarios SET endereco = :endereco, cidade = :cidade, estado = :estado, cep = :cep
                                 WHERE id = :usuario_id'
                            );
                            $stmt->execute([
                                'endereco' => $enderecoNovoPadrao['logradouro'] . ', ' . $enderecoNovoPadrao['numero'],
                                'cidade' => $enderecoNovoPadrao['cidade'],
                                'estado' => $enderecoNovoPadrao['estado'],
                                'cep' => substr($enderecoNovoPadrao['cep'], 0, 5) . '-' . substr($enderecoNovoPadrao['cep'], 5),
                                'usuario_id' => $usuarioId,
                            ]);
                        } else {
                            $stmt = $pdo->prepare(
                                'UPDATE usuarios SET endereco = "", cidade = NULL, estado = NULL, cep = NULL
                                 WHERE id = :usuario_id'
                            );
                            $stmt->execute(['usuario_id' => $usuarioId]);
                        }
                    }
                    $pdo->commit();
                    $sucessoEndereco = 'Endereço removido.';
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log('Falha ao excluir endereço do cliente #' . $usuarioId . ': ' . $e->getMessage());
                    $errosEndereco[] = 'Não foi possível remover o endereço.';
                }
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
$stmt->execute(['id' => $usuarioId]);
$usuario = $stmt->fetch();
$stmt = $pdo->prepare('SELECT * FROM enderecos WHERE usuario_id = :usuario_id ORDER BY padrao DESC, criado_em DESC');
$stmt->execute(['usuario_id' => $usuarioId]);
$enderecos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu perfil - Lume Pet</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css?v=3">
</head>
<body>
<?php require __DIR__ . '/../includes/user_home_logo.php'; ?>
<h1>Olá, <?= limpar($usuario['nome']) ?></h1>

<nav>
    <a href="<?= BASE_URL ?>/usuario/dashboard.php">Minha conta</a> |
    <a href="<?= BASE_URL ?>/usuario/perfil.php">Meu perfil</a> |
    <a href="<?= BASE_URL ?>/usuario/compras.php">Minhas compras</a> |
    <a href="<?= BASE_URL ?>/usuario/doacoes.php">Minhas doações</a> |
    <a href="<?= BASE_URL ?>/usuario/pontos.php">Meus pontos (<?= (int) $usuario['pontos'] ?>)</a> |
    <a href="<?= BASE_URL ?>/usuario/cartoes.php">Meus cartões</a> |
    <a href="<?= BASE_URL ?>/includes/logout.php">Sair</a>
</nav>

<?php if ($sucesso): ?><p style="color:green;">Dados atualizados com sucesso!</p><?php endif; ?>
<?php foreach ($erros as $erro): ?><p style="color:red;"><?= limpar($erro) ?></p><?php endforeach; ?>
<?php if ($sucessoEndereco !== ''): ?><p style="color:green;" role="status"><?= limpar($sucessoEndereco) ?></p><?php endif; ?>
<?php foreach ($errosEndereco as $erro): ?><p style="color:red;" role="alert"><?= limpar($erro) ?></p><?php endforeach; ?>

<form method="post" action="<?= BASE_URL ?>/usuario/perfil.php">
    <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">

    <label>Nome</label>
    <input type="text" name="nome" value="<?= limpar($usuario['nome']) ?>" required>

    <label>E-mail</label>
    <input type="email" value="<?= limpar($usuario['email']) ?>" disabled>

    <label>Telefone</label>
    <input type="text" name="telefone" value="<?= limpar($usuario['telefone']) ?>" required>

    <label for="perfil-endereco">Endereço</label>
    <input type="text" id="perfil-endereco" name="endereco" value="<?= limpar($usuario['endereco']) ?>" required>

    <label for="perfil-cidade">Cidade</label>
    <input type="text" id="perfil-cidade" name="cidade" value="<?= limpar($usuario['cidade'] ?? '') ?>">

    <label for="perfil-estado">Estado</label>
    <input type="text" id="perfil-estado" name="estado" maxlength="2" value="<?= limpar($usuario['estado'] ?? '') ?>">

    <label for="perfil-cep">CEP</label>
    <div class="perfil-cep-controle">
        <input type="text" id="perfil-cep" name="cep" value="<?= limpar($usuario['cep'] ?? '') ?>"
            maxlength="9" inputmode="numeric" autocomplete="postal-code" placeholder="00000-000">
        <button type="button" id="buscar-cep">Buscar endereço</button>
    </div>
    <p class="perfil-cep-status" id="perfil-cep-status" role="status" aria-live="polite"></p>

    <button type="submit">Salvar</button>
</form>

<section class="perfil-enderecos">
    <h2>Endereços de entrega</h2>
    <p>Salve endereços diferentes e escolha qual será usado como padrão nas próximas compras.</p>
    <?php if ($enderecos): ?>
        <ul>
            <?php foreach ($enderecos as $endereco): ?>
                <li>
                    <strong><?= limpar($endereco['apelido']) ?></strong>
                    <?= $endereco['padrao'] ? ' — Padrão' : '' ?><br>
                    <?= limpar($endereco['logradouro']) ?>, <?= limpar($endereco['numero']) ?>
                    <?= $endereco['complemento'] ? ' · ' . limpar($endereco['complemento']) : '' ?><br>
                    <?= limpar($endereco['cidade']) ?>/<?= limpar($endereco['estado']) ?> ·
                    <?= limpar(substr($endereco['cep'], 0, 5) . '-' . substr($endereco['cep'], 5)) ?>
                    <div>
                        <?php if (!$endereco['padrao']): ?>
                            <form method="post" action="<?= BASE_URL ?>/usuario/perfil.php">
                                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                                <input type="hidden" name="acao" value="definir_padrao">
                                <input type="hidden" name="endereco_id" value="<?= (int) $endereco['id'] ?>">
                                <button type="submit">Definir como padrão</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= BASE_URL ?>/usuario/perfil.php">
                            <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                            <input type="hidden" name="acao" value="excluir_endereco">
                            <input type="hidden" name="endereco_id" value="<?= (int) $endereco['id'] ?>">
                            <button type="submit" onclick="return confirm('Remover este endereço salvo?')">Remover</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nenhum endereço de entrega salvo.</p>
    <?php endif; ?>

    <h3>Adicionar endereço</h3>
    <form method="post" action="<?= BASE_URL ?>/usuario/perfil.php">
        <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
        <input type="hidden" name="acao" value="salvar_endereco">
        <label for="endereco-apelido">Nome do endereço</label>
        <input type="text" id="endereco-apelido" name="apelido" placeholder="Casa, trabalho..." required maxlength="60">
        <label for="endereco-cep-novo">CEP</label>
        <div class="perfil-cep-controle">
            <input type="text" id="endereco-cep-novo" name="cep_novo" maxlength="9" inputmode="numeric" placeholder="00000-000" required>
            <button type="button" id="buscar-cep-novo">Buscar CEP</button>
        </div>
        <p id="endereco-cep-status" role="status" aria-live="polite"></p>
        <label for="endereco-logradouro-novo">Rua/avenida</label>
        <input type="text" id="endereco-logradouro-novo" name="logradouro_novo" required>
        <label for="endereco-numero-novo">Número</label>
        <input type="text" id="endereco-numero-novo" name="numero_novo" required maxlength="30">
        <label for="endereco-complemento-novo">Complemento</label>
        <input type="text" id="endereco-complemento-novo" name="complemento_novo" maxlength="100">
        <label for="endereco-bairro-novo">Bairro</label>
        <input type="text" id="endereco-bairro-novo" name="bairro_novo" maxlength="80">
        <label for="endereco-cidade-nova">Cidade</label>
        <input type="text" id="endereco-cidade-nova" name="cidade_nova" required>
        <label for="endereco-estado-novo">Estado</label>
        <input type="text" id="endereco-estado-novo" name="estado_novo" maxlength="2" required>
        <label><input type="checkbox" name="padrao_novo" value="1"> Definir como endereço padrão</label>
        <button type="submit">Salvar endereço</button>
    </form>
</section>
<script src="<?= BASE_URL ?>/js/cep-perfil.js?v=1"></script>
<script src="<?= BASE_URL ?>/js/enderecos.js?v=1"></script>
</body>
</html>