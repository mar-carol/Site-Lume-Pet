<?php

require_once __DIR__ . '/../includes/auth.php';

logoutUsuario();

header('Location: ../index.php');
exit;