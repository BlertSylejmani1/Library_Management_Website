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
<div class="books-page loans-page">
    <div class="loans-summary">
        <div class="loan-summary-card lsc-active" data-loan-filter-trigger="active">
            <span class="lsc-icon">🔄</span>
            <span class="lsc-val"><?= $counts['active'] ?></span>
            <span class="lsc-label">Active</span>
        </div>
        <div class="loan-summary-card lsc-overdue" data-loan-filter-trigger="overdue">
            <span class="lsc-icon">⚠️</span>
            <span class="lsc-val"><?= $counts['overdue'] ?></span>
            <span class="lsc-label">Overdue</span>
        </div>
        <div class="loan-summary-card lsc-returned" data-loan-filter-trigger="returned">
            <span class="lsc-icon">✅</span>
            <span class="lsc-val"><?= $counts['returned'] ?></span>
            <span class="lsc-label">Returned</span>
        </div>
    </div>

    <div class="page-header" style="margin-bottom: 1rem;">
        <div class="genre-tabs">
            <?php foreach (['all' => 'All', 'active' => 'Active', 'overdue' => 'Overdue', 'returned' => 'Returned'] as $key => $label): ?>
                <button class="genre-tab <?= $key === 'all' ? 'active' : '' ?>" type="button" data-loan-filter="<?= htmlspecialchars($key) ?>">
                    <?= htmlspecialchars($label) ?><?= $key !== 'all' ? ' (' . ($counts[$key] ?? 0) . ')' : '' ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="page-header-right" style="margin-left: auto;">
            <button class="add-btn" type="button" data-modal-open="newLoanModal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                New Loan
            </button>
        </div>
    </div>
