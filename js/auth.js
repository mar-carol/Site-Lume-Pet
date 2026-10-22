    // LOGIN / CADASTRO
    // (armazenamento local no navegador — apenas para demonstração)

    const raizProjetoUrl = new URL('../', document.currentScript.src);
    let usuarioLogado = window.usuarioSessao || null;

   async function fazerLogin() {
    const email = document.getElementById('login-email').value.trim();
    const senha = document.getElementById('login-senha').value;
    const erro = document.getElementById('erro-login');

    erro.textContent = '';

    if (!email || !senha) {
        erro.textContent = 'Preencha e-mail e senha.';
        return;
    }

    const dados = new FormData();

    dados.append('email', email);
    dados.append('senha', senha);

    try {
        const resposta = await fetch('api/login.php', {
            method: 'POST',
            body: dados,
            credentials: 'same-origin'
        });

        const resultado = await resposta.json();

        if (!resultado.sucesso) {
            erro.textContent = resultado.erro;
            return;
        }

        usuarioLogado = resultado.usuario;
        localStorage.setItem('lumepet_usuario_logado', JSON.stringify(usuarioLogado));
        fecharLogin();
        window.location.reload();

    } catch (erroConexao) {
        erro.textContent =
            'Erro de conexão com o servidor.';
    }
}

    async function fazerCadastro() {
      const nome = document.getElementById('cad-nome').value.trim();
      const email = document.getElementById('cad-email').value.trim();
      const senha = document.getElementById('cad-senha').value;
      const senha2 = document.getElementById('cad-senha2').value;
      const aceitouPolitica = document.getElementById('cad-aceito').checked;
      const erro = document.getElementById('erro-cadastro');
      erro.textContent = '';

      if (!nome || !email || !senha || !senha2) {
        erro.textContent = 'Preencha todos os campos.';
        return;
      }
      if (!aceitouPolitica) {
        erro.textContent = 'Aceite a Política de Privacidade para continuar.';
        return;
      }
      const erroSenha = validarSenha(senha);
      if (erroSenha) {
        erro.textContent = erroSenha;
        return;
      }
      if (senha !== senha2) {
        erro.textContent = 'As senhas não coincidem.';
        return;
      }

      const dados = new FormData();
      dados.append('nome', nome);
      dados.append('email', email);
      dados.append('senha', senha);
      dados.append('senha2', senha2);

      try {
        const resposta = await fetch('api/cadastro.php', {
          method: 'POST',
          body: dados,
          credentials: 'same-origin'
        });
        const resultado = await resposta.json();

        if (!resposta.ok || !resultado.sucesso) {
          erro.textContent = resultado.erro || 'Não foi possível criar a conta.';
          return;
        }

        usuarioLogado = resultado.usuario;
        localStorage.setItem('lumepet_usuario_logado', JSON.stringify(usuarioLogado));
        window.location.reload();
      } catch (erroConexao) {
        erro.textContent = 'Erro de conexão com o servidor.';
      }
    }

    function atualizarUsuarioUI() {
      const nomeEl = document.getElementById('usuarioNome');
      const link = document.getElementById('usuarioLink');
      if (!nomeEl || !link) return;

      if (usuarioLogado) {
      nomeEl.textContent = usuarioLogado.nome.split(' ')[0];
      montarDropdownUsuario();
    } else {
      nomeEl.textContent = 'Entrar';
      link.title = 'Entrar ou cadastrar';
      document.getElementById('usuarioDropdownDesktop').innerHTML = '';
      document.getElementById('usuarioDropdownMobile').innerHTML = '';
    }
  }

  atualizarUsuarioUI();

  function toggleLogin(event) {
      event.preventDefault();
      mudarAba('login');
      document.getElementById('loginOverlay').classList.add('aberto');
    }

    function fecharLogin() {
      document.getElementById('loginOverlay').classList.remove('aberto');
      document.getElementById('erro-login').textContent = '';
      document.getElementById('erro-cadastro').textContent = '';
    }

    function mudarAba(aba) {
      document.getElementById('aba-login').classList.toggle('ativo', aba === 'login');
      document.getElementById('aba-cadastro').classList.toggle('ativo', aba === 'cadastro');
      document.getElementById('form-login').style.display = aba === 'login' ? 'flex' : 'none';
      document.getElementById('form-cadastro').style.display = aba === 'cadastro' ? 'flex' : 'none';
      document.getElementById('loginTitulo').textContent = aba === 'login' ? 'Entrar' : 'Criar conta';
    }

    // MENU SUSPENSO DO USUÁRIO  - sub menu com "Meus Pedidos", "Minha Conta" e "Sair"

    function usuarioClick(event) {
      event.preventDefault();

      if (!usuarioLogado) {
        toggleLogin(event);
        return;
      }

      montarDropdownUsuario();
      document.getElementById('usuarioDropdownDesktop').classList.toggle('aberto');
      document.getElementById('usuarioDropdownMobile').classList.toggle('aberto');
    }

    function montarDropdownUsuario() {
      const html = `
        <a href="#" onclick="abrirPedidos(event,'atuais')">Meus Pedidos</a>
        <a href="#" onclick="abrirConta(event)">Minha Conta</a>
        <a href="#" onclick="fazerLogout(event)" class="usuario-dropdown-sair">Sair</a>
      `;
      document.getElementById('usuarioDropdownDesktop').innerHTML = html;
      document.getElementById('usuarioDropdownMobile').innerHTML = html;
    }

    function fecharDropdownsUsuario() {
      document.getElementById('usuarioDropdownDesktop')?.classList.remove('aberto');
      document.getElementById('usuarioDropdownMobile')?.classList.remove('aberto');
    }

    document.addEventListener('click', function (e) {
      if (!e.target.closest('.usuario-item') && !e.target.closest('.usuario-mobile-wrap')) {
        fecharDropdownsUsuario();
      }
    });

    async function fazerLogout(event) {
      event.preventDefault();
      const confirmar = confirm('Deseja realmente sair da sua conta?');
      if (!confirmar) return;

      try {
        const resposta = await fetch('api/logout.php', {
          method: 'POST',
          credentials: 'same-origin'
        });
        const resultado = await resposta.json();

        if (!resposta.ok || !resultado.sucesso) {
          throw new Error('Falha ao encerrar a sessão.');
        }
      } catch (erroConexao) {
        alert('Não foi possível sair da conta. Tente novamente.');
        return;
      }

      usuarioLogado = null;
      localStorage.removeItem('lumepet_usuario_logado');
      window.location.replace('index.php?logout=1');
    }


    // MEUS PEDIDOS / HISTÓRICO

    function escaparHtml(valor) {
      return String(valor ?? '').replace(/[&<>"']/g, caractere => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
      })[caractere]);
    }

    function formatarDinheiro(valor) {
      return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function rotuloStatusPedido(status) {
      return ({
        pendente: 'Pendente de confirmação',
        pago: 'Pagamento confirmado',
        enviado: 'Em transporte',
        entregue: 'Entregue',
        cancelado: 'Cancelado'
      })[status] || 'Status indisponível';
    }

    function renderizarPedido(pedido) {
      const data = pedido.criado_em
        ? new Date(String(pedido.criado_em).replace(' ', 'T')).toLocaleString(
          'pt-BR',
          pedido.origem === 'historico_local' ? { dateStyle: 'short' } : {}
        )
        : 'Data indisponível';
      const itens = pedido.itens.map(item =>
        `<li>${Number(item.quantidade)}x ${escaparHtml(item.nome)} — ${formatarDinheiro(item.preco_unitario)} cada</li>`
      ).join('');
      const historicoLegado = pedido.origem === 'historico_local'
        ? '<p class="pedido-observacao">Histórico importado do navegador; pagamento não confirmado.</p>'
        : '';

      return `
        <div class="pedido-card">
          <div class="pedido-card-topo">
            <strong>Pedido #${Number(pedido.id)}</strong>
            <span>${escaparHtml(data)}</span>
          </div>
          <p class="pedido-status">Status: ${escaparHtml(rotuloStatusPedido(pedido.status))}</p>
          ${historicoLegado}
          <ul>${itens}</ul>
          <div class="pedido-detalhes">
            <span>Pagamento: ${escaparHtml(pedido.metodo_pagamento || 'Não informado')}</span>
            <span>Confirmação: ${pedido.pago_em ? escaparHtml(new Date(String(pedido.pago_em).replace(' ', 'T')).toLocaleString('pt-BR')) : 'Aguardando confirmação'}</span>
            <span>Endereço: ${escaparHtml(pedido.endereco_entrega || 'Não informado no histórico antigo')}</span>
            <span>Subtotal: ${formatarDinheiro(pedido.subtotal)}</span>
            <span>Frete: ${formatarDinheiro(pedido.frete)}</span>
            <span>Desconto: ${formatarDinheiro(pedido.desconto)}</span>
          </div>
          <div class="pedido-card-rodape">
            <span>${pedido.pago_em ? 'Pagamento confirmado' : 'Pagamento não confirmado'}</span>
            <strong>Total: ${formatarDinheiro(pedido.total)}</strong>
          </div>
        </div>
      `;
    }

    async function abrirPedidos(event) {
      event.preventDefault();
      fecharDropdownsUsuario();

      if (!usuarioLogado) {
        alert('Entre na sua conta para ver seus pedidos.');
        return;
      }
    

      const titulo = document.getElementById('pedidosTitulo');
      const lista = document.getElementById('pedidosLista');

      titulo.textContent = 'Meus Pedidos';
      lista.innerHTML = '<p class="pedidos-vazio">Carregando seus pedidos...</p>';
      document.getElementById('pedidosOverlay').classList.add('aberto');
      try {
        const pedidos = await window.carregarPedidosCliente();
        lista.innerHTML = pedidos.length
          ? pedidos.map(renderizarPedido).join('')
          : '<p class="pedidos-vazio">Você ainda não fez nenhum pedido.</p>';
      } catch (erro) {
        lista.innerHTML = `<p class="pedidos-vazio">${escaparHtml(erro.message)}</p>`;
      }
    }

    function fecharPedidos() {
      document.getElementById('pedidosOverlay').classList.remove('aberto');
    }


    // MINHA CONTA

    function abrirConta(event) {
      event.preventDefault();
      fecharDropdownsUsuario();
      if (!usuarioLogado) return;

      window.location.href = new URL('conta.php', raizProjetoUrl).href;
    }
