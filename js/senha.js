//==== regras para criar senha ====

const SENHA_ESPECIAIS_PERMITIDOS = '!@#$%&*?^_';  // caracteres permitidos na senha 

function temNumerosSeguidos(senha) {
  for (let i = 0; i < senha.length - 2; i++) {
    const a = senha.charCodeAt(i);
    const b = senha.charCodeAt(i + 1);
    const c = senha.charCodeAt(i + 2);
    const saoDigitos = [a, b, c].every(x => x >= 48 && x <= 57);
    if (!saoDigitos) continue;

    const crescente = b - a === 1 && c - b === 1;
    const decrescente = a - b === 1 && b - c === 1;
    const repetido = a === b && b === c;
    if (crescente || decrescente || repetido) return true;
  }
  return false;
}

function validarSenha(senha) {
  if (senha.length < 8) return 'A senha deve ter no mínimo 8 caracteres.';
  if (/\s/.test(senha)) return 'A senha não pode conter espaços.';
  if (/[^\x21-\x7E]/.test(senha)) return 'A senha não pode conter acentos.';
  if (/[.,(){}\[\]|\\\/<>:~\-+=;'"]/.test(senha)) {
    return 'A senha contém caracteres não permitidos. Use apenas: ' + SENHA_ESPECIAIS_PERMITIDOS;
  }
  if (!/[A-Z]/.test(senha)) return 'A senha deve ter pelo menos 1 letra maiúscula.';
  if (!/[a-z]/.test(senha)) return 'A senha deve ter pelo menos 1 letra minúscula.';
  if (!/\d/.test(senha)) return 'A senha deve ter pelo menos 1 número.';
  if (!/[!@#$%&*?^_]/.test(senha)) {
    return 'A senha deve ter pelo menos 1 caractere especial (' + SENHA_ESPECIAIS_PERMITIDOS + ').';
  }
  if (temNumerosSeguidos(senha)) return 'A senha não pode ter números seguidos (ex: 123, 321, 111).';
  return null;
}