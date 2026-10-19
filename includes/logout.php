<?php
require_once __DIR__ . '/auth.php';

logoutUsuario();

header('Location: ' . BASE_URL . '/index.php');
exit;
