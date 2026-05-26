<?php

require_once __DIR__ . '/Database.php';

class BookRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(string $search = '', ?string $genre = null): array
    {
        $sql = 'SELECT * FROM books WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (title LIKE :search OR author LIKE :search OR isbn LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($genre && $genre !== 'All') {
            $sql .= ' AND genre = :genre';
            $params['genre'] = $genre;
        }

        $sql .= ' ORDER BY created_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM books')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM books WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO books
                (title, author, genre, isbn, publication_year, copies_total, copies_available, status, cover_image, description, created_at, updated_at)
             VALUES
                (:title, :author, :genre, :isbn, :publication_year, :copies_total, :copies_available, :status, :cover_image, :description, NOW(), NOW())'
        );

        $statement->execute([
            'title' => trim($data['title']),
            'author' => trim($data['author']),
            'genre' => trim($data['genre']),
            'isbn' => trim($data['isbn']),
            'publication_year' => (int) $data['publication_year'],
            'copies_total' => (int) $data['copies_total'],
            'copies_available' => (int) $data['copies_available'],
            'status' => ((int) $data['copies_available']) > 0 ? 'available' : 'loaned',
            'cover_image' => $data['cover_image'],
            'description' => trim($data['description']),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE books
             SET title = :title,
                 author = :author,
                 genre = :genre,
                 isbn = :isbn,
                 publication_year = :publication_year,
                 copies_total = :copies_total,
                 copies_available = :copies_available,
                 status = :status,
                 cover_image = :cover_image,
                 description = :description,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'title' => trim($data['title']),
            'author' => trim($data['author']),
            'genre' => trim($data['genre']),
            'isbn' => trim($data['isbn']),
            'publication_year' => (int) $data['publication_year'],
            'copies_total' => (int) $data['copies_total'],
            'copies_available' => (int) $data['copies_available'],
            'status' => ((int) $data['copies_available']) > 0 ? 'available' : 'loaned',
            'cover_image' => $data['cover_image'],
            'description' => trim($data['description']),
        ]);
    }

    public function delete(int $id): ?string
    {
        $book = $this->findById($id);
        if (!$book) {
            return null;
        }

        $activeLoans = $this->db->prepare("SELECT COUNT(*) FROM loans WHERE book_id = :id AND status IN ('active', 'overdue')");
        $activeLoans->execute(['id' => $id]);
        if ((int) $activeLoans->fetchColumn() > 0) {
            throw new RuntimeException('This book has active loan records and cannot be deleted yet.');
        }

        $statement = $this->db->prepare('DELETE FROM books WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $book['cover_image'] ?: null;
    }

    public function availableForLoans(): array
    {
        $statement = $this->db->query('SELECT id, title, copies_available FROM books WHERE copies_available > 0 ORDER BY title ASC');
        return $statement->fetchAll();
    }

    public function popular(int $limit = 5): array
    {
        $statement = $this->db->prepare(
            'SELECT b.*, COALESCE(loan_stats.loans_count, 0) AS loans_count
             FROM books b
             LEFT JOIN (
                SELECT book_id, COUNT(*) AS loans_count
                FROM loans
                GROUP BY book_id
             ) AS loan_stats ON loan_stats.book_id = b.id
             ORDER BY loans_count DESC, b.title ASC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}

