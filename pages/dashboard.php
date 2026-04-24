<?php
require_once __DIR__ . '/../config/config.php';

requireLogin();

$currentUser = getSessionUser();
$activePage = 'dashboard';
$pageTitle = 'Dashboard';
$isStudent = ($currentUser['role'] ?? ROLE_STUDENT) === ROLE_STUDENT;

$monthly = [
    ['month' => 'Nov', 'loans' => 60, 'returns' => 52],
    ['month' => 'Dec', 'loans' => 75, 'returns' => 68],
    ['month' => 'Jan', 'loans' => 95, 'returns' => 88],
    ['month' => 'Feb', 'loans' => 80, 'returns' => 74],
    ['month' => 'Mar', 'loans' => 110, 'returns' => 98],
    ['month' => 'Apr', 'loans' => 128, 'returns' => 112],
];
$maxChartValue = max(array_column($monthly, 'loans'));

$books = $GLOBALS['books'];
$allLoans = $GLOBALS['loans'];
$users = $GLOBALS['users'];
$myLoans = array_values(array_filter($allLoans, fn ($loan) => (int) $loan['user_id'] === (int) ($currentUser['id'] ?? 0)));

$popularBooks = array_map(function ($book) {
    $loans = max(0, (int) $book['copies'] - (int) $book['available']);
    $book['loans'] = $loans;
    $book['progress'] = (int) round(($loans / max((int) $book['copies'], 1)) * 100);
    return $book;
}, $books);
usort($popularBooks, fn ($a, $b) => $b['loans'] <=> $a['loans']);
$popularBooks = array_slice($popularBooks, 0, 5);

$recentLoans = array_slice($allLoans, 0, 5);

$stats = [
    ['label' => 'Total Books', 'value' => count($books), 'icon' => '📚', 'color' => 'blue', 'sub' => 'In catalogue'],
    ['label' => 'Active Loans', 'value' => count(array_filter($allLoans, fn ($loan) => $loan['status'] === 'active')), 'icon' => '🔄', 'color' => 'teal', 'sub' => 'Currently borrowed'],
    ['label' => 'Overdue', 'value' => count(array_filter($allLoans, fn ($loan) => $loan['status'] === 'overdue')), 'icon' => '⚠️', 'color' => 'red', 'sub' => 'Past due date'],
    ['label' => 'Members', 'value' => count($users), 'icon' => '👥', 'color' => 'purple', 'sub' => 'Registered users'],
];

