<?php

require_once __DIR__ . '/../includes/auth.php';

logoutUsuario();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['sucesso' => true]);
