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
    let cepEndereco = null;
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
      cepEndereco = null;
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
        cepEndereco = {
          uf: dados.uf,
          logradouro: dados.logradouro || '',
          bairro: dados.bairro || '',
          localidade: dados.localidade || ''
        };

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
          desconto = Math.min(subtotal, cupomAtual.valor);
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
      if (document.getElementById('pixContainerPedido')) desenharPixPedido();

    }

    // ── FORMA DE PAGAMENTO (PIX ou Cartão) ──

    function atualizarMetodoPagamento() {
      const metodoEl = document.querySelector('input[name="pagamento"]:checked');
      const metodo = metodoEl ? metodoEl.value : 'pix';
      const extra = document.getElementById('pagamento-extra');

      if (metodo === 'pix') {
        extra.innerHTML = renderPix();
        desenharPixPedido();
      } else {
        extra.innerHTML = renderCartao();
      }
    }

        function renderPix() {
      return `
        <div class="pix-bloco">
          <p class="pix-aviso">Checkout demonstrativo: o QR Code não processa pagamentos. Ao confirmar, o pedido ficará pendente.</p>
          <div id="pixContainerPedido"></div>
        </div>
      `;
    }

    //==== espaço vazio para gerar o qrcode do pix ====
    function desenharPixPedido() {
      const container = document.getElementById('pixContainerPedido');
      if (!container) return;
      const total = calcularTotaisPagamento().total;
      pixCodigo = LumePix.renderizar(container, total, 'PED' + Date.now());
    }


    function renderCartao() {
      return `
        <div class="cartao-bloco">
          <p class="pix-aviso">Checkout demonstrativo: nenhum dado de cartão é solicitado ou processado.</p>
          <div class="cartao-tipo">
            <label><input type="radio" name="tipoCartao" value="credito" checked> Crédito</label>
            <label><input type="radio" name="tipoCartao" value="debito"> Débito</label>
          </div>
        </div>
      `;
    }

    async function confirmarPedido() {
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
        await finalizarPedido(tipoCartao);
        return;
      }

      await finalizarPedido('pix');
    }

    let enviandoPedido = false;

    async function finalizarPedido(metodo) {
      if (enviandoPedido) return;
      enviandoPedido = true;
      const botao = document.querySelector('.btn-confirmar');
      if (botao) {
        botao.disabled = true;
        botao.textContent = 'Registrando pedido...';
      }

      try {
        const resposta = await fetch(window.pedidosApiUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken || ''
          },
          body: JSON.stringify({
            acao: 'finalizar',
            itens: itensCarrinho.map(item => ({ id: item.id, qtd: item.qtd })),
            metodo,
            cep: document.getElementById('cepInput').value.replace(/\D/g, ''),
            uf: cepEndereco?.uf || '',
            logradouro: cepEndereco?.logradouro || '',
            bairro: cepEndereco?.bairro || '',
            localidade: cepEndereco?.localidade || '',
            numero: document.getElementById('numeroCasa').value.trim(),
            complemento: document.getElementById('complemento').value.trim(),
            cupom: cupomAtual?.codigo || ''
          })
        });
        const resultado = await resposta.json();
        if (!resposta.ok || !resultado.sucesso) {
          throw new Error(resultado.erro || 'Não foi possível registrar o pedido.');
        }

        esvaziarCarrinho();
        fecharPagamento();
        document.getElementById('numeroCasa').value = '';
        document.getElementById('complemento').value = '';
        alert(`Pedido #${resultado.compra_id} registrado.\nO checkout é uma simulação; nenhum pagamento foi processado. O pedido está pendente de confirmação.`);
      } catch (erro) {
        alert(erro.message || 'Não foi possível registrar o pedido. Seus itens continuam no carrinho.');
      } finally {
        enviandoPedido = false;
        if (botao) {
          botao.disabled = false;
          botao.textContent = '✅ Confirmar pedido';
        }
      }
    }
