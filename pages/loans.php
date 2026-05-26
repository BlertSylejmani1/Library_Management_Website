<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/BookRepository.php';
require_once __DIR__ . '/../classes/LoanRepository.php';
require_once __DIR__ . '/../classes/UserRepository.php';

requireAdmin();

$activePage = 'loans';
$pageTitle = 'Loans';
$pageSubtitle = 'Track all active and past book loans.';
$flash = pullFlash('loans');
$pageError = '';

try {
    $db = Database::connection();
    $loanRepository = new LoanRepository($db);
    $bookRepository = new BookRepository($db);
    $userRepository = new UserRepository($db);

    if (isPost()) {
        try {
            verify_csrf_or_fail();

            $userId = (int) ($_POST['user_id'] ?? 0);
            $bookId = (int) ($_POST['book_id'] ?? 0);
            $duration = max(1, min(60, (int) ($_POST['duration_days'] ?? 14)));
            $notes = trim($_POST['notes'] ?? '');
            $issuedAt = date('Y-m-d');
            $dueAt = date('Y-m-d', strtotime('+' . $duration . ' days'));
            $status = $dueAt < date('Y-m-d') ? 'overdue' : 'active';

            $loanRepository->create([
                'user_id' => $userId,
                'book_id' => $bookId,
                'issued_at' => $issuedAt,
                'due_at' => $dueAt,
                'status' => $status,
                'notes' => $notes,
                'created_by' => (int) ($_SESSION['user']['id'] ?? 0),
            ]);

            flash('loans', 'Loan created successfully.');
        } catch (Throwable $exception) {
            flash('loans', $exception->getMessage(), 'error');
        }

        redirect('pages/loans.php');
    }

    $loans = $loanRepository->all();
    $counts = $loanRepository->counts();
    $members = $userRepository->all(ROLE_STUDENT);
    $books = $bookRepository->availableForLoans();
} catch (Throwable $exception) {
    $loans = [];
    $counts = ['active' => 0, 'overdue' => 0, 'returned' => 0];
    $members = [];
    $books = [];
    $pageError = $exception->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="books-page loans-page">
    <?php if ($flash): ?>
        <div class="page-alert alert-<?= h($flash['type']) ?>" style="position: fixed; top: 90px; right: 1.75rem; z-index: 999;">
            <?= h($flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($pageError !== ''): ?>
        <div class="page-alert alert-error" style="position: fixed; top: 90px; right: 1.75rem; z-index: 999;">
            <?= h($pageError) ?>
        </div>
    <?php endif; ?>

    <div class="loans-summary">
        <div class="loan-summary-card lsc-active" data-loan-filter-trigger="active">
            <span class="lsc-icon">Active</span>
            <span class="lsc-val" data-loan-count="active"><?= (int) ($counts['active'] ?? 0) ?></span>
            <span class="lsc-label">Active</span>
        </div>
        <div class="loan-summary-card lsc-overdue" data-loan-filter-trigger="overdue">
            <span class="lsc-icon">Due</span>
            <span class="lsc-val" data-loan-count="overdue"><?= (int) ($counts['overdue'] ?? 0) ?></span>
            <span class="lsc-label">Overdue</span>
        </div>
        <div class="loan-summary-card lsc-returned" data-loan-filter-trigger="returned">
            <span class="lsc-icon">Done</span>
            <span class="lsc-val" data-loan-count="returned"><?= (int) ($counts['returned'] ?? 0) ?></span>
            <span class="lsc-label">Returned</span>
        </div>
    </div>

    <div class="page-header" style="margin-bottom: 1rem;">
        <div class="genre-tabs">
            <?php foreach (['all' => 'All', 'active' => 'Active', 'overdue' => 'Overdue', 'returned' => 'Returned'] as $key => $label): ?>
                <button class="genre-tab <?= $key === 'all' ? 'active' : '' ?>" type="button" data-loan-filter="<?= h($key) ?>">
                    <?= h($label) ?><?= $key !== 'all' ? ' (' . ((int) ($counts[$key] ?? 0)) . ')' : '' ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="page-header-right" style="margin-left: auto;">
            <button class="add-btn" type="button" data-modal-open="newLoanModal">New Loan</button>
        </div>
    </div>

    <div class="table-card loans-table-card">
        <table class="books-table loans-table-full">
            <thead>
                <tr>
                    <th>Loan ID</th><th>Book</th><th>Member</th><th>Issued</th><th>Due Date</th><th>Status</th><th>Days</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan): ?>
                    <?php
                    $dueTimestamp = strtotime($loan['due_at']);
                    $daysLeft = (int) round(($dueTimestamp - strtotime('today')) / 86400);
                    ?>
                    <tr data-loan-row data-loan-id="<?= (int) $loan['id'] ?>" data-status="<?= h($loan['status']) ?>">
                        <td><code class="loan-id">#<?= (int) $loan['id'] ?></code></td>
                        <td><span class="book-title-cell"><?= h($loan['book_title']) ?></span></td>
                        <td><?= h($loan['member_name']) ?></td>
                        <td><span class="date-cell"><?= h($loan['issued_at']) ?></span></td>
                        <td><span class="date-cell" data-loan-due><?= h($loan['due_at']) ?></span></td>
                        <td><span class="status-pill status-<?= h($loan['status']) ?>" data-loan-status><?= h(ucfirst($loan['status'])) ?></span></td>
                        <td data-loan-days>
                            <?php if ($loan['status'] === 'returned'): ?>
                                <span class="days-neutral">-</span>
                            <?php elseif ($daysLeft >= 0): ?>
                                <span class="days-ok"><?= $daysLeft ?>d left</span>
                            <?php else: ?>
                                <span class="days-overdue"><?= abs($daysLeft) ?>d late</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <?php if ($loan['status'] !== 'returned'): ?>
                                    <button class="act-btn act-return" type="button" data-loan-return data-loan-action-endpoint="<?= BASE_URL ?>/ajax/loans.php" data-loan-id="<?= (int) $loan['id'] ?>">Return</button>
                                    <?php if ($loan['status'] === 'active'): ?>
                                        <button class="act-btn act-edit" type="button" data-loan-renew data-loan-action-endpoint="<?= BASE_URL ?>/ajax/loans.php" data-loan-id="<?= (int) $loan['id'] ?>">Renew</button>
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

<div class="modal-overlay modal-hidden" id="newLoanModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>New Loan</h2>
            <button class="modal-close" type="button" data-modal-close="newLoanModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="post" class="new-loan-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                <div class="form-row">
                    <div class="form-field">
                        <label>Member Name</label>
                        <select name="user_id" required>
                            <option value="">Select student</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= (int) $member['id'] ?>"><?= h($member['name']) ?> (<?= h($member['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Book Title</label>
                        <select name="book_id" required>
                            <option value="">Select book</option>
                            <?php foreach ($books as $book): ?>
                                <option value="<?= (int) $book['id'] ?>"><?= h($book['title']) ?> (<?= (int) $book['copies_available'] ?> available)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Loan Duration (days)</label>
                        <input type="number" name="duration_days" value="14" min="1" max="60" />
                    </div>
                    <div class="form-field">
                        <label>Notes</label>
                        <input type="text" name="notes" placeholder="Optional remarks for this loan" />
                    </div>
                </div>
                <div class="modal-footer" style="padding-top: 1.25rem;">
                    <button class="btn-secondary" type="button" data-modal-close="newLoanModal">Cancel</button>
                    <button class="btn-primary" type="submit">Create Loan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
