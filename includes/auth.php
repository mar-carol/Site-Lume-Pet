<?php
/**
 * Autenticação: login, logout, sessão e controle de acesso.
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/**
 * Autentica um usuário pelo e-mail e senha.
 * Retorna os dados do usuário (sem a senha) em caso de sucesso, ou null.
 */
function autenticarUsuario(string $email, string $senha): ?array
{
    $pdo = getConexao();

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email AND status = "ativo" LIMIT 1');
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        return null;
    }

    unset($usuario['senha_hash']);
    return $usuario;
}

/**
 * Cria a sessão do usuário logado (regenera o ID de sessão para evitar fixation).
 */
function iniciarSessaoUsuario(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id']   = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
}

/**
 * Encerra a sessão (logout).
 */
function logoutUsuario(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function usuarioEstaLogado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function usuarioEhAdmin(): bool
{
    return usuarioEstaLogado() && ($_SESSION['usuario_tipo'] ?? '') === 'admin';
}

/** Bloqueia a página se o usuário não estiver logado. */
function exigirLogin(): void
{
    if (!usuarioEstaLogado()) {
        header('Location: /auth/login.php');
        exit;
    }
}

/** Bloqueia a página se o usuário não for admin. */
function exigirAdmin(): void
{
    exigirLogin();
    if (!usuarioEhAdmin()) {
        http_response_code(403);
        die('Acesso restrito a administradores.');
    }
}