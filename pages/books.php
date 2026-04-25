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
<div class="books-page" data-books-page>
    <div class="page-header">
        <div class="page-header-left">
            <div class="search-wrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" placeholder="Search title, author, ISBN..." class="page-search" data-filter-input data-filter-scope="books-items" />
            </div>
            <div class="genre-tabs">
                <?php foreach ($genres as $index => $genre): ?>
                    <button class="genre-tab <?= $index === 0 ? 'active' : '' ?>" type="button" data-genre-tab="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="page-header-right">
            <div class="view-toggle">
                <button class="vt-btn active" type="button" data-view-btn="table">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                </button>
                <button class="vt-btn" type="button" data-view-btn="grid">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </button>
            </div>
            <button class="add-btn" type="button" data-modal-open="bookEditorModal" data-book-mode="add">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add Book
            </button>
        </div>
    </div>