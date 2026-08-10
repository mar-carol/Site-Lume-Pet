// LOGIN / CADASTRO
// (armazenamento local no navegador — apenas para demonstração)

let usuarioLogado = null;

try {
  usuarioLogado = JSON.parse(localStorage.getItem('lumepet_usuario_logado') || 'null');
} catch (e) {
  usuarioLogado = null;
}

function getUsuarios() {
  try {
    return JSON.parse(localStorage.getItem('lumepet_usuarios') || '[]');
  } catch (e) {
    return [];
  }
}

function salvarUsuarios(lista) {
  localStorage.setItem('lumepet_usuarios', JSON.stringify(lista));
}

function atualizarUsuarioUI() {
  const nomeEl = document.getElementById('usuarioNome');
  const link = document.getElementById('usuarioLink');
  if (!nomeEl || !link) return;

  if (usuarioLogado) {
    nomeEl.textContent = usuarioLogado.nome.split(' ')[0];
    link.title = 'Clique para sair da conta';
  } else {
    nomeEl.textContent = 'Entrar';
    link.title = 'Entrar ou cadastrar';
  }
}

function toggleLogin(event) {
  event.preventDefault();

  if (usuarioLogado) {
    const sair = confirm(`Olá, ${usuarioLogado.nome}! Deseja sair da sua conta?`);
    if (sair) {
      usuarioLogado = null;
      localStorage.removeItem('lumepet_usuario_logado');
      atualizarUsuarioUI();
    }
    return;
  }

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

function fazerLogin() {
  const email = document.getElementById('login-email').value.trim().toLowerCase();
  const senha = document.getElementById('login-senha').value;
  const erro = document.getElementById('erro-login');
  erro.textContent = '';

  if (!email || !senha) {
    erro.textContent = 'Preencha e-mail e senha.';
    return;
  }

  const usuarios = getUsuarios();
  const usuario = usuarios.find(u => u.email === email && u.senha === senha);

  if (!usuario) {
    erro.textContent = 'E-mail ou senha incorretos.';
    return;
  }

  usuarioLogado = { nome: usuario.nome, email: usuario.email };
  localStorage.setItem('lumepet_usuario_logado', JSON.stringify(usuarioLogado));
  atualizarUsuarioUI();
  fecharLogin();

  document.getElementById('login-email').value = '';
  document.getElementById('login-senha').value = '';
}

function fazerCadastro() {
  const nome = document.getElementById('cad-nome').value.trim();
  const email = document.getElementById('cad-email').value.trim().toLowerCase();
  const senha = document.getElementById('cad-senha').value;
  const senha2 = document.getElementById('cad-senha2').value;
  const erro = document.getElementById('erro-cadastro');
  erro.textContent = '';

  if (!nome || !email || !senha || !senha2) {
    erro.textContent = 'Preencha todos os campos.';
    return;
  }
  if (!email.includes('@') || !email.includes('.')) {
    erro.textContent = 'Informe um e-mail válido.';
    return;
  }
  if (senha.length < 6) {
    erro.textContent = 'A senha deve ter no mínimo 6 caracteres.';
    return;
  }
  if (senha !== senha2) {
    erro.textContent = 'As senhas não coincidem.';
    return;
  }

  const usuarios = getUsuarios();
  if (usuarios.find(u => u.email === email)) {
    erro.textContent = 'Já existe uma conta com este e-mail.';
    return;
  }

  usuarios.push({ nome, email, senha });
  salvarUsuarios(usuarios);

  usuarioLogado = { nome, email };
  localStorage.setItem('lumepet_usuario_logado', JSON.stringify(usuarioLogado));
  atualizarUsuarioUI();
  fecharLogin();

  document.getElementById('cad-nome').value = '';
  document.getElementById('cad-email').value = '';
  document.getElementById('cad-senha').value = '';
  document.getElementById('cad-senha2').value = '';
}

document.addEventListener('DOMContentLoaded', atualizarUsuarioUI);



// CARRINHO


let itensCarrinho = [];

function toggleCarrinho(event) {
  event.preventDefault();
  const painel = document.getElementById('carrinhoPainel');
  painel.style.display = painel.style.display === 'block' ? 'none' : 'block';
}

// O carrinho só fecha quando o usuário clicar para fechar

function adicionarAoCarrinho(nome, preco) {
  const precoNum = parseFloat(preco.replace(',', '.'));
  const existente = itensCarrinho.find(i => i.nome === nome);

  if (existente) {
    existente.qtd++;
  } else {
    itensCarrinho.push({ nome, preco: precoNum, qtd: 1 });
  }

  atualizarCarrinho();
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

function atualizarResumoPagamento() {
  const resumo = document.getElementById('pagamentoResumo');
  if (!resumo) return;

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
    alert(`✅ Pedido confirmado via ${nomeTipo}!\nObrigado pela compra! 🐾`);
    finalizarPedido();
    return;
  }

  // PIX
  alert('✅ Pagamento via PIX confirmado!\nObrigado pela compra! 🐾');
  finalizarPedido();
}

function finalizarPedido() {
  // registra os produtos comprados para liberar avaliação
  if (usuarioLogado) {
    const chave = 'lumepet_compras_' + usuarioLogado.email;
    const compras = JSON.parse(localStorage.getItem(chave) || '[]');
    itensCarrinho.forEach(item => {
      if (!compras.includes(item.nome)) compras.push(item.nome);
    });
    localStorage.setItem(chave, JSON.stringify(compras));
  }

  pararTimerPix();
  esvaziarCarrinho();
  fecharPagamento();
  document.getElementById('numeroCasa').value = '';
  document.getElementById('complemento').value = '';
}


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
  const texto = campoPesquisa.value.toLowerCase().trim();
  const cards = document.querySelectorAll('.produto-card');
  const vazio = document.getElementById('lojaVazio');
  const geralProdutos = ['shampoo natural', 'ração premium', 'bolinha de borracha', 'cama pet'];
  let produtosVisiveis = 0;

  cards.forEach(card => {
    const categoria = card.dataset.categoria;
    const nome = card.dataset.nome.toLowerCase();
    const passaPesquisa = nome.includes(texto);

    let passaCategoria;
    if (texto) {
      // com texto digitado, a busca vale para TODAS as categorias
      passaCategoria = true;
    } else {
      passaCategoria = categoriaAtiva === 'geral'
        ? geralProdutos.includes(nome)
        : categoria === categoriaAtiva;
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

const SECOES_CLASSES = ['.carrossel', '.servico', '.loja', '.adote', '.doacao', '.sobre', '.avaliacao', '.contato'];

function abrirProduto(card) {
  const img = card.querySelector('.produto-img img');
  const nome = card.querySelector('h3').textContent.trim();
  const preco = card.querySelector('.produto-preco').textContent.trim();
  const paragrafos = Array.from(card.querySelectorAll('p')).map(p => p.textContent.trim());

  produtoAtual = { nome, preco: preco.replace('R$', '').trim() };
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
  renderAvaliacoesSecao(nome);
  montarFormAvaliacao(nome);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function fecharProduto() {
  document.getElementById('produtoPagina').classList.remove('aberta');

  SECOES_CLASSES.forEach(sel => {
    const el = document.querySelector(sel);
    if (el) el.style.display = '';
  });

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function alterarQtdProduto(delta) {
  produtoQtd = Math.max(1, produtoQtd + delta);
  document.getElementById('pd-qtd').textContent = produtoQtd;
}

function adicionarProdutoDaPagina() {
  if (!produtoAtual) return;
  for (let i = 0; i < produtoQtd; i++) {
    adicionarAoCarrinho(produtoAtual.nome, produtoAtual.preco);
  }
}

// abre a página do produto ao clicar no card (menos quando o clique for no botão de adicionar)
document.getElementById('lojaGrade').addEventListener('click', function (e) {
  if (e.target.closest('button')) return;
  const card = e.target.closest('.produto-card');
  if (card) abrirProduto(card);
});


// AVALIAÇÕES DE PRODUTOS

function getAvaliacoes(produtoNome) {
  try {
    const todas = JSON.parse(localStorage.getItem('lumepet_avaliacoes') || '{}');
    return todas[produtoNome] || [];
  } catch (e) {
    return [];
  }
}

function salvarAvaliacao(produtoNome, avaliacao) {
  const todas = JSON.parse(localStorage.getItem('lumepet_avaliacoes') || '{}');
  if (!todas[produtoNome]) todas[produtoNome] = [];
  todas[produtoNome].push(avaliacao);
  localStorage.setItem('lumepet_avaliacoes', JSON.stringify(todas));
}

function comprouProduto(produtoNome) {
  if (!usuarioLogado) return false;
  try {
    const compras = JSON.parse(localStorage.getItem('lumepet_compras_' + usuarioLogado.email) || '[]');
    return compras.includes(produtoNome);
  } catch (e) {
    return false;
  }
}

function renderEstrelas(nota) {
  let html = '<span class="estrelas">';
  for (let i = 1; i <= 5; i++) {
    html += i <= nota ? '★' : '☆';
  }
  html += '</span>';
  return html;
}

function renderAvaliacoesSecao(produtoNome) {
  const avaliacoes = getAvaliacoes(produtoNome);
  const media = document.getElementById('pd-media');
  const lista = document.getElementById('pd-lista-avaliacoes');

  if (avaliacoes.length === 0) {
    media.innerHTML = '';
    lista.innerHTML = '<p class="pd-sem-avaliacoes">Este produto ainda não tem avaliações. Seja o primeiro a avaliar!</p>';
    return;
  }

  const somaNotas = avaliacoes.reduce((s, a) => s + a.nota, 0);
  const mediaNota = (somaNotas / avaliacoes.length).toFixed(1);

  media.innerHTML = `${renderEstrelas(Math.round(mediaNota))} <strong>${mediaNota}</strong> (${avaliacoes.length} avaliação${avaliacoes.length > 1 ? 'ões' : ''})`;

  lista.innerHTML = avaliacoes.map(a => `
    <div class="avaliacao-item">
      ${renderEstrelas(a.nota)}
      <strong>${a.autor}</strong>
      <p>${a.texto}</p>
    </div>
  `).join('');
}

let notaSelecionada = 0;

function montarFormAvaliacao(produtoNome) {
  const container = document.getElementById('pd-nova-avaliacao');
  notaSelecionada = 0;

  if (!usuarioLogado) {
    container.innerHTML = `<div class="pd-aviso-compra">Entre na sua conta e compre este produto para deixar sua avaliação.</div>`;
    return;
  }

  if (!comprouProduto(produtoNome)) {
    container.innerHTML = `<div class="pd-aviso-compra">Você poderá avaliar este produto depois de comprá-lo.</div>`;
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
      <button onclick="enviarAvaliacao('${produtoNome.replace(/'/g, "\\'")}')">Enviar avaliação</button>
    </div>
  `;

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

function enviarAvaliacao(produtoNome) {
  const texto = document.getElementById('textoAvaliacao').value.trim();

  if (notaSelecionada === 0) {
    alert('Selecione de 1 a 5 estrelas antes de enviar.');
    return;
  }
  if (!texto) {
    alert('Escreva um comentário sobre o produto.');
    return;
  }

  salvarAvaliacao(produtoNome, {
    nota: notaSelecionada,
    texto,
    autor: usuarioLogado.nome
  });

  alert('✅ Avaliação enviada, obrigado pelo feedback!');
  renderAvaliacoesSecao(produtoNome);
  montarFormAvaliacao(produtoNome);
}

// MENU HAMBÚRGUER

function toggleMenu() {
  document.getElementById('menuHamburguer').classList.toggle('aberto');
  document.getElementById('navMenu').classList.toggle('aberto');
}

// fecha o menu ao clicar em qualquer link
document.querySelectorAll('#navMenu a').forEach(link => {
  link.addEventListener('click', () => {
    document.getElementById('menuHamburguer').classList.remove('aberto');
    document.getElementById('navMenu').classList.remove('aberto');
  });
});