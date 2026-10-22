(function () {
  async function requisitarPedidos(opcoes = {}) {
    const resposta = await fetch(window.pedidosApiUrl, {
      credentials: 'same-origin',
      ...opcoes,
      headers: {
        ...(opcoes.headers || {}),
        ...(opcoes.method === 'POST' ? { 'X-CSRF-Token': window.csrfToken || '' } : {})
      }
    });
    const dados = await resposta.json();
    if (!resposta.ok || !dados.sucesso) {
      throw new Error(dados.erro || 'Não foi possível carregar os pedidos.');
    }
    return dados;
  }

  async function sincronizarPedidosLegados() {
    const email = window.usuarioSessao?.email;
    if (!email) return false;

    const chave = 'lumepet_pedidos_' + email;
    let pedidos;
    try {
      pedidos = JSON.parse(localStorage.getItem(chave) || '[]');
    } catch (erro) {
      throw new Error('Não foi possível ler o histórico de pedidos salvo neste navegador.');
    }
    if (!Array.isArray(pedidos) || pedidos.length === 0) return false;

    const resposta = await requisitarPedidos({
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ acao: 'importar_historico', pedidos })
    });
    localStorage.removeItem(chave);
    return resposta.importados > 0;
  }

  async function carregarPedidosCliente() {
    await sincronizarPedidosLegados();
    const resposta = await requisitarPedidos();
    return resposta.compras;
  }

  window.sincronizarPedidosLegados = sincronizarPedidosLegados;
  window.carregarPedidosCliente = carregarPedidosCliente;
})();
