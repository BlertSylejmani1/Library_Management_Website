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
        // ── Process a new loan ───────────────────────────────────
    public function processLoan(int $userId, int $bookId): array {
        for ($i = 0; $i < count($GLOBALS['books']); $i++) {
            $entry = $GLOBALS['books'][$i];
            $id    = is_array($entry) ? (int)$entry['id'] : $entry->getId();

            if ($id === $bookId) {
                $avail = is_array($entry) ? (int)$entry['available'] : $entry->getAvailable();
                if ($avail <= 0) {
                    return ['success' => false, 'message' => 'No copies available for this book.'];
                }

                $GLOBALS['books'][$i]['available']--;
                $GLOBALS['books'][$i]['status'] = $GLOBALS['books'][$i]['available'] > 0 ? 'available' : 'loaned';

                $maxLoanNum = 841;
                foreach ($GLOBALS['loans'] as $loan) {
                    $num = (int) str_replace('L-', '', $loan['id']);
                    if ($num > $maxLoanNum) $maxLoanNum = $num;
                }
                $loanId = 'L-' . str_pad($maxLoanNum + 1, 4, '0', STR_PAD_LEFT);

                $memberName = 'Unknown';
                foreach ($GLOBALS['users'] as $u) {
                    if ((int)$u['id'] === $userId) { $memberName = $u['name']; break; }
                }

                $newLoan = [
                    'id'      => $loanId,
                    'user_id' => $userId,
                    'book_id' => $bookId,
                    'book'    => $GLOBALS['books'][$i]['title'],
                    'member'  => $memberName,
                    'issued'  => date('Y-m-d'),
                    'due'     => date('Y-m-d', strtotime('+14 days')),
                    'status'  => 'active',
                ];
                $GLOBALS['loans'][] = $newLoan;
                return ['success' => true, 'message' => 'Loan created.', 'loan' => $newLoan];
            }
        }
        return ['success' => false, 'message' => 'Book not found.'];
    }

    // ── Process a return ─────────────────────────────────────
    public function processReturn(string $loanId): array {
        for ($i = 0; $i < count($GLOBALS['loans']); $i++) {
            if ($GLOBALS['loans'][$i]['id'] === $loanId) {
                if ($GLOBALS['loans'][$i]['status'] === 'returned') {
                    return ['success' => false, 'message' => 'This loan is already returned.'];
                }
                $GLOBALS['loans'][$i]['status'] = 'returned';
                $bookId = (int)$GLOBALS['loans'][$i]['book_id'];

                for ($j = 0; $j < count($GLOBALS['books']); $j++) {
                    $bid = is_array($GLOBALS['books'][$j]) ? (int)$GLOBALS['books'][$j]['id'] : $GLOBALS['books'][$j]->getId();
                    if ($bid === $bookId) {
                        $GLOBALS['books'][$j]['available']++;
                        $GLOBALS['books'][$j]['status'] = 'available';
                        break;
                    }
                }
                return ['success' => true, 'message' => 'Book returned successfully.'];
            }
        }
        return ['success' => false, 'message' => 'Loan not found.'];
    }

    public function getAllLoans(): array { return $GLOBALS['loans']; }

    public function getDashboardStats(): array {
        $active = $overdue = 0;
        foreach ($GLOBALS['loans'] as $loan) {
            if ($loan['status'] === 'active')  $active++;
            if ($loan['status'] === 'overdue') $overdue++;
        }
        return [
            'total_books'   => count($GLOBALS['books']),
            'active_loans'  => $active,
            'overdue'       => $overdue,
            'total_members' => count($GLOBALS['users']),
        ];
    }

    public function __toString(): string {
        return "[Admin #{$this->getId()}] {$this->getName()} ({$this->getEmail()}) — Dept: {$this->department}";
    }
}