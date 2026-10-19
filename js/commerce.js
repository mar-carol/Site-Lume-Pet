    // CARRINHO


    let itensCarrinho = [];

    function toggleCarrinho(event) {
      event.preventDefault();
      const painel = document.getElementById('carrinhoPainel');
      painel.style.display = painel.style.display === 'block' ? 'none' : 'block';
    }

    // O carrinho só fecha quando o usuário clicar para fechar

   function adicionarAoCarrinho(id, nome, preco) {
    const precoNum = Number(preco);

    const existente = itensCarrinho.find(
        item => item.id === id
    );

    if (existente) {
        existente.qtd++;
    } else {
        itensCarrinho.push({
            id: id,
            nome: nome,
            preco: precoNum,
            qtd: 1
        });
    }

    atualizarCarrinho();
    mostrarToast(nome);
}

    function alterarQtd(nome, delta) {
      const item = itensCarrinho.find(i => i.nome === nome);
      if (!item) return;
      item.qtd += delta;
      if (item.qtd <= 0) {
        itensCarrinho = itensCarrinho.filter(i => i.nome !== nome);
      }
      atualizarCarrinho();
    }

    function removerItem(nome) {
      itensCarrinho = itensCarrinho.filter(i => i.nome !== nome);
      atualizarCarrinho();
    }

    function esvaziarCarrinho() {
      itensCarrinho = [];
      atualizarCarrinho();
    }

    function atualizarCarrinho() {
      const lista = document.getElementById('carrinho-lista');
      const count = document.querySelector('.carrinho-count');
      const total = document.getElementById('carrinho-valor');

      const totalItens = itensCarrinho.reduce((s, i) => s + i.qtd, 0);
      count.textContent = totalItens;

      if (itensCarrinho.length === 0) {
        lista.innerHTML = '<li class="carrinho-vazio">Nenhum item ainda.</li>';
      } else {
        lista.innerHTML = itensCarrinho.map(item => `
          <li>
            <span style="flex:1">${item.nome}</span>
            <div class="item-qtd">
              <button onclick="alterarQtd('${item.nome}', -1)">−</button>
              <span>${item.qtd}</span>
              <button onclick="alterarQtd('${item.nome}', 1)">+</button>
            </div>
            <span>R$ ${(item.preco * item.qtd).toFixed(2).replace('.', ',')}</span>
            <button class="item-remover" onclick="removerItem('${item.nome}')">🗑️</button>
          </li>
        `).join('');
      }

      const soma = itensCarrinho.reduce((s, i) => s + i.preco * i.qtd, 0);
      total.textContent = `R$ ${soma.toFixed(2).replace('.', ',')}`;
    }



    // PAGAMENTO (com CEP/frete e cupom)


    let freteAtual = 0;
    let cepValidado = false;
    let cupomAtual = null; // { codigo, tipo: 'percentual' | 'fixo' | 'frete', valor, desc }

    const CUPONS = {
      'PET10':       { tipo: 'percentual', valor: 10, desc: '10% de desconto' },
      'PET20':       { tipo: 'percentual', valor: 20, desc: '20% de desconto' },
      'BEMVINDO15':  { tipo: 'fixo',       valor: 15, desc: 'R$ 15,00 de desconto' },
      'FRETEGRATIS': { tipo: 'frete',      valor: 0,  desc: 'Frete grátis' },
    };

    // tabela simplificada de frete por estado (simulação)
    const TABELA_FRETE = {
      SP: { valor: 12.90, prazo: '2 a 3 dias úteis' },
      RJ: { valor: 18.90, prazo: '3 a 5 dias úteis' },
      MG: { valor: 18.90, prazo: '3 a 5 dias úteis' },
      ES: { valor: 18.90, prazo: '3 a 5 dias úteis' },
      PR: { valor: 22.90, prazo: '4 a 6 dias úteis' },
      SC: { valor: 22.90, prazo: '4 a 6 dias úteis' },
      RS: { valor: 24.90, prazo: '4 a 7 dias úteis' },
    };
    const FRETE_PADRAO = { valor: 29.90, prazo: '5 a 9 dias úteis' };

    // controle do temporizador do PIX
    const PIX_DURACAO_MS = 15 * 60 * 1000; // 15 minutos
    let pixTimerInterval = null;
    let pixExpiraEm = null;
    let pixCodigo = '';

    function abrirPagamento() {
      if (itensCarrinho.length === 0) {
        alert('Seu carrinho está vazio!');
        return;
      }

      // exige login antes de permitir o pagamento
      if (!usuarioLogado) {
        document.getElementById('carrinhoPainel').style.display = 'none';
        alert('Você precisa entrar na sua conta para finalizar a compra.');
        mudarAba('login');
        document.getElementById('loginOverlay').classList.add('aberto');
        return;
      }

      // reseta o estado de frete/cupom/endereço cada vez que o checkout abre
      freteAtual = 0;
      cepValidado = false;
      cupomAtual = null;
      document.getElementById('cepInput').value = '';
      document.getElementById('cepResultado').innerHTML = '';
      document.getElementById('cupomInput').value = '';
      document.getElementById('cupomResultado').innerHTML = '';
      document.getElementById('numeroCasa').value = '';
      document.getElementById('complemento').value = '';
      document.getElementById('enderecoDetalhes').style.display = 'none';

      // volta a forma de pagamento para PIX por padrão
      document.querySelector('input[name="pagamento"][value="pix"]').checked = true;

      atualizarResumoPagamento();
      atualizarMetodoPagamento();

      document.getElementById('pagamentoOverlay').classList.add('aberto');
      document.getElementById('carrinhoPainel').style.display = 'none';
    }

    function fecharPagamento() {
      document.getElementById('pagamentoOverlay').classList.remove('aberto');
      pararTimerPix();
    }

    function mascaraCep(input) {
      input.value = input.value.replace(/\D/g, '').replace(/(\d{5})(\d)/, '$1-$2').slice(0, 9);
    }

    async function calcularFrete() {
      const cepInput = document.getElementById('cepInput');
      const resultado = document.getElementById('cepResultado');
      const enderecoDetalhes = document.getElementById('enderecoDetalhes');
      const cep = cepInput.value.replace(/\D/g, '');

      if (cep.length !== 8) {
        resultado.innerHTML = '<span class="erro-frete">CEP inválido. Digite os 8 números.</span>';
        freteAtual = 0;
        cepValidado = false;
        enderecoDetalhes.style.display = 'none';
        atualizarResumoPagamento();
        return;
      }

      resultado.innerHTML = '<span class="carregando-frete">Calculando frete...</span>';

      try {
        const resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const dados = await resp.json();

        if (dados.erro) {
          resultado.innerHTML = '<span class="erro-frete">CEP não encontrado.</span>';
          freteAtual = 0;
          cepValidado = false;
          enderecoDetalhes.style.display = 'none';
          atualizarResumoPagamento();
          return;
        }

        const info = TABELA_FRETE[dados.uf] || FRETE_PADRAO;
        freteAtual = info.valor;
        cepValidado = true;

        resultado.innerHTML = `
          <span class="frete-ok">
            📍 ${dados.logradouro ? dados.logradouro + ', ' : ''}${dados.localidade}/${dados.uf}<br>
            🚚 Frete estimado: R$ ${info.valor.toFixed(2).replace('.', ',')} — ${info.prazo}
          </span>
        `;

        enderecoDetalhes.style.display = 'flex';
        atualizarResumoPagamento();
      } catch (e) {
        resultado.innerHTML = '<span class="erro-frete">Não foi possível calcular o frete agora. Tente novamente.</span>';
        freteAtual = 0;
        cepValidado = false;
        enderecoDetalhes.style.display = 'none';
        atualizarResumoPagamento();
      }
    }

    function aplicarCupom() {
      const input = document.getElementById('cupomInput');
      const resultado = document.getElementById('cupomResultado');
      const codigo = input.value.trim().toUpperCase();

      if (!codigo) {
        resultado.innerHTML = '<span class="erro-frete">Digite um código de cupom.</span>';
        return;
      }

      const cupom = CUPONS[codigo];
      if (!cupom) {
        resultado.innerHTML = '<span class="erro-frete">Cupom inválido ou expirado.</span>';
        cupomAtual = null;
        atualizarResumoPagamento();
        return;
      }

      cupomAtual = { codigo, ...cupom };
      resultado.innerHTML = `<span class="frete-ok">🎟️ Cupom "${codigo}" aplicado: ${cupom.desc}</span>`;
      atualizarResumoPagamento();
    }

    function calcularTotaisPagamento() {
      const subtotal = itensCarrinho.reduce((s, i) => s + i.preco * i.qtd, 0);
      let frete = cepValidado ? freteAtual : 0;
      let desconto = 0;

      if (cupomAtual) {
        if (cupomAtual.tipo === 'percentual') {
          desconto = subtotal * (cupomAtual.valor / 100);
        } else if (cupomAtual.tipo === 'fixo') {
          desconto = cupomAtual.valor;
        } else if (cupomAtual.tipo === 'frete') {
          frete = 0;
        }
      }

      const total = Math.max(subtotal - desconto, 0) + frete;
      return { subtotal, frete, desconto, total };
    }

    function atualizarResumoPagamento() {
      const resumo = document.getElementById('pagamentoResumo');
      if (!resumo) return;

      const { subtotal, frete, desconto, total } = calcularTotaisPagamento();

      let html = itensCarrinho.map(i =>
        `<p>${i.nome} x${i.qtd} — R$ ${(i.preco * i.qtd).toFixed(2).replace('.', ',')}</p>`
      ).join('');

      html += `<hr><p>Subtotal: R$ ${subtotal.toFixed(2).replace('.', ',')}</p>`;

      if (desconto > 0) {
        html += `<p>Desconto: - R$ ${desconto.toFixed(2).replace('.', ',')}</p>`;
      }

      html += `<p>Frete: ${cepValidado ? 'R$ ' + frete.toFixed(2).replace('.', ',') : 'informe o CEP acima'}</p>`;
      html += `<hr><strong>Total: R$ ${total.toFixed(2).replace('.', ',')}</strong>`;

      resumo.innerHTML = html;
    }

    // ── FORMA DE PAGAMENTO (PIX ou Cartão) ──

    function atualizarMetodoPagamento() {
      const metodoEl = document.querySelector('input[name="pagamento"]:checked');
      const metodo = metodoEl ? metodoEl.value : 'pix';
      const extra = document.getElementById('pagamento-extra');

      pararTimerPix();

      if (metodo === 'pix') {
        extra.innerHTML = renderPix();
        iniciarTimerPix();
      } else {
        extra.innerHTML = renderCartao();
      }
    }

    function renderPix() {
      pixCodigo = gerarCodigoPix();
      return `
        <div class="pix-bloco">
          <p class="pix-aviso">⏱️ Pague em até <strong id="pixTempo">15:00</strong> ou o pedido será cancelado automaticamente.</p>
          <div class="pix-qr"></div>
          <p>Código Pix Copia e Cola:</p>
          <input type="text" id="pixCodigoInput" value="${pixCodigo}" readonly onclick="this.select()">
          <small>Toque no código para selecionar e copiar</small>
        </div>
      `;
    }

    function gerarCodigoPix() {
      const bloco = () => Math.random().toString(36).slice(2, 10).toUpperCase();
      return `00020126580014BR.GOV.BCB.PIX0136${bloco()}-${bloco()}-${bloco()}5204000053039865802BR5913LUME PET LTDA6009SAO PAULO62070503***6304${bloco().slice(0, 4)}`;
    }

    function iniciarTimerPix() {
      pixExpiraEm = Date.now() + PIX_DURACAO_MS;
      atualizarTimerPix();
      pixTimerInterval = setInterval(atualizarTimerPix, 1000);
    }

    function pararTimerPix() {
      if (pixTimerInterval) {
        clearInterval(pixTimerInterval);
        pixTimerInterval = null;
      }
    }

    function atualizarTimerPix() {
      const tempoEl = document.getElementById('pixTempo');
      const restante = pixExpiraEm - Date.now();

      if (restante <= 0) {
        pararTimerPix();
        alert('⏱️ O tempo para pagamento via PIX expirou. Seu pedido foi cancelado.');
        esvaziarCarrinho();
        fecharPagamento();
        return;
      }

      if (tempoEl) {
        const min = Math.floor(restante / 60000);
        const seg = Math.floor((restante % 60000) / 1000);
        tempoEl.textContent = `${String(min).padStart(2, '0')}:${String(seg).padStart(2, '0')}`;
      }
    }

    function renderCartao() {
      return `
        <div class="cartao-bloco">
          <div class="cartao-tipo">
            <label><input type="radio" name="tipoCartao" value="credito" checked> Crédito</label>
            <label><input type="radio" name="tipoCartao" value="debito"> Débito</label>
          </div>
          <input type="text" id="cartaoNome" placeholder="Nome do titular (como no cartão)">
          <input type="text" id="cartaoNumero" placeholder="Número do cartão" maxlength="19" oninput="mascaraCartao(this)">
          <div class="cartao-linha">
            <input type="text" id="cartaoValidade" placeholder="Validade (MM/AA)" maxlength="5" oninput="mascaraValidade(this)">
            <input type="text" id="cartaoCvv" placeholder="CVV" maxlength="3" oninput="this.value = this.value.replace(/\\D/g, '').slice(0, 3)">
          </div>
        </div>
      `;
    }

    function confirmarPedido() {
      if (!usuarioLogado) {
        alert('Você precisa entrar na sua conta para finalizar a compra.');
        return;
      }

      if (!cepValidado) {
        alert('Por favor, informe e calcule o frete do seu CEP antes de finalizar o pedido.');
        return;
      }

      const numero = document.getElementById('numeroCasa').value.trim();
      if (!numero) {
        alert('Por favor, informe o número da sua residência.');
        return;
      }

      const metodoEl = document.querySelector('input[name="pagamento"]:checked');
      const metodo = metodoEl ? metodoEl.value : 'pix';

      if (metodo === 'cartao') {
        const tipoCartaoEl = document.querySelector('input[name="tipoCartao"]:checked');
        const tipoCartao = tipoCartaoEl ? tipoCartaoEl.value : 'credito';
        const nomeCartao = (document.getElementById('cartaoNome') || {}).value?.trim();
        const numeroCartao = (document.getElementById('cartaoNumero') || {}).value?.trim();
        const validade = (document.getElementById('cartaoValidade') || {}).value?.trim();
        const cvv = (document.getElementById('cartaoCvv') || {}).value?.trim();

        if (!nomeCartao || !numeroCartao || !validade || !cvv) {
          alert('Preencha todos os dados do cartão.');
          return;
        }
        if (numeroCartao.replace(/\s/g, '').length < 13) {
          alert('Número do cartão inválido.');
          return;
        }
        if (!/^\d{2}\/\d{2}$/.test(validade)) {
          alert('Validade inválida. Use o formato MM/AA.');
          return;
        }
        if (cvv.length < 3) {
          alert('CVV inválido.');
          return;
        }

        const nomeTipo = tipoCartao === 'credito' ? 'Cartão de Crédito' : 'Cartão de Débito';
        alert(`✅ Pedido confirmado via ${nomeTipo}!\nObrigado pela compra!`);
        finalizarPedido(nomeTipo);
        return;
      }

      // PIX
      alert('✅ Pagamento via PIX confirmado!\nObrigado pela compra!');
      finalizarPedido('PIX');
    }

    function finalizarPedido(metodoNome) {
      if (usuarioLogado) {
        const chave = 'lumepet_compras_' + usuarioLogado.email;
        const compras = JSON.parse(localStorage.getItem(chave) || '[]');
        itensCarrinho.forEach(item => {
          if (!compras.includes(item.nome)) compras.push(item.nome);
        });
        localStorage.setItem(chave, JSON.stringify(compras));

        const totais = calcularTotaisPagamento();
        const pedido = {
          id: Date.now().toString().slice(-6),
          data: new Date().toLocaleDateString('pt-BR'),
          itens: itensCarrinho.map(i => ({ nome: i.nome, qtd: i.qtd, preco: i.preco })),
          subtotal: totais.subtotal,
          frete: totais.frete,
          desconto: totais.desconto,
          total: totais.total,
          metodo: metodoNome || 'PIX'
        };
        salvarPedido(usuarioLogado.email, pedido);
      }

      pararTimerPix();
      esvaziarCarrinho();
      fecharPagamento();
      document.getElementById('numeroCasa').value = '';
      document.getElementById('complemento').value = '';
    }

