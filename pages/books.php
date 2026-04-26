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

<div class="modal-overlay modal-hidden" id="bookEditorModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2 data-book-modal-title>Add New Book</h2>
            <button class="modal-close" type="button" data-modal-close="bookEditorModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form data-static-form data-success-message="Book details saved." data-close-modal="bookEditorModal">
                <div class="form-row">
                    <div class="form-field">
                        <label>Title <span class="req">*</span></label>
                        <input name="title" type="text" placeholder="Book title" required />
                    </div>
                    <div class="form-field">
                        <label>Author <span class="req">*</span></label>
                        <input name="author" type="text" placeholder="Author name" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>ISBN <span class="req">*</span></label>
                        <input name="isbn" type="text" placeholder="978-XXXXXXXXXX" required />
                    </div>
                    <div class="form-field">
                        <label>Genre</label>
                        <select name="genre">
                            <?php foreach (array_slice($genres, 1) as $genre): ?>
                                <option value="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Year</label>
                        <input name="year" type="number" min="1000" max="2100" placeholder="e.g. 2023" />
                    </div>
                    <div class="form-field">
                        <label>Copies</label>
                        <input name="copies" type="number" min="1" max="99" value="1" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" type="button" data-modal-close="bookEditorModal">Cancel</button>
                    <button class="btn-primary" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal-overlay modal-hidden" id="bookDeleteModal">
    <div class="modal-box confirm-box">
        <div class="confirm-icon">🗑️</div>
        <h3>Delete this book?</h3>
        <p data-delete-copy>This will be permanently removed from the catalogue.</p>
        <div class="modal-footer">
            <button class="btn-secondary" type="button" data-modal-close="bookDeleteModal">Cancel</button>
            <button class="btn-danger" type="button" data-toast-message="Book removed from catalogue." data-modal-close="bookDeleteModal">Delete</button>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>                