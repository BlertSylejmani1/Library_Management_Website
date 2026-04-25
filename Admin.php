<?php
// ============================================================
//  classes/Admin.php
//  Admin class — extends User
//  Adds book/loan management capabilities
//  Phase I — No Database
// ============================================================

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Book.php';

class Admin extends User {

    private string $department;

    public function __construct(
        int    $id,
        string $name,
        string $email,
        string $password,
        string $phone      = '',
        string $department = 'Library Administration'
    ) {
        parent::__construct($id, $name, $email, $password, 'admin', $phone);
        $this->department = $department;
    }

    public function getDepartment(): string { return $this->department; }

    public function setDepartment(string $department): void {
        if (strlen(trim($department)) < 2) {
            throw new InvalidArgumentException('Department name too short.');
        }
        $this->department = trim($department);
    }

    // ── Add a new book ───────────────────────────────────────
    public function addBook(string $title, string $author, string $genre = '', string $isbn = '', int $year = 0, int $copies = 1): array {
        $maxId = 0;
        foreach ($GLOBALS['books'] as $b) {
            $id = is_array($b) ? $b['id'] : $b->getId();
            if ($id > $maxId) $maxId = $id;
        }
        $newBook = [
            'id'        => $maxId + 1,
            'title'     => trim($title),
            'author'    => trim($author),
            'genre'     => trim($genre),
            'isbn'      => trim($isbn),
            'year'      => $year,
            'copies'    => $copies,
            'available' => $copies,
            'status'    => 'available',
        ];
        $GLOBALS['books'][] = $newBook;
        return $newBook;
    }

    // ── Remove a book by ID ──────────────────────────────────
    public function removeBook(int $bookId): bool {
        foreach ($GLOBALS['books'] as $index => $book) {
            $id = is_array($book) ? $book['id'] : $book->getId();
            if ((int)$id === $bookId) {
                array_splice($GLOBALS['books'], $index, 1);
                return true;
            }
        }
        return false;
    }

    // ── Update a book ────────────────────────────────────────
    public function updateBook(int $bookId, array $updates): bool {
        for ($i = 0; $i < count($GLOBALS['books']); $i++) {
            $id = is_array($GLOBALS['books'][$i]) ? $GLOBALS['books'][$i]['id'] : $GLOBALS['books'][$i]->getId();
            if ((int)$id === $bookId) {
                $allowed = ['title', 'author', 'genre', 'isbn', 'year', 'copies'];
                foreach ($allowed as $field) {
                    if (isset($updates[$field])) {
                        $GLOBALS['books'][$i][$field] = $updates[$field];
                    }
                }
                $GLOBALS['books'][$i]['status'] = $GLOBALS['books'][$i]['available'] > 0 ? 'available' : 'loaned';
                return true;
            }
        }
        return false;
    }