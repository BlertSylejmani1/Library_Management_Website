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
<?php else: ?>
    <div class="dashboard">
        <div class="stats-grid">
            <?php foreach ($stats as $index => $stat): ?>
                <div class="stat-card stat-<?= htmlspecialchars($stat['color']) ?>" style="animation-delay: <?= number_format($index * 0.07, 2) ?>s">
                    <div class="stat-card-header">
                        <div class="stat-icon"><?= $stat['icon'] ?></div>
                    </div>
                    <div class="stat-value"><?= htmlspecialchars((string) $stat['value']) ?></div>
                    <div class="stat-label"><?= htmlspecialchars($stat['label']) ?></div>
                    <div class="stat-sub"><?= htmlspecialchars($stat['sub']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="dash-grid">
            <div class="dash-card chart-card">
                <div class="card-header">
                    <div><h3>Loan Activity</h3><span class="card-sub">Last 6 months</span></div>
                    <div class="chart-legend">
                        <span class="legend-dot blue"></span> Loans
                        <span class="legend-dot teal"></span> Returns
                    </div>
                </div>
                <div class="bar-chart">
                    <?php foreach ($monthly as $month): ?>
                        <div class="bar-group">
                            <div class="bars">
                                <div class="bar bar-loan" style="height: <?= ($month['loans'] / $maxChartValue) * 100 ?>%" title="Loans: <?= $month['loans'] ?>">
                                    <span class="bar-tip"><?= $month['loans'] ?></span>
                                </div>
                                <div class="bar bar-return" style="height: <?= ($month['returns'] / $maxChartValue) * 100 ?>%" title="Returns: <?= $month['returns'] ?>">
                                    <span class="bar-tip"><?= $month['returns'] ?></span>
                                </div>
                            </div>
                            <div class="bar-label"><?= htmlspecialchars($month['month']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dash-card popular-card">
                <div class="card-header">
                    <div><h3>Popular Books</h3><span class="card-sub">Most borrowed</span></div>
                </div>
                <div class="popular-list">
                    <?php foreach ($popularBooks as $index => $book): ?>
                        <div class="popular-item">
                            <div class="popular-rank">#<?= $index + 1 ?></div>
                            <div class="popular-info">
                                <div class="popular-title"><?= htmlspecialchars($book['title']) ?></div>
                                <div class="popular-genre"><?= htmlspecialchars($book['genre']) ?></div>
                                <div class="popular-bar-wrap">
                                    <div class="popular-bar" style="width: <?= $book['progress'] ?>%"></div>
                                </div>
                            </div>
                            <div class="popular-count"><?= $book['loans'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dash-card loans-card">
                <div class="card-header">
                    <div><h3>Recent Loans</h3><span class="card-sub">Latest borrowing activity</span></div>
                    <a class="view-all-btn" href="<?= BASE_URL ?>/pages/loans.php">View all →</a>
                </div>
                <div class="loans-table-wrap">
                    <table class="loans-table">
                        <thead>
                            <tr><th>Loan ID</th><th>Book</th><th>Member</th><th>Due Date</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLoans as $loan): ?>
                                <tr>
                                    <td><code class="loan-id"><?= htmlspecialchars($loan['id']) ?></code></td>
                                    <td><span class="book-name"><?= htmlspecialchars($loan['book']) ?></span></td>
                                    <td><?= htmlspecialchars($loan['member']) ?></td>
                                    <td><?= htmlspecialchars($loan['due']) ?></td>
                                    <td><span class="status-badge status-<?= htmlspecialchars($loan['status']) ?>"><?= ucfirst($loan['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dash-card actions-card">
                <div class="card-header">
                    <div><h3>Quick Actions</h3><span class="card-sub">Common tasks</span></div>
                </div>
                <div class="quick-actions">
                    <?php foreach ([
                        ['icon' => '📚', 'label' => 'Add New Book', 'color' => 'blue', 'modal' => 'dashAddBook'],
                        ['icon' => '👤', 'label' => 'Register Member', 'color' => 'purple', 'modal' => 'dashAddMember'],
                        ['icon' => '🔄', 'label' => 'Process Loan', 'color' => 'teal', 'modal' => 'dashNewLoan'],
                        ['icon' => '↩️', 'label' => 'Process Return', 'color' => 'green', 'href' => BASE_URL . '/pages/loans.php'],
                        ['icon' => '📊', 'label' => 'View All Loans', 'color' => 'orange', 'href' => BASE_URL . '/pages/loans.php'],
                        ['icon' => '🔍', 'label' => 'Search Catalogue', 'color' => 'gray', 'href' => BASE_URL . '/pages/books.php'],
                    ] as $action): ?>
                        <?php if (isset($action['href'])): ?>
                            <a class="quick-action-btn qa-<?= $action['color'] ?>" href="<?= htmlspecialchars($action['href']) ?>">
                                <span class="qa-icon"><?= $action['icon'] ?></span>
                                <span class="qa-label"><?= htmlspecialchars($action['label']) ?></span>
                            </a>
                        <?php else: ?>
                            <button class="quick-action-btn qa-<?= $action['color'] ?>" type="button" data-modal-open="<?= htmlspecialchars($action['modal']) ?>">
                                <span class="qa-icon"><?= $action['icon'] ?></span>
                                <span class="qa-label"><?= htmlspecialchars($action['label']) ?></span>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="dash-calendar">
                    <div class="cal-header">April 2026</div>
                    <div class="cal-days">
                        <?php foreach (['Mo','Tu','We','Th','Fr','Sa','Su'] as $day): ?>
                            <div class="cal-day-label"><?= $day ?></div>
                        <?php endforeach; ?>
                        <?php for ($day = 1; $day <= 30; $day++): ?>
                            <div class="cal-day <?= $day === 24 ? 'today' : '' ?> <?= in_array($day, [5, 12, 18, 25], true) ? 'has-event' : '' ?>">
                                <?= $day ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php foreach ([
            'dashNewLoan' => ['title' => 'Process New Loan', 'submit' => 'Create Loan', 'message' => 'Loan created successfully for the selected member.'],
            'dashAddBook' => ['title' => 'Add New Book', 'submit' => 'Add Book', 'message' => 'Book added to catalogue.'],
            'dashAddMember' => ['title' => 'Register New Member', 'submit' => 'Register', 'message' => 'Member registered successfully.'],
        ] as $modalId => $modal): ?>
            <div class="modal-overlay modal-hidden" id="<?= $modalId ?>">
                <div class="modal-box">
                    <div class="modal-header">
                        <h2><?= htmlspecialchars($modal['title']) ?></h2>
                        <button class="modal-close" type="button" data-modal-close="<?= $modalId ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form class="static-form" data-static-form data-success-message="<?= htmlspecialchars($modal['message']) ?>" data-close-modal="<?= $modalId ?>">
                            <div class="form-field">
                                <label><?= $modalId === 'dashAddBook' ? 'Title' : 'Member Name' ?></label>
                                <input type="text" required />
                            </div>
                            <div class="form-field">
                                <label><?= $modalId === 'dashAddBook' ? 'Author' : 'Book Title' ?></label>
                                <input type="text" required />
                            </div>
                            <div class="form-field">
                                <label><?= $modalId === 'dashAddMember' ? 'Email' : 'Details' ?></label>
                                <input type="<?= $modalId === 'dashAddMember' ? 'email' : 'text' ?>" required />
                            </div>
                            <div class="modal-footer" style="padding-top: 1rem;">
                                <button class="btn-secondary" type="button" data-modal-close="<?= $modalId ?>">Cancel</button>
                                <button class="btn-primary" type="submit"><?= htmlspecialchars($modal['submit']) ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

