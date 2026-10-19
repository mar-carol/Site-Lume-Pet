const campoCepPerfil = document.getElementById('perfil-cep');
const botaoBuscarCep = document.getElementById('buscar-cep');
const statusCepPerfil = document.getElementById('perfil-cep-status');

if (campoCepPerfil && botaoBuscarCep && statusCepPerfil) {
  campoCepPerfil.addEventListener('input', () => {
    const numeros = campoCepPerfil.value.replace(/\D/g, '').slice(0, 8);
    campoCepPerfil.value = numeros.length > 5
      ? `${numeros.slice(0, 5)}-${numeros.slice(5)}`
      : numeros;
  });

  botaoBuscarCep.addEventListener('click', async () => {
    const cep = campoCepPerfil.value.replace(/\D/g, '');

    if (cep.length !== 8) {
      statusCepPerfil.textContent = 'Digite um CEP válido com 8 números.';
      statusCepPerfil.dataset.estado = 'erro';
      campoCepPerfil.focus();
      return;
    }

    botaoBuscarCep.disabled = true;
    campoCepPerfil.disabled = true;
    statusCepPerfil.textContent = 'Consultando endereço...';
    statusCepPerfil.dataset.estado = 'carregando';

    try {
      const resposta = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      if (!resposta.ok) {
        throw new Error('A consulta de CEP não está disponível no momento.');
      }

      const endereco = await resposta.json();
      if (endereco.erro) {
        statusCepPerfil.textContent = 'CEP não encontrado. Confira os números e tente novamente.';
        statusCepPerfil.dataset.estado = 'erro';
        return;
      }

      const campoEndereco = document.getElementById('perfil-endereco');
      const campoCidade = document.getElementById('perfil-cidade');
      const campoEstado = document.getElementById('perfil-estado');

      if (endereco.logradouro) campoEndereco.value = endereco.logradouro;
      if (endereco.localidade) campoCidade.value = endereco.localidade;
      if (endereco.uf) campoEstado.value = endereco.uf;

      statusCepPerfil.textContent = 'Endereço encontrado. Confira os dados e salve seu perfil.';
      statusCepPerfil.dataset.estado = 'sucesso';
    } catch (erro) {
      statusCepPerfil.textContent = erro instanceof Error
        ? erro.message
        : 'Não foi possível consultar o CEP. Tente novamente.';
      statusCepPerfil.dataset.estado = 'erro';
    } finally {
      botaoBuscarCep.disabled = false;
      campoCepPerfil.disabled = false;
    }
  });
}
