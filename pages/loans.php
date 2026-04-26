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

    <div class="table-card">
        <table class="books-table loans-table-full">
            <thead>
                <tr>
                    <th>Loan ID</th><th>Book</th><th>Member</th><th>Issued</th><th>Due Date</th><th>Status</th><th>Days</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan): ?>
                    <?php
                    $dueTimestamp = strtotime($loan['due']);
                    $daysLeft = (int) round(($dueTimestamp - strtotime('today')) / 86400);
                    ?>
                    <tr data-loan-row data-status="<?= htmlspecialchars($loan['status']) ?>">
                        <td><code class="loan-id"><?= htmlspecialchars($loan['id']) ?></code></td>
                        <td><span class="book-title-cell"><?= htmlspecialchars($loan['book']) ?></span></td>
                        <td><?= htmlspecialchars($loan['member']) ?></td>
                        <td><span class="date-cell"><?= htmlspecialchars($loan['issued']) ?></span></td>
                        <td><span class="date-cell"><?= htmlspecialchars($loan['due']) ?></span></td>
                        <td><span class="status-pill status-<?= htmlspecialchars($loan['status']) ?>"><?= ucfirst($loan['status']) ?></span></td>
                        <td>
                            <?php if ($loan['status'] === 'returned'): ?>
                                <span class="days-neutral">—</span>
                            <?php elseif ($daysLeft >= 0): ?>
                                <span class="days-ok"><?= $daysLeft ?>d left</span>
                            <?php else: ?>
                                <span class="days-overdue"><?= abs($daysLeft) ?>d late</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <?php if ($loan['status'] !== 'returned'): ?>
                                    <button class="act-btn act-return" type="button" data-toast-message="&quot;<?= htmlspecialchars($loan['book']) ?>&quot; marked as returned.">Return</button>
                                    <?php if ($loan['status'] === 'active'): ?>
                                        <button class="act-btn act-edit" type="button" data-toast-message="Loan <?= htmlspecialchars($loan['id']) ?> renewed for 14 more days.">Renew</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="returned-label">Completed</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="modal-hidden" data-loan-empty><td colspan="8" class="empty-row">No loans found.</td></tr>
            </tbody>
        </table>
    </div>
</div>
