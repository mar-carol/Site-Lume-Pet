<?php
/* Dependência de autenticação */
require_once __DIR__ . '/auth.php';

/* Encerra a sessão do usuário */
logoutUsuario();

/* Redireciona para a página inicial */
header('Location: ' . BASE_URL . '/index.php');
exit; 