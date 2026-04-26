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
    
    <div class="result-count"><span data-books-count><?= count($books) ?></span> books found</div>

    <div class="table-card" data-view-panel="table">
        <table class="books-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Genre</th>
                    <th>ISBN</th>
                    <th>Year</th>
                    <th>Copies</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody data-filter-scope-id="books-items">
                <?php foreach ($books as $book): ?>
                    <tr
                        data-filter-item
                        data-title="<?= htmlspecialchars(strtolower($book['title'])) ?>"
                        data-author="<?= htmlspecialchars(strtolower($book['author'])) ?>"
                        data-isbn="<?= htmlspecialchars(strtolower($book['isbn'])) ?>"
                        data-genre="<?= htmlspecialchars($book['genre']) ?>"
                        data-book='<?= htmlspecialchars(json_encode($book, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>'
                    >
                        <td><span class="book-title-cell"><?= htmlspecialchars($book['title']) ?></span></td>
                        <td><?= htmlspecialchars($book['author']) ?></td>
                        <td><span class="genre-tag"><?= htmlspecialchars($book['genre']) ?></span></td>
                        <td><code class="isbn-code"><?= htmlspecialchars($book['isbn']) ?></code></td>
                        <td><?= htmlspecialchars((string) $book['year']) ?></td>
                        <td>
                            <span class="copies-cell">
                                <span class="copies-avail"><?= (int) $book['available'] ?></span>
                                <span class="copies-sep">/</span>
                                <span class="copies-total"><?= (int) $book['copies'] ?></span>
                            </span>
                        </td>
                        <td>
                            <span class="status-pill status-<?= (int) $book['available'] > 0 ? 'available' : 'loaned' ?>">
                                <?= (int) $book['available'] > 0 ? 'Available' : 'All Loaned' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="act-btn act-edit" type="button" data-edit-book>Edit</button>
                                <button class="act-btn act-delete" type="button" data-delete-book data-book-title="<?= htmlspecialchars($book['title']) ?>">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="modal-hidden" data-filter-empty-row><td colspan="8" class="empty-row">No books match your search.</td></tr>
            </tbody>
        </table>
    </div>
    
    <div class="books-grid modal-hidden" data-view-panel="grid">
        <?php foreach ($books as $book): ?>
            <div
                class="book-card"
                data-filter-item
                data-title="<?= htmlspecialchars(strtolower($book['title'])) ?>"
                data-author="<?= htmlspecialchars(strtolower($book['author'])) ?>"
                data-isbn="<?= htmlspecialchars(strtolower($book['isbn'])) ?>"
                data-genre="<?= htmlspecialchars($book['genre']) ?>"
                data-book='<?= htmlspecialchars(json_encode($book, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>'
            >
                <div class="book-card-spine spine-<?= ['blue','teal','purple','orange','red','green'][$book['id'] % 6] ?>"></div>
                <div class="book-card-body">
                    <div class="book-card-header">
                        <span class="genre-tag"><?= htmlspecialchars($book['genre']) ?></span>
                        <span class="status-pill status-<?= (int) $book['available'] > 0 ? 'available' : 'loaned' ?>">
                            <?= (int) $book['available'] > 0 ? 'Available' : 'Loaned' ?>
                        </span>
                    </div>
                    <h4 class="book-card-title"><?= htmlspecialchars($book['title']) ?></h4>
                    <p class="book-card-author"><?= htmlspecialchars($book['author']) ?></p>
                    <div class="book-card-meta">
                        <span><?= (int) $book['year'] ?></span>
                        <span><?= (int) $book['available'] ?>/<?= (int) $book['copies'] ?> available</span>
                    </div>
                    <div class="book-card-actions">
                        <button class="act-btn act-edit" type="button" data-edit-book>Edit</button>
                        <button class="act-btn act-delete" type="button" data-delete-book data-book-title="<?= htmlspecialchars($book['title']) ?>">Delete</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="empty-row modal-hidden" data-filter-empty>No books match your search.</div>
    </div>
</div>
                