<?php
// Gerar código de verificação de e-mail (6 caracteres) usado no cadastro e na recuperação de senha
function gerarCodigoVerificacaoEmail(): string
{
    // Letras maiúsculas e números permitidos no código
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    // Código final, montado caractere por caractere
    $codigo = '';
    // Sorteio seguro dos 6 caracteres do código
    for ($i = 0; $i < 6; $i++) {
        $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $codigo;
}

// Enviar e-mail pela API da Resend (código de verificação e demais avisos da Lume Pet)
function enviarEmailResend(string $destinatario, string $assunto, string $mensagem): bool
{
    // Chave da API da Resend e e-mail remetente, lidos das variáveis de ambiente do servidor
    $chaveApi = getenv('LUME_PET_RESEND_API_KEY') ?: '';
    $remetente = getenv('LUME_PET_MAIL_FROM') ?: '';
    // Checar configuração: cancela o envio se a chave estiver vazia ou o remetente for inválido
    if ($chaveApi === '' || !filter_var($remetente, FILTER_VALIDATE_EMAIL)) {
        error_log('Envio de e-mail indisponível: configure LUME_PET_RESEND_API_KEY e LUME_PET_MAIL_FROM.');
        return false;
    }

    // Checar extensão cURL: necessária para chamar a API (precisa estar habilitada no PHP/XAMPP)
    if (!function_exists('curl_init')) {
        error_log('Envio de e-mail indisponível: a extensão cURL do PHP não está habilitada.');
        return false;
    }

    // Dados do e-mail em JSON: remetente, destinatário, assunto e mensagem em texto simples
    $dados = json_encode([
        'from' => 'Lume Pet <' . $remetente . '>',
        'to' => [$destinatario],
        'subject' => $assunto,
        'text' => $mensagem,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    // Requisição POST para a Resend: endpoint, autenticação, corpo e limites de tempo
    $curl = curl_init('https://api.resend.com/emails');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $dados,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $chaveApi,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);

    // Resultado do envio: resposta da Resend, código HTTP e erro de conexão (se houver)
    $resposta = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($curl);
    curl_close($curl);

    // Registro de falhas no log do servidor: sem resposta ou status HTTP diferente de 2xx
    if ($resposta === false || $status < 200 || $status >= 300) {
        error_log('Falha no envio de e-mail pela Resend. HTTP ' . $status
            . ($erroCurl !== '' ? '; cURL: ' . $erroCurl : '') . '.');
        return false;
    }

    // Envio concluído e aceito pela Resend
    return true; 
    }