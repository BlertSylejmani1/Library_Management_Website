<?php
require_once __DIR__ . '/../config/config.php';

requireLogin();

$currentUser = getSessionUser();
if (($currentUser['role'] ?? ROLE_STUDENT) === ROLE_STUDENT) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$activePage = 'books';
$pageTitle = 'Books';
$pageSubtitle = 'Browse and manage the library catalogue.';

$genres = ['All', 'Software Eng.', 'CS Theory', 'Networking', 'OS', 'Databases', 'AI / ML', 'Architecture'];
$books = $GLOBALS['books'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>