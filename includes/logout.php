<?php
require_once __DIR__ . '/auth.php';

logoutUsuario();

header("Location: /lume_pet/INCLUDES/login.php");
exit;
