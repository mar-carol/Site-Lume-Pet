    // LOJA - FILTROS (pesquisa em todas as categorias)


    let categoriaAtiva = 'geral';

    function filtrarCategoria(categoria, event) {
      categoriaAtiva = categoria;
      document.querySelectorAll('.categoria-btn').forEach(btn => btn.classList.remove('ativo'));
      if (event && event.currentTarget) {
        event.currentTarget.classList.add('ativo');
      }
      filtrarProdutos();
    }

    function filtrarProdutos() {
      const campoPesquisa = document.getElementById('campoPesquisa');
      if (!campoPesquisa) return;

      const texto = campoPesquisa.value.toLowerCase().trim();
      const pesquisaMenu = document.getElementById('menuPesquisa');
      if (pesquisaMenu && pesquisaMenu.value !== campoPesquisa.value) {
        pesquisaMenu.value = campoPesquisa.value;
      }
      const cards = document.querySelectorAll('.produto-card');
      const vazio = document.getElementById('lojaVazio');
      const categoriasPorFiltro = {
        higiene: ['higiene'],
        alimento: ['alimento', 'alimentos', 'comida', 'ração', 'racao'],
        brinquedo: ['brinquedo', 'brinquedos'],
        conforto: ['conforto']
      };
      let produtosVisiveis = 0;

      cards.forEach(card => {
        const categoria = card.dataset.categoria;
        const nome = card.dataset.nome.toLowerCase();
        const descricao = (card.dataset.descricao || '').toLowerCase();
        const passaPesquisa = nome.includes(texto) || descricao.includes(texto);

        let passaCategoria;
        if (texto) {
          // com texto digitado, a busca vale para TODAS as categorias
          passaCategoria = true;
        } else {
          const categoriasPermitidas = categoriasPorFiltro[categoriaAtiva] || [categoriaAtiva];
          passaCategoria = categoriaAtiva === 'geral' || categoriasPermitidas.includes(categoria);
        }

        const deveMostrar = passaCategoria && passaPesquisa;

        card.classList.toggle('oculto', !deveMostrar);
        card.classList.toggle('visivel', deveMostrar);
        card.style.display = deveMostrar ? 'flex' : 'none';

        if (deveMostrar) produtosVisiveis++;
      });

      if (vazio) {
        vazio.style.display = produtosVisiveis > 0 ? 'none' : 'flex';
      }

      document.querySelectorAll('.categoria-btn').forEach(btn => {
        btn.classList.toggle('desativado', !!texto);
      });
    }


    // Inicia mostrando o Geral
    filtrarProdutos();

    function sincronizarPesquisaMenu() {
      const pesquisaMenu = document.getElementById('menuPesquisa');
      const pesquisaLoja = document.getElementById('campoPesquisa');
      if (!pesquisaMenu || !pesquisaLoja) return;
      pesquisaLoja.value = pesquisaMenu.value;
      filtrarProdutos();
    }

    function pesquisarPeloMenu(event) {
      event.preventDefault();
      sincronizarPesquisaMenu();
      document.getElementById('loja')?.scrollIntoView({ behavior: 'smooth' });
      document.getElementById('menuHamburguer')?.classList.remove('aberto');
      document.getElementById('navMenu')?.classList.remove('aberto');
    }


    // CARROSSEL PRINCIPAL (topo do site)

    (function () {
      const slidesEl   = document.getElementById('slides');
      const dotsWrap   = document.getElementById('dots');
      const progressEl = document.getElementById('progress');

      if (!slidesEl) return; // sai se não houver carrossel na página

      const total    = document.querySelectorAll('#slides .slide').length;
      const INTERVAL = 5000; // ms entre troca automática de slide

      let current      = 0;
      let autoTimer    = null;
      let progressTimer = null;
      let progressVal  = 0;

    //  Criar dots dinamicamente 
      for (let i = 0; i < total; i++) {
        const d = document.createElement('button');
        d.className = 'dot' + (i === 0 ? ' active' : '');
        d.setAttribute('aria-label', 'Ir para slide ' + (i + 1));
        d.addEventListener('click', () => goTo(i));
        dotsWrap.appendChild(d);
      }

      // Ir para slide N 
      function goTo(n) {
        current = (n + total) % total;
        slidesEl.style.transform = 'translateX(-' + (current * 100) + '%)';
        updateDots();
        resetAuto();
      }

      // Atualizar dots 
      function updateDots() {
        dotsWrap.querySelectorAll('.dot').forEach(function (d, i) {
          d.classList.toggle('active', i === current);
        });
      }

      // Barra de progresso
      function startProgress() {
        progressVal = 0;
        clearInterval(progressTimer);
        progressTimer = setInterval(function () {
          progressVal += 100 / (INTERVAL / 100);
          if (progressVal > 100) progressVal = 100;
          progressEl.style.width = progressVal + '%';
        }, 100);
      }

      // ─ Autoplay 
      function resetAuto() {
        clearInterval(autoTimer);
        clearInterval(progressTimer);
        startProgress();
        autoTimer = setInterval(function () {
          goTo(current + 1);
        }, INTERVAL);
      }

      //Botões de seta 
      document.getElementById('prev').addEventListener('click', function () {
        goTo(current - 1);
      });
      document.getElementById('next').addEventListener('click', function () {
        goTo(current + 1);
      });

      //Pausar no hover 
      var carouselEl = document.querySelector('.carrossel');
      carouselEl.addEventListener('mouseenter', function () {
        clearInterval(autoTimer);
        clearInterval(progressTimer);
      });
      carouselEl.addEventListener('mouseleave', resetAuto);

      //Swipe (touch) — recalibrado para não conflitar com o scroll vertical
      var touchStartX = null;
      var touchStartY = null;

      slidesEl.addEventListener('touchstart', function (e) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
      }, { passive: true });

      slidesEl.addEventListener('touchend', function (e) {
        if (touchStartX === null) return;
        var dx = e.changedTouches[0].clientX - touchStartX;
        var dy = e.changedTouches[0].clientY - touchStartY;

        // só troca de slide se o movimento horizontal for
        // claramente maior que o vertical (evita bug ao rolar a página)
        if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
          goTo(current + (dx < 0 ? 1 : -1));
        }
        touchStartX = null;
        touchStartY = null;
      });

      // ── Teclado ──
      document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft')  goTo(current - 1);
        if (e.key === 'ArrowRight') goTo(current + 1);
      });

      // ── Recalcula a posição ao girar a tela / redimensionar ──
      window.addEventListener('resize', function () {
        slidesEl.style.transition = 'none';
        slidesEl.style.transform = 'translateX(-' + (current * 100) + '%)';
        requestAnimationFrame(function () {
          slidesEl.style.transition = '';
        });
      });

      // ── Iniciar ──
      resetAuto();
    })();


    // Carrossel serviços

    (function () {
    const slides = document.querySelectorAll('#servicoCarrossel .servico-slide');
    const dotsWrap = document.getElementById('servicoDots');
    let atual = 0;

    slides.forEach((_, i) => {
      const dot = document.createElement('button');
      dot.className = 'dot' + (i === 0 ? ' active' : '');
      dot.addEventListener('click', () => irParaSlide(i));
      dotsWrap.appendChild(dot);
    });

    function irParaSlide(i) {
      slides[atual].classList.remove('active');
      dotsWrap.children[atual].classList.remove('active');
      atual = i;
      slides[atual].classList.add('active');
      dotsWrap.children[atual].classList.add('active');
    }

    setInterval(() => irParaSlide((atual + 1) % slides.length), 4000);
  })();

    // CARROSSEL - ADOTE UM PET

    (function () {
      const carrossel = document.getElementById('adoteCarrossel');
      if (!carrossel) return; // sai se essa seção não existir na página

      const slides = carrossel.querySelectorAll('.adote-slide');
      const dotsWrap = document.getElementById('adoteDots');
      if (slides.length === 0 || !dotsWrap) return;

      let current = 0;

      // cria os dots dinamicamente
      slides.forEach((_, i) => {
        const dot = document.createElement('button');
        dot.className = 'dot' + (i === 0 ? ' active' : '');
        dot.setAttribute('aria-label', 'Ir para foto ' + (i + 1));
        dot.addEventListener('click', () => goTo(i));
        dotsWrap.appendChild(dot);
      });

      const dots = dotsWrap.querySelectorAll('.dot');

      function goTo(i) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = i;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
      }

      // troca automática a cada 4 segundos
      setInterval(() => goTo((current + 1) % slides.length), 4000);
    })();


    //Doação
    let metodoDoacao = 'pix';

    function selecionarValor(valor, event) {
      document.getElementById('doacao-valor').value = valor;
      document.querySelectorAll('.btn-valor').forEach(btn => btn.classList.remove('ativo'));
      if (event && event.currentTarget) {
        event.currentTarget.classList.add('ativo');
      }
    }

    function limparBotoesValor() {
      document.querySelectorAll('.btn-valor').forEach(btn => btn.classList.remove('ativo'));
    }

    function abrirPagamentoDoacao() {
      const email = document.getElementById('doacao-email').value.trim();
      const valor = document.getElementById('doacao-valor').value.trim();
      let valido = true;

      // Limpa erros
      document.getElementById('erro-email').textContent = '';
      document.getElementById('erro-valor').textContent = '';

      // Valida email
      if (!email || !email.includes('@')) {
        document.getElementById('erro-email').textContent = 'Por favor, informe um e-mail válido.';
        valido = false;
      }

      // Valida valor
      if (!valor || parseFloat(valor) <= 0) {
        document.getElementById('erro-valor').textContent = 'Por favor, informe um valor para a doação.';
        valido = false;
      }

      if (!valido) return;

      // Preenche resumo
      const nome = document.getElementById('doacao-nome').value.trim();
      document.getElementById('doacaoResumo').innerHTML = `
        ${nome ? `<p><strong>Nome:</strong> ${nome}</p>` : ''}
        <p><strong>E-mail:</strong> ${email}</p>
        <p><strong>Valor:</strong> R$ ${parseFloat(valor).toFixed(2).replace('.', ',')}</p>
      `;

      selecionarMetodo('pix');
      document.getElementById('doacaoOverlay').classList.add('aberto');
    }

    function fecharPagamentoDoacao() {
      document.getElementById('doacaoOverlay').classList.remove('aberto');
    }

    function selecionarMetodo(metodo) {
      metodoDoacao = metodo;

      document.querySelectorAll('.btn-metodo').forEach(btn => btn.classList.remove('ativo'));
      document.getElementById('btn-' + metodo).classList.add('ativo');

      document.getElementById('detalhe-pix').style.display = metodo === 'pix' ? 'flex' : 'none';
      document.getElementById('detalhe-cartao').style.display = metodo !== 'pix' ? 'flex' : 'none';

      if (metodo =='pix') {
        const valor = parseFloat(document.getElementById('doacao-valor').value);
        LumePix. renderizar (
          document.getElementById('pixDoacaoContainer'),
          valor,
          'DOA' + Date.now()          
        );
      }
    }

    function mascaraCartao(input) {
      input.value = input.value.replace(/\D/g, '').replace(/(\d{4})/g, '$1 ').trim().slice(0, 19);
    }

    function mascaraValidade(input) {
      input.value = input.value.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1/$2').slice(0, 5);
    }

    function confirmarDoacao() {
      const nomes = { pix: 'PIX', credito: 'Cartão de Crédito', debito: 'Cartão de Débito' };

      if (metodoDoacao !== 'pix') {
        const campos = document.querySelectorAll('#detalhe-cartao input');
        for (let campo of campos) {
          if (!campo.value.trim()) {
            alert('Por favor, preencha todos os dados do cartão.');
            return;
          }
        }
      }

      alert(`✅ Doação confirmada via ${nomes[metodoDoacao]}!\nObrigado pelo seu apoio! 🐾`);
      fecharPagamentoDoacao();

      // Limpa o formulário
      document.getElementById('doacao-nome').value = '';
      document.getElementById('doacao-email').value = '';
      document.getElementById('doacao-valor').value = '';
      document.querySelectorAll('.btn-valor').forEach(btn => btn.classList.remove('ativo'));
    }


    // PÁGINA DE PRODUTO

    let produtoAtual = null;
    let produtoQtd = 1;
    let posicaoScrollProduto = 0;

    const SECOES_CLASSES = ['.carrossel', '.servico', '.loja', '.adote', '.doacao', '.sobre', '.avaliacao', '.contato'];

    function abrirProduto(card) {
      const img = card.querySelector('.produto-img img');
      const nome = card.querySelector('h3').textContent.trim();
      const preco = card.querySelector('.produto-preco').textContent.trim();
      const paragrafos = Array.from(card.querySelectorAll('p')).map(p => p.textContent.trim());

      produtoAtual = {
        id: Number(card.dataset.id),
        nome,
        preco: Number(card.dataset.preco)
      };
      posicaoScrollProduto = window.scrollY;
      produtoQtd = 1;

      document.getElementById('pd-img').src = img.src;
      document.getElementById('pd-img').alt = img.alt;
      document.getElementById('pd-nome').textContent = nome;
      document.getElementById('pd-desc').textContent = paragrafos.join(' ');
      document.getElementById('pd-preco').textContent = preco;
      document.getElementById('pd-qtd').textContent = produtoQtd;

      SECOES_CLASSES.forEach(sel => {
        const el = document.querySelector(sel);
        if (el) el.style.display = 'none';
      });

      document.getElementById('produtoPagina').classList.add('aberta');
      renderAvaliacoesSecao(produtoAtual.id);
      montarFormAvaliacao(produtoAtual.id, nome);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function fecharProduto() {
      document.getElementById('produtoPagina').classList.remove('aberta');

      SECOES_CLASSES.forEach(sel => {
        const el = document.querySelector(sel);
        if (el) el.style.display = '';
      });

      window.scrollTo({ top: posicaoScrollProduto, behavior: 'smooth' });
    }

    function alterarQtdProduto(delta) {
      produtoQtd = Math.max(1, produtoQtd + delta);
      document.getElementById('pd-qtd').textContent = produtoQtd;
    }

    function adicionarProdutoDaPagina() {
      if (!produtoAtual) return;
      for (let i = 0; i < produtoQtd; i++) {
        adicionarAoCarrinho(produtoAtual.id, produtoAtual.nome, produtoAtual.preco);
      }
    }

    // abre a página do produto ao clicar no card (menos quando o clique for no botão de adicionar) ARRUMAR ISSO QUE NAO TA ABRINDO 
    const lojaGrade = document.getElementById('lojaGrade');
    if (lojaGrade) {
      lojaGrade.addEventListener('click', function (e) {
        if (e.target.closest('button')) return;
        const card = e.target.closest('.produto-card');
        if (card) abrirProduto(card);
      });
    }


    // AVALIAÇÕES DE PRODUTOS

    function renderEstrelas(nota) {
      let html = '<span class="estrelas">';
      for (let i = 1; i <= 5; i++) {
        html += i <= nota ? '★' : '☆';
      }
      html += '</span>';
      return html;
    }

    async function comprouProduto(produtoId, produtoNome) {
      if (!usuarioLogado) return false;
      try {
        const compras = await window.carregarPedidosCliente();
        return compras.some(compra =>
          compra.status !== 'cancelado' && compra.itens.some(item =>
            Number(item.produto_id) === Number(produtoId) || item.nome === produtoNome
          )
        );
      } catch (erro) {
        console.error('Não foi possível verificar as compras salvas.', erro);
        throw erro;
      }
    }

    async function renderAvaliacoesSecao(produtoId) {
      const media = document.getElementById('pd-media');
      const lista = document.getElementById('pd-lista-avaliacoes');
      media.replaceChildren();
      lista.textContent = 'Carregando avaliações...';

      try {
        const resposta = await fetch(`api/avaliacao.php?produto_id=${encodeURIComponent(produtoId)}`, {
          credentials: 'same-origin'
        });
        const resultado = await resposta.json();
        if (!resposta.ok || !resultado.sucesso) {
          throw new Error(resultado.erro || 'Não foi possível carregar as avaliações.');
        }

        const avaliacoes = resultado.avaliacoes;
        if (avaliacoes.length === 0) {
          lista.innerHTML = '<p class="pd-sem-avaliacoes">Este produto ainda não tem avaliações. Seja o primeiro a avaliar!</p>';
          return;
        }

        const somaNotas = avaliacoes.reduce((soma, avaliacao) => soma + Number(avaliacao.nota), 0);
        const mediaNota = (somaNotas / avaliacoes.length).toFixed(1);
        media.innerHTML = `${renderEstrelas(Math.round(mediaNota))} <strong>${mediaNota}</strong> (${avaliacoes.length} avaliação${avaliacoes.length > 1 ? 'ões' : ''})`;

        lista.replaceChildren();
        avaliacoes.forEach(avaliacao => {
          const item = document.createElement('article');
          item.className = 'avaliacao-item';
          item.innerHTML = renderEstrelas(Number(avaliacao.nota));

          const autor = document.createElement('strong');
          autor.textContent = avaliacao.autor;
          const comentario = document.createElement('p');
          comentario.textContent = avaliacao.comentario;
          item.append(autor, comentario);
          lista.appendChild(item);
        });
      } catch (erro) {
        media.replaceChildren();
        lista.textContent = erro.message;
      }
    }

    let notaSelecionada = 0;

    async function montarFormAvaliacao(produtoId, produtoNome) {
      const container = document.getElementById('pd-nova-avaliacao');
      notaSelecionada = 0;

      if (!usuarioLogado) {
        container.innerHTML = '<div class="pd-aviso-compra">Entre na sua conta e compre este produto para deixar sua avaliação.</div>';
        return;
      }

      container.textContent = 'Verificando seu histórico de compras...';
      try {
        if (!await comprouProduto(produtoId, produtoNome)) {
          container.innerHTML = '<div class="pd-aviso-compra">Você poderá avaliar este produto depois de comprá-lo.</div>';
          return;
        }
      } catch (erro) {
        container.textContent = 'Não foi possível verificar seu histórico de compras. Tente novamente mais tarde.';
        return;
      }

      if (!produtoAtual || Number(produtoAtual.id) !== Number(produtoId)) return;

      if (!usuarioLogado) {
        container.innerHTML = '<div class="pd-aviso-compra">Você poderá avaliar este produto depois de comprá-lo.</div>';
        return;
      }

      container.innerHTML = `
        <div class="pd-nova-avaliacao-form">
          <strong>Deixe sua avaliação</strong>
          <div class="estrelas-input" id="estrelasInput">
            <span data-valor="1">★</span>
            <span data-valor="2">★</span>
            <span data-valor="3">★</span>
            <span data-valor="4">★</span>
            <span data-valor="5">★</span>
          </div>
          <textarea id="textoAvaliacao" placeholder="Conte como foi sua experiência com o produto..."></textarea>
          <button type="button">Enviar avaliação</button>
        </div>
      `;

      container.querySelector('button').addEventListener('click', () => enviarAvaliacao(produtoId));
      document.querySelectorAll('#estrelasInput span').forEach(estrela => {
        estrela.addEventListener('click', function () {
          notaSelecionada = parseInt(this.dataset.valor);
          atualizarEstrelasInput();
        });
      });
    }

    function atualizarEstrelasInput() {
      document.querySelectorAll('#estrelasInput span').forEach(estrela => {
        estrela.classList.toggle('ativa', parseInt(estrela.dataset.valor) <= notaSelecionada);
      });
    }

    async function enviarAvaliacao(produtoId) {
      const texto = document.getElementById('textoAvaliacao').value.trim();

      if (notaSelecionada === 0) {
        alert('Selecione de 1 a 5 estrelas antes de enviar.');
        return;
      }
      if (!texto) {
        alert('Escreva um comentário sobre o produto.');
        return;
      }

      try {
        const resposta = await fetch('api/avaliacao.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken
          },
          credentials: 'same-origin',
          body: JSON.stringify({ produto_id: produtoId, nota: notaSelecionada, comentario: texto })
        });
        const resultado = await resposta.json();
        if (!resposta.ok || !resultado.sucesso) {
          throw new Error(resultado.erro || 'Não foi possível enviar a avaliação.');
        }

        document.getElementById('textoAvaliacao').value = '';
        notaSelecionada = 0;
        atualizarEstrelasInput();
        await renderAvaliacoesSecao(produtoId);
        alert('Avaliação salva na sua conta. Obrigado pelo feedback!');
      } catch (erro) {
        alert(erro.message);
      }
    }

    // MENU HAMBÚRGUER

    function toggleMenu() {
      document.getElementById('menuHamburguer').classList.toggle('aberto');
      document.getElementById('navMenu').classList.toggle('aberto');
    }

    // fecha o menu ao clicar em qualquer link
    document.querySelectorAll('#navMenu a').forEach(link => {
      link.addEventListener('click', () => {
        if (link.hash) {
          document.querySelectorAll('#navMenu a.ativo').forEach(item => item.classList.remove('ativo'));
          link.classList.add('ativo');
        }
        document.getElementById('menuHamburguer').classList.remove('aberto');
        document.getElementById('navMenu').classList.remove('aberto');
      });
    });

    function atualizarLinkAtivo() {
      const hash = window.location.hash || '#inicio';
      document.querySelectorAll('#navMenu a[href^="#"]').forEach(link => {
        link.classList.toggle('ativo', link.getAttribute('href') === hash);
      });
    }
    window.addEventListener('hashchange', atualizarLinkAtivo);
    atualizarLinkAtivo();

    // CONTATO - máscara de telefone
