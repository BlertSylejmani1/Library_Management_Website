<?php
require_once __DIR__ . '/../config/config.php';

requireAdmin();

$activePage = 'loans';
$pageTitle = 'Loans';
$pageSubtitle = 'Track all active and past book loans.';
$loans = $GLOBALS['loans'];
$counts = [
    'active' => count(array_filter($loans, fn ($loan) => $loan['status'] === 'active')),
    'overdue' => count(array_filter($loans, fn ($loan) => $loan['status'] === 'overdue')),
    'returned' => count(array_filter($loans, fn ($loan) => $loan['status'] === 'returned')),
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
