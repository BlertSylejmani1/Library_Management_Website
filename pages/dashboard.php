<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/BookRepository.php';
require_once __DIR__ . '/../classes/BookRequestRepository.php';
require_once __DIR__ . '/../classes/LoanRepository.php';
require_once __DIR__ . '/../classes/UserRepository.php';

requireLogin();

$currentUser = getSessionUser();
$activePage = 'dashboard';
$pageTitle = 'Dashboard';
$isStudent = ($currentUser['role'] ?? ROLE_STUDENT) === ROLE_STUDENT;
$pageError = '';

$books = [];
$myLoans = [];
$popularBooks = [];
$recentLoans = [];
$bookRequests = [];
$pendingRequestBookIds = [];
$counts = ['active' => 0, 'overdue' => 0, 'returned' => 0];
$memberCount = 0;

try {
    $db = Database::connection();
    $bookRepository = new BookRepository($db);
    $bookRequestRepository = new BookRequestRepository($db);
    $loanRepository = new LoanRepository($db);
    $userRepository = new UserRepository($db);

    $books = $bookRepository->all();
    $popularBooks = $bookRepository->popular(5);
    $recentLoans = $loanRepository->recent(5);
    $counts = $loanRepository->counts();
    $memberCount = $userRepository->countAll();
    $myLoans = $loanRepository->byUser((int) ($currentUser['id'] ?? 0));
    $bookRequests = $isStudent
        ? $bookRequestRepository->byUser((int) ($currentUser['id'] ?? 0))
        : $bookRequestRepository->all('pending');
    $pendingRequestBookIds = $isStudent ? $bookRequestRepository->pendingBookIdsForUser((int) ($currentUser['id'] ?? 0)) : [];
} catch (Throwable $exception) {
    $pageError = 'Database data could not be loaded. Please verify your MySQL connection.';
}

$monthly = [
    ['month' => 'Dec', 'loans' => 72, 'returns' => 66],
    ['month' => 'Jan', 'loans' => 88, 'returns' => 81],
    ['month' => 'Feb', 'loans' => 94, 'returns' => 86],
    ['month' => 'Mar', 'loans' => 111, 'returns' => 101],
    ['month' => 'Apr', 'loans' => 126, 'returns' => 114],
    ['month' => 'May', 'loans' => 84, 'returns' => 68],
];
$maxChartValue = max(array_column($monthly, 'loans'));

$stats = [
    ['label' => 'Total Books', 'value' => count($books), 'icon' => 'Books', 'color' => 'blue', 'sub' => 'In catalogue'],
    ['label' => 'Active Loans', 'value' => (int) ($counts['active'] ?? 0), 'icon' => 'Loans', 'color' => 'teal', 'sub' => 'Currently borrowed'],
    ['label' => 'Overdue', 'value' => (int) ($counts['overdue'] ?? 0), 'icon' => 'Due', 'color' => 'red', 'sub' => 'Past due date'],
    ['label' => 'Members', 'value' => $memberCount, 'icon' => 'Users', 'color' => 'purple', 'sub' => 'Registered users'],
];

