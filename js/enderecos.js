document.addEventListener('DOMContentLoaded', () => {
  const cep = document.getElementById('endereco-cep-novo');
  const buscar = document.getElementById('buscar-cep-novo');
  const status = document.getElementById('endereco-cep-status');
  if (!cep || !buscar || !status) return;

  cep.addEventListener('input', () => {
    const numeros = cep.value.replace(/\D/g, '').slice(0, 8);
    cep.value = numeros.length > 5
      ? `${numeros.slice(0, 5)}-${numeros.slice(5)}`
      : numeros;
  });

  buscar.addEventListener('click', async () => {
    const numeros = cep.value.replace(/\D/g, '');
    if (numeros.length !== 8) {
      status.textContent = 'Digite um CEP válido com 8 números.';
      cep.focus();
      return;
    }

    buscar.disabled = true;
    status.textContent = 'Consultando CEP...';
    try {
      const resposta = await fetch(`https://viacep.com.br/ws/${numeros}/json/`);
      if (!resposta.ok) throw new Error('O serviço de consulta de CEP não está disponível.');
      const endereco = await resposta.json();
      if (endereco.erro) throw new Error('CEP não encontrado. Confira os números.');

      document.getElementById('endereco-logradouro-novo').value = endereco.logradouro || '';
      document.getElementById('endereco-bairro-novo').value = endereco.bairro || '';
      document.getElementById('endereco-cidade-nova').value = endereco.localidade || '';
      document.getElementById('endereco-estado-novo').value = endereco.uf || '';
      status.textContent = 'CEP encontrado. Confira os dados e informe o número.';
    } catch (erro) {
      status.textContent = erro instanceof Error ? erro.message : 'Não foi possível consultar o CEP.';
    } finally {
      buscar.disabled = false;
    }
  });
});
