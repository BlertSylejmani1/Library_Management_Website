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