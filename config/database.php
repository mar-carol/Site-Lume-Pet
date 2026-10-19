<?php

if (!defined('BASE_URL')) {
    $projectRoot = realpath(dirname(__DIR__));
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
        ? realpath($_SERVER['DOCUMENT_ROOT'])
        : false;

    if ($projectRoot !== false && $documentRoot !== false) {
        $normalizedProjectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');
        $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $documentRootPrefix = $normalizedDocumentRoot . '/';

        if (stripos($normalizedProjectRoot, $documentRootPrefix) === 0) {
            $baseUrl = substr($normalizedProjectRoot, strlen($normalizedDocumentRoot));
        } elseif (strcasecmp($normalizedProjectRoot, $normalizedDocumentRoot) === 0) {
            $baseUrl = '';
        } else {
            $baseUrl = '/' . basename($normalizedProjectRoot);
        }
    } else {
        $baseUrl = '/' . basename(dirname(__DIR__));
    }

    define('BASE_URL', rtrim($baseUrl, '/'));
}

define('DB_HOST', getenv('LUME_PET_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('LUME_PET_DB_NAME') ?: 'lume_pet');
define('DB_USER', getenv('LUME_PET_DB_USER') ?: 'root');
define('DB_PASS', getenv('LUME_PET_DB_PASS') ?: '');
define('DB_CHARSET', getenv('LUME_PET_DB_CHARSET') ?: 'utf8mb4');

function getConexao(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST .
               ";dbname=" . DB_NAME .
               ";charset=" . DB_CHARSET;

        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                $opcoes
            );
        } catch (PDOException $e) {
            error_log(
                'Erro de conexão com o banco: ' . $e->getMessage()
            );

            die(
                'Não foi possível conectar ao banco de dados.'
            );
        }
    }

    return $pdo;
}