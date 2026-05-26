<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/BookRepository.php';
require_once __DIR__ . '/../classes/FileUploader.php';

requireLogin();

$currentUser = getSessionUser();
if (($currentUser['role'] ?? ROLE_STUDENT) === ROLE_STUDENT) {
    redirect('pages/dashboard.php');
}

$activePage = 'books';
$pageTitle = 'Books';
$pageSubtitle = 'Browse and manage the library catalogue.';
$genres = ['All', 'Software Eng.', 'CS Theory', 'Networking', 'OS', 'Databases', 'AI / ML', 'Architecture'];
$pageError = '';
$editingBook = null;
$flash = pullFlash('books');

try {
    $bookRepository = new BookRepository(Database::connection());

    if (isPost()) {
        verify_csrf_or_fail();
        $action = $_POST['action'] ?? 'save';
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $genre = trim($_POST['genre'] ?? 'Software Eng.');
        $isbn = trim($_POST['isbn'] ?? '');
        $publicationYear = (int) ($_POST['publication_year'] ?? date('Y'));
        $copiesTotal = max(1, (int) ($_POST['copies_total'] ?? 1));
        $copiesAvailable = max(0, min((int) ($_POST['copies_available'] ?? $copiesTotal), $copiesTotal));
        $description = trim($_POST['description'] ?? '');

        new Book(
            max(1, $bookId),
            $title,
            $author,
            $genre,
            $isbn,
            $publicationYear,
            $copiesTotal,
            $copiesAvailable
        );

        $existingBook = $bookId > 0 ? $bookRepository->findById($bookId) : null;
        $coverImage = FileUploader::uploadBookCover($_FILES['cover'] ?? [], $existingBook['cover_image'] ?? null);

        $payload = [
            'title' => $title,
            'author' => $author,
            'genre' => $genre,
            'isbn' => $isbn,
            'publication_year' => $publicationYear,
            'copies_total' => $copiesTotal,
            'copies_available' => $copiesAvailable,
            'cover_image' => $coverImage,
            'description' => $description,
        ];

        if ($action === 'update' && $bookId > 0) {
            $bookRepository->update($bookId, $payload);
            flash('books', 'Book updated successfully.');
        } else {
            $bookRepository->create($payload);
            flash('books', 'Book added successfully.');
        }

        redirect('pages/books.php');
    }

    $books = $bookRepository->all();
} catch (Throwable $exception) {
    $books = [];
    $pageError = $exception->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="books-page" data-books-page>
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
                    <button class="genre-tab <?= $index === 0 ? 'active' : '' ?>" type="button" data-genre-tab="<?= h($genre) ?>"><?= h($genre) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="page-header-right">
            <div class="view-toggle">
                <button class="vt-btn active" type="button" data-view-btn="table">Table</button>
                <button class="vt-btn" type="button" data-view-btn="grid">Grid</button>
            </div>
            <button class="add-btn" type="button" data-modal-open="bookEditorModal" data-book-mode="add">
                Add Book
            </button>
        </div>
    </div>

    <div class="table-card" style="margin-bottom: 1rem;">
        <div class="card-header">
            <div><h3>Book Lookup</h3><span class="card-sub">Find book details by title or ISBN</span></div>
        </div>
        <div class="modal-body" style="padding-top: 0;">
            <div class="form-row">
                <div class="form-field">
                    <label>Search by ISBN or title</label>
                    <input type="text" data-api-book-query placeholder="9780132350884 or Clean Code" />
                </div>
                <div class="form-field" style="align-self: end;">
                    <button class="btn-primary" type="button" data-api-book-search>Search</button>
                </div>
            </div>
            <div class="empty-row modal-hidden" data-api-book-status></div>
            <div class="books-grid modal-hidden" data-api-book-results></div>
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
                        data-book-row-id="<?= (int) $book['id'] ?>"
                        data-title="<?= h(strtolower($book['title'])) ?>"
                        data-author="<?= h(strtolower($book['author'])) ?>"
                        data-isbn="<?= h(strtolower($book['isbn'])) ?>"
                        data-genre="<?= h($book['genre']) ?>"
                        data-book='<?= h(json_encode($book, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'
                    >
                        <td><span class="book-title-cell"><?= h($book['title']) ?></span></td>
                        <td><?= h($book['author']) ?></td>
                        <td><span class="genre-tag"><?= h($book['genre']) ?></span></td>
                        <td><code class="isbn-code"><?= h($book['isbn']) ?></code></td>
                        <td><?= (int) $book['publication_year'] ?></td>
                        <td>
                            <span class="copies-cell">
                                <span class="copies-avail"><?= (int) $book['copies_available'] ?></span>
                                <span class="copies-sep">/</span>
                                <span class="copies-total"><?= (int) $book['copies_total'] ?></span>
                            </span>
                        </td>
                        <td>
                            <span class="status-pill status-<?= (int) $book['copies_available'] > 0 ? 'available' : 'loaned' ?>">
                                <?= (int) $book['copies_available'] > 0 ? 'Available' : 'All Loaned' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="act-btn act-edit" type="button" data-edit-book>Edit</button>
                                <button class="act-btn act-delete" type="button" data-delete-book data-book-id="<?= (int) $book['id'] ?>" data-book-title="<?= h($book['title']) ?>">Delete</button>
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
                data-book-card-id="<?= (int) $book['id'] ?>"
                data-title="<?= h(strtolower($book['title'])) ?>"
                data-author="<?= h(strtolower($book['author'])) ?>"
                data-isbn="<?= h(strtolower($book['isbn'])) ?>"
                data-genre="<?= h($book['genre']) ?>"
                data-book='<?= h(json_encode($book, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'
            >
                <div class="book-card-spine spine-<?= ['blue','teal','purple','orange','red','green'][$book['id'] % 6] ?>"></div>
                <div class="book-card-body">
                    <div class="book-card-header">
                        <span class="genre-tag"><?= h($book['genre']) ?></span>
                        <span class="status-pill status-<?= (int) $book['copies_available'] > 0 ? 'available' : 'loaned' ?>">
                            <?= (int) $book['copies_available'] > 0 ? 'Available' : 'Loaned' ?>
                        </span>
                    </div>
                    <h4 class="book-card-title"><?= h($book['title']) ?></h4>
                    <p class="book-card-author"><?= h($book['author']) ?></p>
                    <div class="book-card-meta">
                        <span><?= (int) $book['publication_year'] ?></span>
                        <span><?= (int) $book['copies_available'] ?>/<?= (int) $book['copies_total'] ?> available</span>
                    </div>
                    <div class="book-card-actions">
                        <button class="act-btn act-edit" type="button" data-edit-book>Edit</button>
                        <button class="act-btn act-delete" type="button" data-delete-book data-book-id="<?= (int) $book['id'] ?>" data-book-title="<?= h($book['title']) ?>">Delete</button>
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
            <h2 data-book-modal-title><?= $editingBook ? 'Edit Book' : 'Add New Book' ?></h2>
            <button class="modal-close" type="button" data-modal-close="bookEditorModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="post" enctype="multipart/form-data" data-book-form>
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                <input type="hidden" name="action" value="create" data-book-form-action />
                <input type="hidden" name="book_id" value="" data-book-id />
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
                        <input name="isbn" type="text" placeholder="9780132350884" required />
                    </div>
                    <div class="form-field">
                        <label>Genre</label>
                        <select name="genre">
                            <?php foreach (array_slice($genres, 1) as $genre): ?>
                                <option value="<?= h($genre) ?>"><?= h($genre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Year</label>
                        <input name="publication_year" type="number" min="1900" max="2100" placeholder="e.g. 2023" />
                    </div>
                    <div class="form-field">
                        <label>Total Copies</label>
                        <input name="copies_total" type="number" min="1" max="99" value="1" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Available Copies</label>
                        <input name="copies_available" type="number" min="0" max="99" value="1" />
                    </div>
                    <div class="form-field">
                        <label>Cover Image</label>
                        <input name="cover" type="file" accept=".jpg,.jpeg,.png,.webp" />
                    </div>
                </div>
                <div class="form-field">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Short catalogue description..."></textarea>
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
        <div class="confirm-icon">Delete</div>
        <h3>Delete this book?</h3>
        <p data-delete-copy>This will be permanently removed from the catalogue.</p>
        <div class="modal-footer">
            <button class="btn-secondary" type="button" data-modal-close="bookDeleteModal">Cancel</button>
            <button class="btn-danger" type="button" data-confirm-book-delete data-endpoint="<?= BASE_URL ?>/ajax/books.php">Delete</button>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