function mascaraTelefoneContato(input) {
  let v = input.value.replace(/\D/g, '').slice(0, 11);
  if (v.length > 10) {
    v = v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
  } else if (v.length > 5) {
    v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
  } else if (v.length > 2) {
    v = v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
  } else {
    v = v.replace(/(\d{0,2})/, '($1');
  }
  input.value = v;
}

// CONTATO - enviar formulário para o banco de dados (via PHP)
async function enviarContato() {
  const nome = document.getElementById('contato-nome').value.trim();
  const email = document.getElementById('contato-email').value.trim();
  const telefone = document.getElementById('contato-telefone').value.trim();
  const assunto = document.getElementById('contato-assunto').value;
  const mensagem = document.getElementById('contato-mensagem').value.trim();
  const erro = document.getElementById('erro-contato');
  erro.textContent = '';
  erro.style.color = '';

  if (!nome) {
    erro.textContent = 'Por favor, informe seu nome completo.';
    return;
  }
  if (telefone.replace(/\D/g, '').length < 10) {
    erro.textContent = 'Por favor, informe um número de celular/WhatsApp válido.';
    return;
  }

  const botao = document.querySelector('.btn-contato');
  if (botao) botao.disabled = true;

  try {
    const resposta = await fetch('api/contato.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ nome, email, telefone, assunto, mensagem })
    });

    const resultado = await resposta.json();

    if (resposta.ok && resultado.sucesso) {
      erro.style.color = 'green';
      erro.textContent = 'Mensagem enviada com sucesso!';
      document.getElementById('contato-nome').value = '';
      document.getElementById('contato-email').value = '';
      document.getElementById('contato-telefone').value = '';
      document.getElementById('contato-assunto').value = '';
      document.getElementById('contato-mensagem').value = '';
    } else {
      erro.textContent = resultado.erro || 'Erro ao enviar. Tente novamente.';
    }
  } catch (e) {
    erro.textContent = 'Erro de conexão. Tente novamente.';
  } finally {
    if (botao) botao.disabled = false;
  }
}

    // TOAST DE NOTIFICAÇÃO

function mostrarToast(nomeProduto) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.innerHTML = `
    <span class="toast-icone">🛒</span>
    <div class="toast-texto">
      <strong>Adicionado ao carrinho!</strong>
      <span>${nomeProduto}</span>
    </div>
  `;

  container.appendChild(toast);

  // remove o toast do DOM depois que a animação de saída termina
  setTimeout(() => {
    toast.remove();
  }, 2700);
}
