<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';

class UserRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(?string $role = null, string $search = ''): array
    {
        $sql = 'SELECT * FROM users WHERE 1=1';
        $params = [];

        if ($role && in_array($role, [ROLE_ADMIN, ROLE_STUDENT], true)) {
            $sql .= ' AND role = :role';
            $params['role'] = $role;
        }

        if ($search !== '') {
            $sql .= ' AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY created_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);
        return $statement->fetch() ?: null;
    }

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        $stored = (string) ($user['password'] ?? '');
        $verified = password_verify($password, $stored);

        if (!$verified && hash_equals($stored, $password)) {
            $this->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
            $user['password'] = $password;
            $verified = true;
        }

        return $verified ? $user : null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO users
                (name, email, password, role, phone, location, bio, student_id, faculty, department, created_at, updated_at)
             VALUES
                (:name, :email, :password, :role, :phone, :location, :bio, :student_id, :faculty, :department, NOW(), NOW())'
        );

        $statement->execute([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'],
            'phone' => trim($data['phone']),
            'location' => trim($data['location']),
            'bio' => trim($data['bio']),
            'student_id' => trim($data['student_id']),
            'faculty' => trim($data['faculty']),
            'department' => trim($data['department']),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE users
             SET name = :name,
                 email = :email,
                 role = :role,
                 phone = :phone,
                 location = :location,
                 bio = :bio,
                 student_id = :student_id,
                 faculty = :faculty,
                 department = :department,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'role' => $data['role'],
            'phone' => trim($data['phone']),
            'location' => trim($data['location']),
            'bio' => trim($data['bio']),
            'student_id' => trim($data['student_id']),
            'faculty' => trim($data['faculty']),
            'department' => trim($data['department']),
        ]);

        if (!empty($data['password'])) {
            $this->updatePasswordHash($id, password_hash($data['password'], PASSWORD_DEFAULT));
        }
    }

    public function updateProfile(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE users
             SET name = :name,
                 email = :email,
                 phone = :phone,
                 location = :location,
                 bio = :bio,
                 student_id = :student_id,
                 faculty = :faculty,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => trim($data['phone']),
            'location' => trim($data['location']),
            'bio' => trim($data['bio']),
            'student_id' => trim($data['student_id']),
            'faculty' => trim($data['faculty']),
        ]);
    }

    public function changePassword(int $id, string $newPassword): void
    {
        $this->updatePasswordHash($id, password_hash($newPassword, PASSWORD_DEFAULT));
    }

    public function delete(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function updatePasswordHash(int $id, string $hash): void
    {
        $statement = $this->db->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            'id' => $id,
            'password' => $hash,
        ]);
    }
}