$libraryInfo = [
    ['icon' => 'Info', 'label' => 'Loan Duration', 'value' => '14 days'],
    ['icon' => 'Renew', 'label' => 'Max Renewals', 'value' => '2 per book'],
    ['icon' => 'Books', 'label' => 'Books at Once', 'value' => 'Up to 5 books'],
    ['icon' => 'Fine', 'label' => 'Overdue Fine', 'value' => 'EUR 0.50 / day'],
    ['icon' => 'Hours', 'label' => 'Opening Hours', 'value' => '8am - 8pm Mon-Fri'],
    ['icon' => 'Place', 'label' => 'Location', 'value' => 'UP Library, Prishtina'],
    ['icon' => 'Mail', 'label' => 'Contact', 'value' => 'library@uni-pr.edu'],
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<?php if ($pageError !== ''): ?>
    <div class="page-alert alert-error" style="position: fixed; top: 90px; right: 1.75rem; z-index: 999;">
        <?= h($pageError) ?>
    </div>
<?php endif; ?>

<input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />

<?php if ($isStudent): ?>
    <?php
    $returnedLoans = count(array_filter($myLoans, fn ($loan) => $loan['status'] === 'returned'));
    $activeLoans = count(array_filter($myLoans, fn ($loan) => $loan['status'] === 'active'));
    ?>
    <div class="student-dash">
        <div class="student-welcome">
            <div class="sw-left">
                <div class="sw-avatar"><?= h(strtoupper(substr($currentUser['name'] ?? 'S', 0, 1))) ?></div>
                <div>
                    <h2>Welcome back, <?= h(explode(' ', $currentUser['name'] ?? 'Student')[0]) ?>!</h2>
                    <p>You have <strong><?= $activeLoans ?></strong> active loan<?= $activeLoans !== 1 ? 's' : '' ?>.</p>
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
                        <div class="loan-row" data-loan-row data-loan-id="<?= (int) $loan['id'] ?>" data-status="<?= h($loan['status']) ?>">
                            <div class="loan-row-icon">Book</div>
                            <div class="loan-row-info">
                                <span class="loan-row-title"><?= h($loan['book_title']) ?></span>
                                <span class="loan-row-due"><?= $loan['status'] === 'returned' ? 'Returned' : 'Due: ' . h($loan['due_at']) ?></span>
                            </div>
                            <div class="loan-row-right">
                                <span class="loan-pill lp-<?= h($loan['status']) ?>" data-loan-status><?= h(ucfirst($loan['status'])) ?></span>
                                <?php if (in_array($loan['status'], ['active', 'overdue'], true) && (int) $loan['renewal_count'] < 2): ?>
                                    <button class="renew-btn" type="button" data-loan-renew data-loan-action-endpoint="<?= BASE_URL ?>/ajax/loans.php" data-loan-id="<?= (int) $loan['id'] ?>">Renew</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$myLoans): ?>
                        <div class="cat-empty">No loan activity yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="student-card info-card">
                <div class="sc-header">
                    <div><h3>Library Info</h3><span class="sc-sub">Rules &amp; contact</span></div>
                </div>
                <div class="info-list">
                    <?php foreach ($libraryInfo as $item): ?>
                        <div class="info-item">
                            <span class="info-icon"><?= h($item['icon']) ?></span>
                            <div>
                                <span class="info-label"><?= h($item['label']) ?></span>
                                <span class="info-value"><?= h($item['value']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="student-card requests-card">
                <div class="sc-header">
                    <div><h3>My Requests</h3><span class="sc-sub">Borrow requests and review status</span></div>
                </div>
                <div class="request-list" data-student-request-list>
                    <?php foreach (array_slice($bookRequests, 0, 5) as $request): ?>
                        <div class="request-item">
                            <div>
                                <strong><?= h($request['book_title']) ?></strong>
                                <span><?= h(date('M d, Y', strtotime($request['requested_at']))) ?></span>
                            </div>
                            <span class="status-pill status-<?= h($request['status']) ?>"><?= h(ucfirst($request['status'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$bookRequests): ?>
                        <div class="cat-empty" data-student-request-empty>No book requests yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="student-card catalogue-card" id="browse-catalogue">
                <div class="sc-header">
                    <div><h3>Browse Catalogue</h3><span class="sc-sub">Search available books</span></div>
                </div>
                <div class="cat-search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input type="text" placeholder="Search by title or author..." data-filter-input data-filter-scope="catalogue-grid" />
                </div>
                <div class="catalogue-grid" data-filter-scope-id="catalogue-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="cat-book-card" data-filter-item data-title="<?= h(strtolower($book['title'])) ?>" data-author="<?= h(strtolower($book['author'])) ?>">
                            <div class="cat-spine cat-spine-<?= (int) $book['id'] % 5 ?>"></div>
                            <div class="cat-body">
                                <span class="cat-genre"><?= h($book['genre']) ?></span>
                                <h4 class="cat-title"><?= h($book['title']) ?></h4>
                                <p class="cat-author"><?= h($book['author']) ?></p>
                                <div class="cat-footer">
                                    <span class="cat-avail <?= (int) $book['copies_available'] > 0 ? 'avail-yes' : 'avail-no' ?>">
                                        <?= (int) $book['copies_available'] > 0 ? (int) $book['copies_available'] . ' available' : 'All loaned' ?>
                                    </span>
                                    <?php
                                    $isPendingRequest = in_array((int) $book['id'], $pendingRequestBookIds, true);
                                    $isUnavailable = (int) $book['copies_available'] === 0;
                                    ?>
                                    <button
                                        class="borrow-btn <?= ($isUnavailable || $isPendingRequest) ? 'borrow-disabled' : '' ?>"
                                        type="button"
                                        data-book-request
                                        data-request-endpoint="<?= BASE_URL ?>/ajax/book_requests.php"
                                        data-book-id="<?= (int) $book['id'] ?>"
                                        <?= ($isUnavailable || $isPendingRequest) ? 'disabled' : '' ?>
                                    >
                                        <?= $isPendingRequest ? 'Requested' : ($isUnavailable ? 'Unavailable' : 'Request') ?>
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
                <div class="stat-card stat-<?= h($stat['color']) ?>" style="animation-delay: <?= number_format($index * 0.07, 2) ?>s">
                    <div class="stat-card-header">
                        <div class="stat-icon"><?= h($stat['icon']) ?></div>
                    </div>
                    <div class="stat-value"><?= h((string) $stat['value']) ?></div>
                    <div class="stat-label"><?= h($stat['label']) ?></div>
                    <div class="stat-sub"><?= h($stat['sub']) ?></div>
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
                            <div class="bar-label"><?= h($month['month']) ?></div>
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
                        <?php
                        $loansCount = (int) ($book['loans_count'] ?? 0);
                        $progress = (int) round(($loansCount / max((int) $book['copies_total'], 1)) * 100);
                        ?>
                        <div class="popular-item">
                            <div class="popular-rank">#<?= $index + 1 ?></div>
                            <div class="popular-info">
                                <div class="popular-title"><?= h($book['title']) ?></div>
                                <div class="popular-genre"><?= h($book['genre']) ?></div>
                                <div class="popular-bar-wrap">
                                    <div class="popular-bar" style="width: <?= $progress ?>%"></div>
                                </div>
                            </div>
                            <div class="popular-count"><?= $loansCount ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dash-card loans-card">
                <div class="card-header">
                    <div><h3>Recent Loans</h3><span class="card-sub">Latest borrowing activity</span></div>
                    <a class="view-all-btn" href="<?= BASE_URL ?>/pages/loans.php">View all</a>
                </div>
                <div class="loans-table-wrap">
                    <table class="loans-table">
                        <thead>
                            <tr><th>Loan ID</th><th>Book</th><th>Member</th><th>Due Date</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLoans as $loan): ?>
                                <tr>
                                    <td><code class="loan-id">#<?= (int) $loan['id'] ?></code></td>
                                    <td><span class="book-name"><?= h($loan['book_title']) ?></span></td>
                                    <td><?= h($loan['member_name']) ?></td>
                                    <td><?= h($loan['due_at']) ?></td>
                                    <td><span class="status-badge status-<?= h($loan['status']) ?>"><?= h(ucfirst($loan['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dash-card requests-admin-card">
                <div class="card-header">
                    <div><h3>Book Requests</h3><span class="card-sub">Pending student requests</span></div>
                </div>
                <div class="request-admin-list" data-admin-request-list>
                    <?php foreach (array_slice($bookRequests, 0, 6) as $request): ?>
                        <div class="request-admin-item" data-request-row data-request-id="<?= (int) $request['id'] ?>">
                            <div class="request-admin-copy">
                                <strong><?= h($request['book_title']) ?></strong>
                                <span><?= h($request['student_name']) ?> - <?= h(date('M d', strtotime($request['requested_at']))) ?></span>
                            </div>
                            <div class="action-btns">
                                <button class="act-btn act-edit" type="button" data-request-action="approve" data-request-endpoint="<?= BASE_URL ?>/ajax/book_requests.php" data-request-id="<?= (int) $request['id'] ?>">Approve</button>
                                <button class="act-btn act-delete" type="button" data-request-action="reject" data-request-endpoint="<?= BASE_URL ?>/ajax/book_requests.php" data-request-id="<?= (int) $request['id'] ?>">Reject</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$bookRequests): ?>
                        <div class="empty-row" data-admin-request-empty>No pending requests.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dash-card actions-card">
                <div class="card-header">
                    <div><h3>Quick Actions</h3><span class="card-sub">Common tasks</span></div>
                </div>
                <div class="quick-actions">
                    <?php foreach ([
                        ['icon' => 'Books', 'label' => 'Add New Book', 'color' => 'blue', 'href' => BASE_URL . '/pages/books.php'],
                        ['icon' => 'Users', 'label' => 'Register Member', 'color' => 'purple', 'href' => BASE_URL . '/pages/users.php'],
                        ['icon' => 'Loans', 'label' => 'Process Loan', 'color' => 'teal', 'href' => BASE_URL . '/pages/loans.php'],
                        ['icon' => 'Return', 'label' => 'Process Return', 'color' => 'green', 'href' => BASE_URL . '/pages/loans.php'],
                        ['icon' => 'Stats', 'label' => 'View All Loans', 'color' => 'orange', 'href' => BASE_URL . '/pages/loans.php'],
                        ['icon' => 'Search', 'label' => 'Search Catalogue', 'color' => 'gray', 'href' => BASE_URL . '/pages/books.php'],
                    ] as $action): ?>
                        <a class="quick-action-btn qa-<?= h($action['color']) ?>" href="<?= h($action['href']) ?>">
                            <span class="qa-icon"><?= h($action['icon']) ?></span>
                            <span class="qa-label"><?= h($action['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="dash-calendar">
                    <div class="cal-header">May 2026</div>
                    <div class="cal-days">
                        <?php foreach (['Mo','Tu','We','Th','Fr','Sa','Su'] as $day): ?>
                            <div class="cal-day-label"><?= h($day) ?></div>
                        <?php endforeach; ?>
                        <?php for ($day = 1; $day <= 31; $day++): ?>
                            <div class="cal-day <?= $day === 25 ? 'today' : '' ?> <?= in_array($day, [4, 11, 18, 25], true) ? 'has-event' : '' ?>">
                                <?= $day ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
