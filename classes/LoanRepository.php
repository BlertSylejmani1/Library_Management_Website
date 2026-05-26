<?php

require_once __DIR__ . '/Database.php';

class LoanRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(?string $status = null): array
    {
        $sql = 'SELECT l.*, u.name AS member_name, b.title AS book_title
                FROM loans l
                INNER JOIN users u ON u.id = l.user_id
                INNER JOIN books b ON b.id = l.book_id';

        $params = [];
        if ($status && $status !== 'all') {
            $sql .= ' WHERE l.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY l.created_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function byUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT l.*, b.title AS book_title, b.genre
             FROM loans l
             INNER JOIN books b ON b.id = l.book_id
             WHERE l.user_id = :user_id
             ORDER BY l.created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function recent(int $limit = 5): array
    {
        $statement = $this->db->prepare(
            'SELECT l.*, u.name AS member_name, b.title AS book_title
             FROM loans l
             INNER JOIN users u ON u.id = l.user_id
             INNER JOIN books b ON b.id = l.book_id
             ORDER BY l.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function counts(): array
    {
        $statement = $this->db->query(
            "SELECT
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) AS returned
             FROM loans"
        );

        return $statement->fetch() ?: ['active' => 0, 'overdue' => 0, 'returned' => 0];
    }

    public function create(array $data): int
    {
        $userId = (int) ($data['user_id'] ?? 0);
        $bookId = (int) ($data['book_id'] ?? 0);
        $createdBy = (int) ($data['created_by'] ?? 0);
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($userId < 1 || $bookId < 1 || $createdBy < 1) {
            throw new InvalidArgumentException('Please select a valid student and book.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['issued_at']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['due_at'])) {
            throw new InvalidArgumentException('Loan dates are invalid.');
        }

        $this->db->beginTransaction();

        try {
            $userStatement = $this->db->prepare("SELECT id FROM users WHERE id = :id AND role = 'student' LIMIT 1");
            $userStatement->execute(['id' => $userId]);
            if (!$userStatement->fetch()) {
                throw new RuntimeException('The selected student does not exist.');
            }

            $bookStatement = $this->db->prepare('SELECT copies_available FROM books WHERE id = :id FOR UPDATE');
            $bookStatement->execute(['id' => $bookId]);
            $book = $bookStatement->fetch();

            if (!$book || (int) $book['copies_available'] < 1) {
                throw new RuntimeException('The selected book is currently unavailable.');
            }

            $loanStatement = $this->db->prepare(
                'INSERT INTO loans
                    (user_id, book_id, issued_at, due_at, returned_at, status, notes, created_by, renewal_count, created_at, updated_at)
                 VALUES
                    (:user_id, :book_id, :issued_at, :due_at, NULL, :status, :notes, :created_by, 0, NOW(), NOW())'
            );

            $loanStatement->execute([
                'user_id' => $userId,
                'book_id' => $bookId,
                'issued_at' => $data['issued_at'],
                'due_at' => $data['due_at'],
                'status' => $data['status'],
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $updateBook = $this->db->prepare(
                "UPDATE books
                 SET copies_available = CASE WHEN copies_available > 0 THEN copies_available - 1 ELSE 0 END,
                     status = CASE WHEN copies_available > 1 THEN 'available' ELSE 'loaned' END,
                     updated_at = NOW()
                 WHERE id = :id AND copies_available > 0"
            );
            $updateBook->execute(['id' => $bookId]);

            if ($updateBook->rowCount() !== 1) {
                throw new RuntimeException('The selected book was just loaned by another user. Please choose another book.');
            }

            $this->db->commit();
            return (int) $this->db->lastInsertId();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function renew(int $loanId, int $days = 14, ?int $userId = null): array
    {
        $sql = 'SELECT * FROM loans WHERE id = :id';
        $params = ['id' => $loanId];

        if ($userId !== null) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $loan = $statement->fetch();

        if (!$loan) {
            throw new RuntimeException('Loan not found.');
        }

        if (!in_array($loan['status'], ['active', 'overdue'], true)) {
            throw new RuntimeException('Only active or overdue loans can be renewed.');
        }

        if ((int) $loan['renewal_count'] >= 2) {
            throw new RuntimeException('This loan has already reached the renewal limit.');
        }

        $baseDate = max(strtotime($loan['due_at']), strtotime('today'));
        $newDueDate = date('Y-m-d', strtotime('+' . max(1, min(30, $days)) . ' days', $baseDate));
        $newStatus = $newDueDate < date('Y-m-d') ? 'overdue' : 'active';
        $update = $this->db->prepare(
            'UPDATE loans
             SET due_at = :due_at, status = :status, renewal_count = renewal_count + 1, updated_at = NOW()
             WHERE id = :id'
        );
        $update->execute([
            'id' => $loanId,
            'due_at' => $newDueDate,
            'status' => $newStatus,
        ]);

        return [
            'due_at' => $newDueDate,
            'status' => $newStatus,
            'renewal_count' => (int) $loan['renewal_count'] + 1,
        ];
    }

    public function markReturned(int $loanId): array
    {
        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare('SELECT * FROM loans WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $loanId]);
            $loan = $statement->fetch();

            if (!$loan) {
                throw new RuntimeException('Loan not found.');
            }

            if ($loan['status'] === 'returned') {
                throw new RuntimeException('Loan is already returned.');
            }

            $updateLoan = $this->db->prepare(
                "UPDATE loans
                 SET status = 'returned', returned_at = CURDATE(), updated_at = NOW()
                 WHERE id = :id"
            );
            $updateLoan->execute(['id' => $loanId]);

            $updateBook = $this->db->prepare(
                "UPDATE books
                 SET copies_available = copies_available + 1,
                     status = 'available',
                     updated_at = NOW()
                 WHERE id = :book_id"
            );
            $updateBook->execute(['book_id' => (int) $loan['book_id']]);

            $this->db->commit();

            return [
                'returned_at' => date('Y-m-d'),
                'book_id' => (int) $loan['book_id'],
            ];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