$libraryInfo = [
    ['icon' => '📅', 'label' => 'Loan Duration', 'value' => '14 days'],
    ['icon' => '🔄', 'label' => 'Max Renewals', 'value' => '2 per book'],
    ['icon' => '📚', 'label' => 'Books at Once', 'value' => 'Up to 5 books'],
    ['icon' => '⚠️', 'label' => 'Overdue Fine', 'value' => '€0.50 / day'],
    ['icon' => '🕐', 'label' => 'Opening Hours', 'value' => '8am - 8pm Mon-Fri'],
    ['icon' => '📍', 'label' => 'Location', 'value' => 'UP Library, Prishtina'],
    ['icon' => '📞', 'label' => 'Contact', 'value' => 'library@uni-pr.edu'],
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<?php if ($isStudent): ?>
    <?php
    $returnedLoans = count(array_filter($myLoans, fn ($loan) => $loan['status'] === 'returned'));
    $activeLoans = count(array_filter($myLoans, fn ($loan) => $loan['status'] === 'active'));
    ?>
    <div class="student-dash">
        <div class="student-welcome">
            <div class="sw-left">
                <div class="sw-avatar"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
                <div>
                    <h2>Welcome back, <?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?>! 👋</h2>
                    <p>You have <strong><?= $activeLoans ?></strong> active loan<?= $activeLoans !== 1 ? 's' : '' ?>. Happy reading!</p>
                </div>
            </div>
            <div class="sw-stats">
                <div class="sw-stat">
                    <span class="sw-stat-val"><?= $activeLoans ?></span>
                    <span class="sw-stat-label">Active Loans</span>
                </div>
                <div class="sw-stat-div"></div>
                <div class="sw-stat">
                    <span class="sw-stat-val"><?= $returnedLoans ?></span>
                    <span class="sw-stat-label">Returned</span>
                </div>
                <div class="sw-stat-div"></div>
                <div class="sw-stat">
                    <span class="sw-stat-val"><?= count($myLoans) ?></span>
                    <span class="sw-stat-label">Total Borrowed</span>
                </div>
            </div>
        </div>

        <div class="student-grid">
            <div class="student-card">
                <div class="sc-header">
                    <div><h3>My Loans</h3><span class="sc-sub">Your current borrowing activity</span></div>
                </div>
                <div class="my-loans-list">
                    <?php foreach ($myLoans as $loan): ?>
                        <div class="loan-row">
                            <div class="loan-row-icon">📖</div>
                            <div class="loan-row-info">
                                <span class="loan-row-title"><?= htmlspecialchars($loan['book']) ?></span>
                                <span class="loan-row-due"><?= $loan['status'] === 'returned' ? 'Returned' : 'Due: ' . htmlspecialchars($loan['due']) ?></span>
                            </div>
                            <div class="loan-row-right">
                                <span class="loan-pill lp-<?= htmlspecialchars($loan['status']) ?>"><?= ucfirst($loan['status']) ?></span>
                                <?php if ($loan['status'] === 'active'): ?>
                                    <button class="renew-btn" type="button" data-toast-message="&quot;<?= htmlspecialchars($loan['book']) ?>&quot; renewal request sent!">↻ Renew</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="student-card info-card">
                <div class="sc-header">
                    <div><h3>Library Info</h3><span class="sc-sub">Rules &amp; contact</span></div>
                </div>
                <div class="info-list">
                    <?php foreach ($libraryInfo as $item): ?>
                        <div class="info-item">
                            <span class="info-icon"><?= $item['icon'] ?></span>
                            <div>
                                <span class="info-label"><?= htmlspecialchars($item['label']) ?></span>
                                <span class="info-value"><?= htmlspecialchars($item['value']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="student-card catalogue-card">
                <div class="sc-header">
                    <div><h3>Browse Catalogue</h3><span class="sc-sub">Search and request available books</span></div>
                </div>
                <div class="cat-search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input type="text" placeholder="Search by title or author..." data-filter-input data-filter-scope="catalogue-grid" />
                </div>
                <div class="catalogue-grid" data-filter-scope-id="catalogue-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="cat-book-card" data-filter-item data-title="<?= htmlspecialchars(strtolower($book['title'])) ?>" data-author="<?= htmlspecialchars(strtolower($book['author'])) ?>">
                            <div class="cat-spine cat-spine-<?= (int) $book['id'] % 5 ?>"></div>
                            <div class="cat-body">
                                <span class="cat-genre"><?= htmlspecialchars($book['genre']) ?></span>
                                <h4 class="cat-title"><?= htmlspecialchars($book['title']) ?></h4>
                                <p class="cat-author"><?= htmlspecialchars($book['author']) ?></p>
                                <div class="cat-footer">
                                    <span class="cat-avail <?= (int) $book['available'] > 0 ? 'avail-yes' : 'avail-no' ?>">
                                        <?= (int) $book['available'] > 0 ? (int) $book['available'] . ' available' : 'All loaned' ?>
                                    </span>
                                    <button
                                        class="borrow-btn <?= (int) $book['available'] === 0 ? 'borrow-disabled' : '' ?>"
                                        type="button"
                                        <?= (int) $book['available'] === 0 ? 'disabled' : '' ?>
                                        data-toast-message="Borrow request for &quot;<?= htmlspecialchars($book['title']) ?>&quot; sent to librarian!"
                                    >
                                        <?= (int) $book['available'] > 0 ? 'Request' : 'Unavailable' ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="cat-empty modal-hidden" data-filter-empty>No books match your search.</div>
                </div>
            </div>
        </div>
    </div>