<?php

require_once __DIR__ . '/Database.php';

class BookRequestRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(?string $status = null): array
    {
        $sql = 'SELECT br.*, u.name AS student_name, u.email AS student_email, b.title AS book_title, b.author AS book_author
                FROM book_requests br
                INNER JOIN users u ON u.id = br.user_id
                INNER JOIN books b ON b.id = br.book_id';
        $params = [];

        if ($status && $status !== 'all') {
            $sql .= ' WHERE br.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY br.requested_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function byUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT br.*, b.title AS book_title, b.author AS book_author
             FROM book_requests br
             INNER JOIN books b ON b.id = br.book_id
             WHERE br.user_id = :user_id
             ORDER BY br.requested_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function pendingBookIdsForUser(int $userId): array
    {
        $statement = $this->db->prepare(
            "SELECT book_id FROM book_requests WHERE user_id = :user_id AND status = 'pending'"
        );
        $statement->execute(['user_id' => $userId]);
        return array_map('intval', array_column($statement->fetchAll(), 'book_id'));
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT br.*, u.name AS student_name, u.email AS student_email, b.title AS book_title, b.author AS book_author
             FROM book_requests br
             INNER JOIN users u ON u.id = br.user_id
             INNER JOIN books b ON b.id = br.book_id
             WHERE br.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function create(int $userId, int $bookId, string $message = ''): array
    {
        if ($userId < 1 || $bookId < 1) {
            throw new InvalidArgumentException('Please choose a valid book.');
        }

        $book = $this->db->prepare('SELECT id, copies_available FROM books WHERE id = :id LIMIT 1');
        $book->execute(['id' => $bookId]);
        $bookRow = $book->fetch();

        if (!$bookRow) {
            throw new RuntimeException('Book not found.');
        }

        $duplicate = $this->db->prepare(
            "SELECT id FROM book_requests WHERE user_id = :user_id AND book_id = :book_id AND status = 'pending' LIMIT 1"
        );
        $duplicate->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
        ]);

        if ($duplicate->fetch()) {
            throw new RuntimeException('You already have a pending request for this book.');
        }

        $statement = $this->db->prepare(
            "INSERT INTO book_requests (user_id, book_id, status, message, requested_at, updated_at)
             VALUES (:user_id, :book_id, 'pending', :message, NOW(), NOW())"
        );
        $statement->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
            'message' => trim($message),
        ]);

        $request = $this->findById((int) $this->db->lastInsertId());
        if (!$request) {
            throw new RuntimeException('Request was saved but could not be loaded.');
        }

        return $request;
    }
    public function approve(int $requestId, int $adminId, int $days = 14): array
    {
        $this->db->beginTransaction();

        try {
            $requestStatement = $this->db->prepare(
                'SELECT br.*, b.copies_available, b.title AS book_title
                 FROM book_requests br
                 INNER JOIN books b ON b.id = br.book_id
                 WHERE br.id = :id
                 FOR UPDATE'
            );
            $requestStatement->execute(['id' => $requestId]);
            $request = $requestStatement->fetch();

            if (!$request) {
                throw new RuntimeException('Request not found.');
            }

            if ($request['status'] !== 'pending') {
                throw new RuntimeException('This request has already been reviewed.');
            }

            if ((int) $request['copies_available'] < 1) {
                throw new RuntimeException('This book is not available right now.');
            }

            $issuedAt = date('Y-m-d');
            $dueAt = date('Y-m-d', strtotime('+' . max(1, min(60, $days)) . ' days'));

            $loanStatement = $this->db->prepare(
                "INSERT INTO loans
                    (user_id, book_id, issued_at, due_at, returned_at, status, notes, created_by, renewal_count, created_at, updated_at)
                 VALUES
                    (:user_id, :book_id, :issued_at, :due_at, NULL, 'active', :notes, :created_by, 0, NOW(), NOW())"
            );
            $loanStatement->execute([
                'user_id' => (int) $request['user_id'],
                'book_id' => (int) $request['book_id'],
                'issued_at' => $issuedAt,
                'due_at' => $dueAt,
                'notes' => 'Approved student request #' . $requestId,
                'created_by' => $adminId,
            ]);

            $updateBook = $this->db->prepare(
                "UPDATE books
                 SET copies_available = CASE WHEN copies_available > 0 THEN copies_available - 1 ELSE 0 END,
                     status = CASE WHEN copies_available > 1 THEN 'available' ELSE 'loaned' END,
                     updated_at = NOW()
                 WHERE id = :id AND copies_available > 0"
            );
            $updateBook->execute(['id' => (int) $request['book_id']]);

            if ($updateBook->rowCount() !== 1) {
                throw new RuntimeException('The last copy was just loaned. Please review this request later.');
            }

            $updateRequest = $this->db->prepare(
                "UPDATE book_requests
                 SET status = 'approved', admin_note = :admin_note, reviewed_by = :reviewed_by, reviewed_at = NOW(), updated_at = NOW()
                 WHERE id = :id"
            );
            $updateRequest->execute([
                'id' => $requestId,
                'admin_note' => 'Approved and converted to loan due ' . $dueAt,
                'reviewed_by' => $adminId,
            ]);

            $this->db->commit();

            return [
                'book_title' => $request['book_title'],
                'due_at' => $dueAt,
            ];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function reject(int $requestId, int $adminId, string $note = ''): void
    {
        $statement = $this->db->prepare(
            "UPDATE book_requests
             SET status = 'rejected', admin_note = :admin_note, reviewed_by = :reviewed_by, reviewed_at = NOW(), updated_at = NOW()
             WHERE id = :id AND status = 'pending'"
        );
        $statement->execute([
            'id' => $requestId,
            'admin_note' => trim($note) ?: 'Rejected by librarian.',
            'reviewed_by' => $adminId,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Request not found or already reviewed.');
        }
    }
}
