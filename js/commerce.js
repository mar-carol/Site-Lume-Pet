    // CARRINHO


    function lerListaSalva(chave) {
      try {
        const dados = JSON.parse(localStorage.getItem(chave) || '[]');
        if (!Array.isArray(dados)) throw new TypeError('O conteúdo salvo não é uma lista.');
        return dados.filter(item =>
          item && Number.isInteger(Number(item.id)) && Number(item.id) > 0
          && typeof item.nome === 'string' && Number.isFinite(Number(item.preco))
          && Number(item.preco) >= 0
        ).map(item => ({
          id: Number(item.id),
          nome: item.nome,
          preco: Number(item.preco),
          qtd: Math.min(100, Math.max(1, Number.parseInt(item.qtd, 10) || 1)),
          selecionado: item.selecionado !== false
        }));
      } catch (erro) {
        console.error(`Não foi possível carregar a lista "${chave}".`, erro);
        return [];
      }
    }

    const escopoCarrinho = window.usuarioSessao?.id
      ? `usuario_${Number(window.usuarioSessao.id)}`
      : 'visitante';
    const chaveCarrinho = `lumepet_carrinho_v2_${escopoCarrinho}`;
    const chaveSalvos = `lumepet_salvos_v2_${escopoCarrinho}`;
    try {
      localStorage.removeItem('lumepet_carrinho_v1');
      localStorage.removeItem('lumepet_salvos_v1');
    } catch (erro) {
      console.error('Não foi possível remover o carrinho antigo compartilhado.', erro);
    }
    let itensCarrinho = lerListaSalva(chaveCarrinho);
    let itensSalvos = lerListaSalva(chaveSalvos);

    function persistirListasCarrinho() {
      try {
        localStorage.setItem(chaveCarrinho, JSON.stringify(itensCarrinho));
        localStorage.setItem(chaveSalvos, JSON.stringify(itensSalvos));
      } catch (erro) {
        console.error('Não foi possível salvar o carrinho neste navegador.', erro);
        const lista = document.getElementById('carrinho-lista');
        if (lista && !document.getElementById('carrinhoAvisoStorage')) {
          lista.insertAdjacentHTML('beforebegin', '<p class="carrinho-aviso-storage" id="carrinhoAvisoStorage" role="status">Não foi possível salvar as alterações neste navegador.</p>');
        }
      }
    }

    function escaparTextoHTML(valor) {
      return String(valor).replace(/[&<>"']/g, caractere => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      })[caractere]);
    }

    function toggleCarrinho(event) {
      event.preventDefault();
      const painel = document.getElementById('carrinhoPainel');
      if (painel.classList.contains('aberto')) {
        fecharCarrinho();
        return;
      }

      painel.classList.add('aberto');
      painel.setAttribute('aria-hidden', 'false');
      document.getElementById('carrinhoOverlay').classList.add('aberto');
      document.getElementById('carrinhoAbrir').setAttribute('aria-expanded', 'true');
      document.body.classList.add('carrinho-aberto');
      document.getElementById('carrinhoFechar').focus();
    }

    function fecharCarrinho() {
      const painel = document.getElementById('carrinhoPainel');
      if (!painel.classList.contains('aberto')) return;

      painel.classList.remove('aberto');
      painel.setAttribute('aria-hidden', 'true');
      document.getElementById('carrinhoOverlay').classList.remove('aberto');
      document.getElementById('carrinhoAbrir').setAttribute('aria-expanded', 'false');
      document.body.classList.remove('carrinho-aberto');
      document.getElementById('carrinhoAbrir').focus();
    }

    document.addEventListener('keydown', event => {
      const painel = document.getElementById('carrinhoPainel');
      if (!painel.classList.contains('aberto')) return;

      if (event.key === 'Escape') {
        fecharCarrinho();
        return;
      }

      if (event.key === 'Tab') {
        const focaveis = painel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex="0"]');
        const primeiro = focaveis[0];
        const ultimo = focaveis[focaveis.length - 1];

        if (event.shiftKey && document.activeElement === primeiro) {
          event.preventDefault();
          ultimo.focus();
        } else if (!event.shiftKey && document.activeElement === ultimo) {
          event.preventDefault();
          primeiro.focus();
        }
      }
    });

   function adicionarAoCarrinho(id, nome, preco) {
    const precoNum = Number(preco);

    id = Number(id);
    const existente = itensCarrinho.find(item => item.id === id);

    if (existente) {
        existente.qtd = Math.min(100, existente.qtd + 1);
        existente.selecionado = true;
    } else {
        itensCarrinho.push({
            id: id,
            nome: nome,
            preco: precoNum,
            qtd: 1,
            selecionado: true
        });
    }

    atualizarCarrinho();
    persistirListasCarrinho();
    mostrarToast(nome);
}

    function alterarQtd(id, delta) {
      const item = itensCarrinho.find(i => i.id === Number(id));
      if (!item) return;
      item.qtd = Math.min(100, item.qtd + delta);
      if (item.qtd <= 0) {
        itensCarrinho = itensCarrinho.filter(i => i.id !== Number(id));
      }
      atualizarCarrinho();
      persistirListasCarrinho();
    }

    function alternarSelecaoCarrinho(id, selecionado) {
      const item = itensCarrinho.find(i => i.id === Number(id));
      if (!item) return;
      item.selecionado = Boolean(selecionado);
      atualizarCarrinho();
      persistirListasCarrinho();
    }

    function guardarParaDepois(id) {
      const item = itensCarrinho.find(i => i.id === Number(id));
      if (!item) return;
      itensCarrinho = itensCarrinho.filter(i => i.id !== Number(id));
      const existente = itensSalvos.find(i => i.id === Number(id));
      if (existente) {
        existente.qtd = Math.min(100, existente.qtd + item.qtd);
      } else {
        itensSalvos.push({ ...item, selecionado: false });
      }
      atualizarCarrinho();
      persistirListasCarrinho();
    }

    function moverParaCarrinho(id) {
      const item = itensSalvos.find(i => i.id === Number(id));
      if (!item) return;
      itensSalvos = itensSalvos.filter(i => i.id !== Number(id));
      const existente = itensCarrinho.find(i => i.id === Number(id));
      if (existente) {
        existente.qtd = Math.min(100, existente.qtd + item.qtd);
      } else {
        itensCarrinho.push({ ...item, selecionado: true });
      }
      atualizarCarrinho();
      persistirListasCarrinho();
    }

    function removerItem(id) {
      itensCarrinho = itensCarrinho.filter(i => i.id !== Number(id));
      atualizarCarrinho();
      persistirListasCarrinho();
    }

    function removerItemSalvo(id) {
      itensSalvos = itensSalvos.filter(i => i.id !== Number(id));
      atualizarCarrinho();
      persistirListasCarrinho();
    }

    function esvaziarCarrinho() {
      itensCarrinho = [];
      atualizarCarrinho();
      persistirListasCarrinho();
    }

    function atualizarCarrinho() {
      const lista = document.getElementById('carrinho-lista');
      const listaSalvos = document.getElementById('carrinho-salvos');
      const count = document.querySelector('.carrinho-count');
      const total = document.getElementById('carrinho-valor');

      const totalItens = itensCarrinho.reduce((s, i) => s + i.qtd, 0);
      if (count) count.textContent = totalItens > 9 ? '9+' : String(totalItens);

      if (lista) {
        lista.innerHTML = itensCarrinho.length === 0
          ? '<li class="carrinho-vazio">Nenhum item ainda.</li>'
          : itensCarrinho.map(item => `
          <li class="carrinho-item">
            <label class="carrinho-item-selecao">
              <input type="checkbox" ${item.selecionado ? 'checked' : ''}
                     onchange="alternarSelecaoCarrinho(${item.id}, this.checked)"
                     aria-label="Selecionar ${escaparTextoHTML(item.nome)} para comprar">
              <span>${escaparTextoHTML(item.nome)}</span>
            </label>
            <div class="item-qtd">
              <button type="button" onclick="alterarQtd(${item.id}, -1)" aria-label="Diminuir quantidade">−</button>
              <span>${item.qtd}</span>
              <button type="button" onclick="alterarQtd(${item.id}, 1)" aria-label="Aumentar quantidade">+</button>
            </div>
            <span>R$ ${(item.preco * item.qtd).toFixed(2).replace('.', ',')}</span>
            <div class="carrinho-item-acoes">
              <button type="button" class="item-guardar" onclick="guardarParaDepois(${item.id})">Guardar</button>
              <button type="button" class="item-remover" onclick="removerItem(${item.id})" aria-label="Remover ${escaparTextoHTML(item.nome)}"><img src="https://img.icons8.com/?size=100&id=uAIm4KlVQ4e5&format=png&color=000000" alt="" aria-hidden="true"></button>
            </div>
          </li>
        `).join('');
      }

      if (listaSalvos) {
        listaSalvos.innerHTML = itensSalvos.length === 0
          ? '<li class="carrinho-vazio">Nenhum produto guardado.</li>'
          : itensSalvos.map(item => `
          <li class="carrinho-item carrinho-item-salvo">
            <span>${escaparTextoHTML(item.nome)}</span>
            <span>R$ ${(item.preco * item.qtd).toFixed(2).replace('.', ',')}</span>
            <div class="carrinho-item-acoes">
              <button type="button" class="item-guardar" onclick="moverParaCarrinho(${item.id})">Mover para o carrinho</button>
              <button type="button" class="item-remover" onclick="removerItemSalvo(${item.id})" aria-label="Remover ${escaparTextoHTML(item.nome)} dos salvos"><img src="https://img.icons8.com/?size=100&id=uAIm4KlVQ4e5&format=png&color=000000" alt="" aria-hidden="true"></button>
            </div>
          </li>
        `).join('');
      }

      const selecionados = itensCarrinho.filter(item => item.selecionado);
      const soma = selecionados.reduce((s, i) => s + i.preco * i.qtd, 0);
      if (total) total.textContent = `R$ ${soma.toFixed(2).replace('.', ',')}`;
    }

    atualizarCarrinho();



    // PAGAMENTO (com CEP/frete e cupom)


    let freteAtual = 0;
    let cepValidado = false;
    let cepEndereco = null;
    let enderecoSelecionadoSalvo = null;
    let consultaFreteId = 0;
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
      const itensSelecionados = itensCarrinho.filter(item => item.selecionado);
      if (itensSelecionados.length === 0) {
        alert(itensCarrinho.length ? 'Selecione pelo menos um produto para comprar.' : 'Seu carrinho está vazio!');
        return;
      }

      // exige login antes de permitir o pagamento
      if (!usuarioLogado) {
        fecharCarrinho();
        alert('Você precisa entrar na sua conta para finalizar a compra.');
        mudarAba('login');
        document.getElementById('loginOverlay').classList.add('aberto');
        return;
      }
      if (!window.checkoutDados?.perfilCompleto) {
        alert('Antes de comprar, complete seu nome e telefone no perfil. O endereço de entrega será informado no checkout. Seu carrinho continuará salvo.');
        window.location.href = window.checkoutDados?.perfilUrl || 'usuario/perfil.php';
        return;
      }

      // reseta o estado de frete/cupom/endereço cada vez que o checkout abre
      freteAtual = 0;
      cepValidado = false;
      cepEndereco = null;
      enderecoSelecionadoSalvo = null;
      cupomAtual = null;
      document.getElementById('cepInput').value = '';
      document.getElementById('cepResultado').innerHTML = '';
      document.getElementById('cupomInput').value = '';
      document.getElementById('cupomResultado').innerHTML = '';
      document.getElementById('numeroCasa').value = '';
      document.getElementById('complemento').value = '';
      document.getElementById('logradouroEntrega').value = '';
      document.getElementById('salvarEnderecoPadrao').checked = false;
      document.getElementById('enderecoDetalhes').style.display = 'none';
      const enderecoSalvo = document.getElementById('enderecoSalvo');
      const enderecoPadrao = window.checkoutDados?.enderecos?.find(endereco => Number(endereco.padrao) === 1);
      if (enderecoSalvo) {
        enderecoSalvo.value = enderecoPadrao ? String(enderecoPadrao.id) : 'novo';
        selecionarEnderecoCheckout(Boolean(enderecoPadrao));
      }

      // volta a forma de pagamento para PIX por padrão
      document.querySelector('input[name="pagamento"][value="pix"]').checked = true;

      atualizarResumoPagamento();
      atualizarMetodoPagamento();

      document.getElementById('pagamentoOverlay').classList.add('aberto');
      fecharCarrinho();
    }

    function selecionarEnderecoCheckout(recalcular = true) {
      const seletor = document.getElementById('enderecoSalvo');
      const detalhes = document.getElementById('enderecoDetalhes');
      const campos = ['cepInput', 'logradouroEntrega', 'numeroCasa', 'complemento'];
      if (!seletor || !detalhes) return;

      consultaFreteId++;
      const endereco = window.checkoutDados?.enderecos?.find(item => String(item.id) === seletor.value);
      enderecoSelecionadoSalvo = endereco || null;
      const numeroSalvo = String(endereco?.numero || '').trim();
      const numeroLegado = endereco && (
        numeroSalvo === '' || ['S/N', 'SN', 'S N'].includes(numeroSalvo.toUpperCase())
      );
      campos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) campo.readOnly = Boolean(endereco) && !(id === 'numeroCasa' && numeroLegado);
      });

      if (!endereco) {
        document.getElementById('cepInput').value = '';
        document.getElementById('logradouroEntrega').value = '';
        document.getElementById('numeroCasa').value = '';
        document.getElementById('complemento').value = '';
        document.getElementById('cepResultado').innerHTML = '';
        detalhes.style.display = 'none';
        document.getElementById('salvarEnderecoPadrao').checked = false;
        freteAtual = 0;
        cepValidado = false;
        cepEndereco = null;
        atualizarResumoPagamento();
        return;
      }

      document.getElementById('cepInput').value = endereco.cep;
      document.getElementById('logradouroEntrega').value = endereco.logradouro;
      document.getElementById('numeroCasa').value = numeroLegado ? '' : endereco.numero;
      document.getElementById('complemento').value = endereco.complemento || '';
      document.getElementById('salvarEnderecoPadrao').checked = false;
      detalhes.style.display = 'flex';
      cepEndereco = {
        uf: endereco.estado,
        logradouro: endereco.logradouro,
        bairro: endereco.bairro || '',
        localidade: endereco.cidade
      };
      if (recalcular) calcularFrete();
    }

    function fecharPagamento() {
      document.getElementById('pagamentoOverlay').classList.remove('aberto');
    }

    function mascaraCep(input) {
      input.value = input.value.replace(/\D/g, '').replace(/(\d{5})(\d)/, '$1-$2').slice(0, 9);
    }

    function atualizarNumeroEnderecoSelecionado() {
      if (!enderecoSelecionadoSalvo || !cepValidado) return;
      const cep = document.getElementById('cepInput').value.replace(/\D/g, '');
      if (cep.length !== 8) return;
      const endereco = enderecoSelecionadoSalvo;
      const uf = String(endereco.estado || '').toUpperCase();
      const info = TABELA_FRETE[uf] || FRETE_PADRAO;
      const partes = [
        endereco.logradouro,
        document.getElementById('numeroCasa').value.trim()
          ? `Número ${document.getElementById('numeroCasa').value.trim()}`
          : '',
        endereco.complemento,
        endereco.bairro,
        `${endereco.cidade}/${uf}`,
        `CEP ${cep.slice(0, 5)}-${cep.slice(5)}`
      ];
      document.getElementById('cepResultado').innerHTML = `
        <span class="frete-ok">
          ${partes.filter(Boolean).map(escaparTextoHTML).join(', ')}<br>
          🚚 Frete estimado: R$ ${info.valor.toFixed(2).replace('.', ',')} — ${info.prazo}
        </span>
      `;
    }

    async function calcularFrete() {
      const cepInput = document.getElementById('cepInput');
      const resultado = document.getElementById('cepResultado');
      const enderecoDetalhes = document.getElementById('enderecoDetalhes');
      const cep = cepInput.value.replace(/\D/g, '');
      const consultaAtual = ++consultaFreteId;

      if (cep.length !== 8) {
        resultado.innerHTML = '<span class="erro-frete">CEP inválido. Digite os 8 números.</span>';
        freteAtual = 0;
        cepValidado = false;
        enderecoDetalhes.style.display = 'none';
        atualizarResumoPagamento();
        return;
      }

      if (enderecoSelecionadoSalvo) {
        const uf = String(enderecoSelecionadoSalvo.estado || '').toUpperCase();
        const info = TABELA_FRETE[uf] || FRETE_PADRAO;
        freteAtual = info.valor;
        cepValidado = true;
        cepEndereco = {
          uf,
          logradouro: enderecoSelecionadoSalvo.logradouro || '',
          bairro: enderecoSelecionadoSalvo.bairro || '',
          localidade: enderecoSelecionadoSalvo.cidade || ''
        };
        atualizarNumeroEnderecoSelecionado();
        enderecoDetalhes.style.display = 'flex';
        atualizarResumoPagamento();
        return;
      }

      resultado.innerHTML = '<span class="carregando-frete">Calculando frete...</span>';

      try {
        const resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        if (!resp.ok) throw new Error('Não foi possível consultar o CEP.');
        const dados = await resp.json();

        if (consultaAtual !== consultaFreteId) return;

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
          logradouro: enderecoSelecionadoSalvo?.logradouro || dados.logradouro || '',
          bairro: enderecoSelecionadoSalvo?.bairro || dados.bairro || '',
          localidade: enderecoSelecionadoSalvo?.cidade || dados.localidade || ''
        };
        document.getElementById('logradouroEntrega').value = cepEndereco.logradouro;

        resultado.innerHTML = `
          <span class="frete-ok">
            📍 ${dados.logradouro ? dados.logradouro + ', ' : ''}${dados.localidade}/${dados.uf}<br>
            🚚 Frete estimado: R$ ${info.valor.toFixed(2).replace('.', ',')} — ${info.prazo}
          </span>
        `;

        enderecoDetalhes.style.display = 'flex';
        atualizarResumoPagamento();
      } catch (e) {
        if (consultaAtual !== consultaFreteId) return;
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
      const subtotal = itensCarrinho
        .filter(item => item.selecionado)
        .reduce((s, i) => s + i.preco * i.qtd, 0);
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

      let html = itensCarrinho.filter(i => i.selecionado).map(i =>
        `<p>${escaparTextoHTML(i.nome)} x${i.qtd} — R$ ${(i.preco * i.qtd).toFixed(2).replace('.', ',')}</p>`
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
        atualizarOpcoesCartaoCheckout();
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
          <p class="pix-aviso">Checkout demonstrativo: ainda não há operadora para cobrar cartões. O pedido ficará pendente e nenhum valor será cobrado.</p>
          <p>Escolha um cartão já salvo ou cadastre outro em uma nova aba. Ao voltar, a lista será atualizada.</p>
          <label for="tipoCartao">Modalidade</label>
          <select id="tipoCartao" onchange="atualizarOpcoesCartaoCheckout()">
            <option value="credito">Crédito</option>
            <option value="debito">Débito</option>
          </select>
          <label for="cartaoCheckout">Cartão salvo</label>
          <select id="cartaoCheckout"></select>
          <a href="${window.checkoutDados?.cartoesUrl || 'usuario/cartoes.php'}?voltar=checkout" target="_blank" rel="noopener noreferrer">Cadastrar outro cartão</a>
        </div>
      `;
    }

    function atualizarOpcoesCartaoCheckout() {
      const seletor = document.getElementById('cartaoCheckout');
      const modalidade = document.getElementById('tipoCartao')?.value || 'credito';
      if (!seletor) return;
      const cartoes = (window.checkoutDados?.cartoes || []).filter(cartao =>
        cartao.tipo_uso === modalidade || cartao.tipo_uso === 'ambos'
      );
      seletor.innerHTML = cartoes.length
        ? cartoes.map(cartao =>
          `<option value="${Number(cartao.id)}">${escaparTextoHTML(cartao.bandeira)} •••• ${escaparTextoHTML(cartao.ultimos_digitos)} — ${escaparTextoHTML(cartao.nome_titular)}</option>`
        ).join('')
        : '<option value="">Nenhum cartão compatível salvo</option>';
    }

    async function confirmarPedido() {
      if (!usuarioLogado) {
        alert('Você precisa entrar na sua conta para finalizar a compra.');
        return;
      }

      if (!itensCarrinho.some(item => item.selecionado)) {
        alert('Selecione pelo menos um produto para finalizar o pedido.');
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

      const itensComprados = itensCarrinho
        .filter(item => item.selecionado)
        .map(({ id, qtd }) => ({ id, qtd }));
      const metodoEl = document.querySelector('input[name="pagamento"]:checked');
      const metodo = metodoEl ? metodoEl.value : 'pix';

      if (metodo === 'cartao') {
        const tipoCartao = document.getElementById('tipoCartao')?.value || '';
        const cartaoId = Number(document.getElementById('cartaoCheckout')?.value || 0);
        if (!cartaoId) {
          alert('Cadastre ou selecione um cartão compatível antes de continuar.');
          return;
        }
        await finalizarPedido(tipoCartao, cartaoId, itensComprados);
        return;
      }

      await finalizarPedido('pix', null, itensComprados);
    }

    let enviandoPedido = false;

    async function finalizarPedido(metodo, cartaoId, itensComprados) {
      if (enviandoPedido) return;
      enviandoPedido = true;
      const botao = document.querySelector('.btn-confirmar');
      if (botao) {
        botao.disabled = true;
        botao.textContent = 'Registrando pedido...';
      }

      window.addEventListener('focus', async () => {
        if (!window.usuarioSessao || !window.cartoesApiUrl) return;
        try {
          const resposta = await fetch(window.cartoesApiUrl, { credentials: 'same-origin' });
          const dados = await resposta.json();
          if (!resposta.ok || !dados.sucesso) throw new Error(dados.erro || 'Falha ao atualizar cartões.');
          window.checkoutDados.cartoes = dados.cartoes;
          if (document.getElementById('cartaoCheckout')) atualizarOpcoesCartaoCheckout();
        } catch (erro) {
          console.error('Não foi possível atualizar os cartões salvos.', erro);
        }
      });

      if (new URLSearchParams(window.location.search).get('retorno') === 'checkout') {
        window.addEventListener('load', () => setTimeout(abrirPagamento, 0), { once: true });
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
            itens: itensComprados,
            metodo,
            cartao_id: cartaoId,
            endereco_id: enderecoSelecionadoSalvo?.id || null,
            numero: document.getElementById('numeroCasa').value.trim(),
            salvar_endereco_padrao: document.getElementById('salvarEnderecoPadrao').checked,
            cep: document.getElementById('cepInput').value.replace(/\D/g, ''),
            uf: cepEndereco?.uf || '',
            logradouro: document.getElementById('logradouroEntrega').value.trim() || cepEndereco?.logradouro || '',
            bairro: cepEndereco?.bairro || '',
            localidade: cepEndereco?.localidade || '',
            complemento: document.getElementById('complemento').value.trim(),
            cupom: cupomAtual?.codigo || ''
          })
        });
        const resultado = await resposta.json();
        if (!resposta.ok || !resultado.sucesso) {
          throw new Error(resultado.erro || 'Não foi possível registrar o pedido.');
        }

        const quantidadesCompradas = new Map(
          itensComprados.map(item => [item.id, item.qtd])
        );
        itensCarrinho = itensCarrinho.flatMap(item => {
          const quantidadeComprada = quantidadesCompradas.get(item.id) || 0;
          if (quantidadeComprada === 0) return [item];

          const quantidadeRestante = item.qtd - quantidadeComprada;
          if (quantidadeRestante <= 0) return [];
          return [{ ...item, qtd: quantidadeRestante, selecionado: false }];
        });
        atualizarCarrinho();
        persistirListasCarrinho();
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
