<?php

function gerarCodigoVerificacaoEmail(): string
{
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $codigo = '';
    for ($i = 0; $i < 6; $i++) {
        $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $codigo;
}

function enviarEmailResend(string $destinatario, string $assunto, string $mensagem): bool
{
    $chaveApi = getenv('LUME_PET_RESEND_API_KEY') ?: '';
    $remetente = getenv('LUME_PET_MAIL_FROM') ?: '';
    if ($chaveApi === '' || !filter_var($remetente, FILTER_VALIDATE_EMAIL)) {
        error_log('Envio de e-mail indisponível: configure LUME_PET_RESEND_API_KEY e LUME_PET_MAIL_FROM.');
        return false;
    }

    if (!function_exists('curl_init')) {
        error_log('Envio de e-mail indisponível: a extensão cURL do PHP não está habilitada.');
        return false;
    }

    $dados = json_encode([
        'from' => 'Lume Pet <' . $remetente . '>',
        'to' => [$destinatario],
        'subject' => $assunto,
        'text' => $mensagem,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

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

    $resposta = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($curl);
    curl_close($curl);

    if ($resposta === false || $status < 200 || $status >= 300) {
        error_log('Falha no envio de e-mail pela Resend. HTTP ' . $status
            . ($erroCurl !== '' ? '; cURL: ' . $erroCurl : '') . '.');
        return false;
    }

    return true;
}
