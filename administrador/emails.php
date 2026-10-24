<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exigirAdmin();
$pdo = getConexao();
$totalClientes = (int) $pdo->query(
    'SELECT COUNT(*) FROM usuarios WHERE tipo = "cliente" AND status = "ativo"'
)->fetchColumn();
$totalVerificados = (int) $pdo->query(
    'SELECT COUNT(*) FROM usuarios
     WHERE tipo = "cliente" AND status = "ativo" AND email_verificado_em IS NOT NULL'
)->fetchColumn();
$totalPendentes = $totalClientes - $totalVerificados;
$usuarios = $pdo->query(
    'SELECT nome, email, email_verificado_em, criado_em
     FROM usuarios WHERE tipo = "cliente" ORDER BY criado_em DESC'
)->fetchAll();

$tituloPagina = 'Verificação de e-mails';
$paginaAtual = 'emails';
require __DIR__ . '/../includes/admin_header.php';
?>

<section class="lp-panel">
    <h2 style="margin-top:0;">Situação das contas</h2>
    <p>A confirmação é feita pelo próprio cliente com o código enviado ao e-mail. Por segurança, os códigos não são exibidos neste painel.</p>
    <p><strong>Clientes:</strong> <?= $totalClientes ?>
        &nbsp; <strong>E-mails confirmados:</strong> <?= $totalVerificados ?>
        &nbsp; <strong>Aguardando confirmação:</strong> <?= $totalPendentes ?></p>
</section>

<section class="lp-panel">
    <h2 style="margin-top:0;">Contas de clientes</h2>
    <?php if (!$usuarios): ?>
        <p>Nenhum cliente cadastrado.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>E-mail</th>
                    <th>Cadastro</th>
                    <th>Verificação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= limpar($usuario['nome']) ?></td>
                        <td><?= limpar($usuario['email']) ?></td>
                        <td><?= (new DateTime($usuario['criado_em']))->format('d/m/Y H:i') ?></td>
                        <td>
                            <?php if ($usuario['email_verificado_em'] !== null): ?>
                                <span class="lp-badge lp-badge-ativo">Confirmado</span><br>
                                <small><?= (new DateTime($usuario['email_verificado_em']))->format('d/m/Y H:i') ?></small>
                            <?php else: ?>
                                <span class="lp-badge lp-badge-pendente">Pendente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
