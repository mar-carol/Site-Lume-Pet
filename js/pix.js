const LumePix = (() => {

    // ====== Dados do pix ======
    const CONFIG = {
        chave: 'lumepet@gmail.com',
        nome: 'LUME PET',
        cidade: 'OSASCO'
    };
    
    // ====== funções do pix =====
    function campo(id, valor) { // Organiza os campos no formato exigido pelo Pix
        return id + String(valor.length).padStart(2, '0') + valor;
    }

    function limpar(texto, max) { // Remove acentos e caracteres não permitidos
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^A-Za-z0-9 ]/g, '').toUpperCase().substring(0, max).trim();
    }

    function crc16(payload) { // Calcula o código de verificação CRC16
        let crc = 0xFFFF;
        for (let i = 0; i < payload.length; i++) {
            crc ^= payload.charCodeAt(i) << 8;
            for (let j = 0; j < 8; j++) {
                crc = (crc & 0x8000) ? ((crc << 1) ^ 0x1021) : (crc << 1);
                crc &= 0xFFFF;
            }
        }
        return crc.toString(16).toUpperCase().padStart(4, '0');
    }

    function gerarPayload(valor, txid) {  // Monta os dados necessários para gerar o código Pix
        const conta = campo('00', 'br.gov.bcb.pix') + campo('01', CONFIG.chave); // Organiza a chave Pix e o identificador da transação
        const id = txid ? String(txid).replace(/[^A-Za-z0-9]/g, '').substring(0, 25) : '';

        let payload = campo('00', '01') + campo('26', conta) + // Define os campos de identificação e moeda
                      campo('52', '0000') + campo('53', '986');

        const v = Number(valor); // Adiciona o valor e os dados do recebedor    
        if (v > 0) payload += campo('54', v.toFixed(2));

        payload += campo('58', 'BR') +
                   campo('59', limpar(CONFIG.nome, 25)) +
                   campo('60', limpar(CONFIG.cidade, 15)) +
                   campo('62', campo('05', id || '***'));

        payload += '6304'; // Acrescenta o código de verificação CRC16
        return payload + crc16(payload);
    }

    function renderizar(container, valor, txid) { // Gera e apresenta o código QR do Pix
        if (!container) return '';
        const payload = gerarPayload(valor, txid);
        container.innerHTML = '';

        const qr = document.createElement('div');
        qr.className = 'pix-qr';
        container.appendChild(qr);
        if (typeof QRCode !== 'undefined') {
            new QRCode(qr, { text: payload, width: 200, height: 200 });
        }

        const aviso = document.createElement('small'); // Exibe uma orientação para copiar o código Pix
        aviso.textContent = 'Ou copie o código Pix Copia e Cola:';
        container.appendChild(aviso);

        const input = document.createElement('input'); // Cria o campo para exibir o código Pix Copia e Cola
        input.type = 'text';
        input.readOnly = true;
        input.value = payload;
        input.onclick = () => input.select();
        container.appendChild(input);

        const botao = document.createElement('button'); // Cria o botão para copiar o código Pix 
        botao.type = 'button';
        botao.className = 'btn-primary';
        botao.textContent = 'Copiar código Pix';
        botao.onclick = async () => {  // Copia o código Pix e atualiza a mensagem do botão
            try { await navigator.clipboard.writeText(payload); }
            catch (e) { input.select(); document.execCommand('copy'); }
            botao.textContent = 'Código copiado!';
            setTimeout(() => botao.textContent = 'Copiar código Pix', 2000);
        };
        container.appendChild(botao);

        return payload;
    }

    return { gerarPayload, renderizar };  // Disponibiliza as funções para outras partes do sistema
})();
