
<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

header('Location: ' . BASE_URL . '/login.php');
exit;

