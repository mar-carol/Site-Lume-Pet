<?php
/**
 * Cadastro de cartão do usuário.
 *
 * IMPORTANTE (segurança/PCI-DSS): mesmo com a nova aparência de "cartão
 * de banco", o número completo e o CVC digitados abaixo NUNCA são
 * enviados ao servidor PHP nem gravados no banco. O JavaScript desta
 * página só usa esses valores para desenhar o cartão animado e para
 * detectar a bandeira (Visa/Mastercard/Amex) — e então monta um payload
 * seguro (token + 4 últimos dígitos) que é o único enviado ao formulário.
 * Em produção, troque a função gerarTokenLocalDeDemonstracao() no script
 * abaixo pela chamada real ao SDK do seu gateway (Stripe.js, Pagar.me,
 * Mercado Pago etc.), que devolve o token de verdade.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
// (!) Removido o require_once de includes/header.php daqui de cima.
// Toda a lógica de sessão/POST/redirect precisa rodar ANTES de qualquer
// saída HTML — inclusive antes do header.php, que já imprime <!DOCTYPE>,
// <head>, etc. Se header.php roda aqui em cima, o PHP não consegue mais
// mandar header('Location: ...') depois (a resposta HTTP já começou),
// e o redirecionar() para de funcionar sem avisar nada.

exigirLogin();
$usuarioId = $_SESSION['usuario_id'];
$pdo = getConexao();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada, tente novamente.';
    }

    // Estes campos vêm do gateway de pagamento (JS) — nunca de um <input> de número de cartão bruto.
    $gateway      = trim($_POST['gateway'] ?? '');
    $gatewayToken = trim($_POST['gateway_token'] ?? '');
    $bandeira     = trim($_POST['bandeira'] ?? '');
    $ultimos4     = trim($_POST['ultimos_digitos'] ?? '');
    $nomeTitular  = trim($_POST['nome_titular'] ?? '');
    $mes          = (int) ($_POST['validade_mes'] ?? 0);
    $ano          = (int) ($_POST['validade_ano'] ?? 0);

    if ($gatewayToken === '') $erros[] = 'Não foi possível gerar o token do cartão. Confira os dados e tente novamente.';
    if (!preg_match('/^\d{4}$/', $ultimos4)) $erros[] = 'Número de cartão inválido.';
    if ($nomeTitular === '') $erros[] = 'Informe o nome do titular.';
    if ($mes < 1 || $mes > 12) $erros[] = 'Mês de validade inválido.';
    if ($ano < (int) date('Y')) $erros[] = 'Ano de validade inválido.';

    if (!$erros) {
        $stmt = $pdo->prepare('INSERT INTO cartoes (usuario_id, gateway, gateway_token, bandeira, ultimos_digitos, nome_titular, validade_mes, validade_ano)
                                VALUES (:usuario_id, :gateway, :token, :bandeira, :ultimos4, :titular, :mes, :ano)');
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'gateway'    => $gateway ?: 'demo',
            'token'      => $gatewayToken,
            'bandeira'   => $bandeira ?: 'desconhecida',
            'ultimos4'   => $ultimos4,
            'titular'    => $nomeTitular,
            'mes'        => $mes,
            'ano'        => $ano,
        ]);
        redirecionar('/lume_pet/cartoes.php');
    }
}

if (($_GET['acao'] ?? '') === 'excluir' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('DELETE FROM cartoes WHERE id = :id AND usuario_id = :uid');
    $stmt->execute([
        'id' => (int) $_GET['id'],
        'uid' => $usuarioId
    ]);

    redirecionar('/lume_pet/cartoes.php');
}

$stmt = $pdo->prepare('SELECT * FROM cartoes WHERE usuario_id = :uid ORDER BY criado_em DESC');
$stmt->execute(['uid' => $usuarioId]);
$cartoes = $stmt->fetchAll();

// Só a partir daqui é seguro imprimir HTML — todos os redirects já
// aconteceriam antes disso, se fosse o caso.
$tituloPagina = 'Meus cartões';
require_once __DIR__ . '/includes/header.php';
?>
<body class="lp-public">

<div class="lp-container">
    <h1 class="lp-title">Cadastro de Cartões</h1>

    <?php foreach ($erros as $erro): ?>
        <div class="lp-alert lp-alert-erro"><?= limpar($erro) ?></div>
    <?php endforeach; ?>

    <div class="lp-pay-wrap">
        <!-- Pré-visualização animada do cartão -->
        <div>
            <div class="lp-card-stage">
                <div class="lp-credit-card" id="ccPreview">
                    <div class="lp-cc-top">
                        <div>
                            <div class="lp-cc-brand">LUME PET</div>
                            <div class="lp-cc-chip"></div>
                        </div>
                        <div style="text-align:right;">
                            <div class="lp-cc-wave"></div>
                            <div class="lp-cc-flag" id="ccBandeira">&nbsp;</div>
                        </div>
                    </div>
                    <div>
                        <div class="lp-cc-number" id="ccNumero">•••• •••• •••• ••••</div>
                        <div class="lp-cc-bottom">
                            <div class="lp-cc-name" id="ccNome">NOME COMPLETO</div>
                            <div class="lp-cc-expiry">
                                <span>VALID THRU</span>
                                <span id="ccValidade" style="opacity:1;">MM/AA</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="lp-subtitle">Cartões salvos</h2>
            <?php if (!$cartoes): ?>
                <p class="lp-link-muted">Você ainda não tem cartões salvos.</p>
            <?php else: ?>
                <ul class="lp-saved-list">
                <?php foreach ($cartoes as $c): ?>
                    <li class="lp-saved-item">
                        <div class="lp-saved-info">
                            <div class="lp-mini-card"></div>
                            <div>
                                <?= limpar(ucfirst($c['bandeira'])) ?> •••• <?= limpar($c['ultimos_digitos']) ?><br>
                                <span class="lp-link-muted"><?= limpar($c['nome_titular']) ?> ·
                                <?= str_pad($c['validade_mes'], 2, '0', STR_PAD_LEFT) ?>/<?= $c['validade_ano'] ?></span>
                            </div>
                        </div>
                        <a href="?acao=excluir&id=<?= $c['id'] ?>" onclick="return confirm('Remover este cartão?')">Remover</a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Formulário -->
        <div class="lp-card-panel lp-pay-form">
            <!--
              (!) action="" em vez de "/cartoes.php": envia o POST pra própria
              URL atual, seja qual for a pasta onde o arquivo está hospedado
              (ex: /lume_pet/cartoes.php). Isso evita o 404, e evita que o
              formulário quebre de novo se você mover a pasta do projeto.
            -->
            <form method="post" action="" id="formCartao">
                <input type="hidden" name="csrf_token" value="<?= gerarTokenCsrf() ?>">
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="gateway" value="demo">
                <!-- Campos reais enviados ao servidor (preenchidos via JS antes do submit) -->
                <input type="hidden" name="gateway_token" id="gateway_token">
                <input type="hidden" name="bandeira" id="bandeira_hidden">
                <input type="hidden" name="ultimos_digitos" id="ultimos_digitos_hidden">
                <input type="hidden" name="validade_mes" id="validade_mes_hidden">
                <input type="hidden" name="validade_ano" id="validade_ano_hidden">

                <label>Número do cartão</label>
                <div class="lp-input-icon">
                    <input type="text" id="numeroCartao" inputmode="numeric" autocomplete="off"
                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                    <span class="lp-icon">💳</span>
                </div>

                <label>Nome do titular</label>
                <input type="text" id="nomeTitular" name="nome_titular" placeholder="Como está no cartão"
                       autocomplete="off" required>

                <div class="lp-row-3">
                    <div>
                        <label>Mês</label>
                        <input type="text" id="ccMes" inputmode="numeric" placeholder="12" maxlength="2" required>
                    </div>
                    <div>
                        <label>Ano</label>
                        <input type="text" id="ccAno" inputmode="numeric" placeholder="2028" maxlength="4" required>
                    </div>
                    <div>
                        <label>CVC</label>
                        <div class="lp-input-icon">
                            <input type="text" id="ccCvc" inputmode="numeric" placeholder="123" maxlength="4" required>
                            <span class="lp-icon">🔒</span>
                        </div>
                    </div>
                </div>

                <div class="lp-actions">
                    <button type="submit">Confirmar cartão</button>
                    <a href="perfil.php" class="lp-btn lp-btn-outline">Cancelar</a>
                </div>
            </form>
            <p class="lp-link-muted" style="margin-top:14px;">
                O número completo e o CVC nunca são enviados ao nosso servidor, apenas ao gateway de pagamento. Gravamos no banco
                apenas um token seguro do gateway de pagamento e os 4 últimos dígitos.
            </p>
        </div>
    </div>
</div>

<script>
(function () {
    const numeroInput   = document.getElementById('numeroCartao');
    const nomeInput      = document.getElementById('nomeTitular');
    const mesInput       = document.getElementById('ccMes');
    const anoInput       = document.getElementById('ccAno');
    const cvcInput       = document.getElementById('ccCvc');

    const ccNumero    = document.getElementById('ccNumero');
    const ccNome      = document.getElementById('ccNome');
    const ccValidade  = document.getElementById('ccValidade');
    const ccBandeira  = document.getElementById('ccBandeira');

    // Formata "1234567890123456" -> "1234 5678 9012 3456" enquanto digita
    numeroInput.addEventListener('input', function () {
        let digits = this.value.replace(/\D/g, '').slice(0, 16);
        this.value = digits.replace(/(.{4})/g, '$1 ').trim();

        const grupos = digits.padEnd(16, '•').match(/.{1,4}/g) || [];
        ccNumero.textContent = grupos.map(g => g.replace(/\d/g, d => d).padEnd(4, '•')).join(' ');

        ccBandeira.textContent = detectarBandeira(digits).toUpperCase();
    });

    nomeInput.addEventListener('input', function () {
        ccNome.textContent = this.value.trim() ? this.value.toUpperCase() : 'CARDHOLDER NAME';
    });

    function atualizarValidade() {
        const mes = mesInput.value.replace(/\D/g, '').padStart(2, '0').slice(0, 2);
        const ano = anoInput.value.replace(/\D/g, '').slice(-2).padStart(2, '0');
        ccValidade.textContent = (mesInput.value || anoInput.value) ? (mes + '/' + ano) : 'MM/AA';
    }
    mesInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 2);
        atualizarValidade();
    });
    anoInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 4);
        atualizarValidade();
    });
    cvcInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 4);
    });

    function detectarBandeira(digits) {
        if (/^4/.test(digits)) return 'visa';
        if (/^5[1-5]/.test(digits)) return 'mastercard';
        if (/^3[47]/.test(digits)) return 'amex';
        if (/^6(?:011|5)/.test(digits)) return 'elo';
        return digits ? 'cartão' : '';
    }

    /**
     * EM PRODUÇÃO: substitua esta função pela chamada real ao SDK do seu
     * gateway de pagamento (ex.: Stripe.js `stripe.createToken(cardElement)`),
     * que envia o número/CVC direto para o gateway e devolve um token seguro.
     * Aqui geramos apenas um token de demonstração local, só para o CRUD
     * funcionar sem uma conta de gateway configurada.
     */
    function gerarTokenLocalDeDemonstracao(ultimos4) {
        return 'demo_tok_' + ultimos4 + '_' + Date.now();
    }

    document.getElementById('formCartao').addEventListener('submit', function (e) {
        const digits = numeroInput.value.replace(/\D/g, '');

        if (digits.length < 13) {
            e.preventDefault();
            alert('Número de cartão inválido.');
            return;
        }
        if (!cvcInput.value || cvcInput.value.length < 3) {
            e.preventDefault();
            alert('CVC inválido.');
            return;
        }

        const ultimos4 = digits.slice(-4);
        const bandeira = detectarBandeira(digits);

        // Só isto é enviado ao servidor — nunca o número completo nem o CVC.
        document.getElementById('gateway_token').value      = gerarTokenLocalDeDemonstracao(ultimos4);
        document.getElementById('bandeira_hidden').value     = bandeira;
        document.getElementById('ultimos_digitos_hidden').value = ultimos4;
        document.getElementById('validade_mes_hidden').value = mesInput.value.padStart(2, '0');
        document.getElementById('validade_ano_hidden').value = anoInput.value.length === 2 ? '20' + anoInput.value : anoInput.value;

        // Impede que o número/CVC "brutos" sejam enviados no POST.
        numeroInput.removeAttribute('name');
        cvcInput.removeAttribute('name');
    });
})();
</script>

</body>
</html>