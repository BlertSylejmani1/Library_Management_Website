<?php

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect('pages/dashboard.php');
}

redirect('login.php');

