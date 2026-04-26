<?php
// ============================================================
//  classes/User.php
//  Klasa bazë User — OOP me enkapsulim
//  Përdor: konstruktor, getters, setters, validim
//  Faza I — Pa bazë të dhënash
// ============================================================

class User {

    // ── Atributet private (enkapsulim) ──────────────────
    private int    $id;
    private string $name;
    private string $email;
    private string $password;
    private string $role;
    private string $phone;

    // ── Konstruktori ─────────────────────────────────────────
    public function __construct(
        int    $id,
        string $name,
        string $email,
        string $password,
        string $role  = 'student',
        string $phone = ''
    ) {
        $this->id       = $id;
        $this->name     = $name;
        $this->email    = $email;
        $this->password = $password;
        $this->role     = $role;
        $this->phone    = $phone;
    }

    // ── Getters ─────────────────────────────────────────────

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getRole(): string {
        return $this->role;
    }

    public function getPhone(): string {
        return $this->phone;
    }

    // Password getter kthen version të maskuar për siguri
    public function getMaskedPassword(): string {
        return str_repeat('*', strlen($this->password));
    }
        // ── Setters (me validim bazë) ─────────────────────

    public function setName(string $name): void {
        // Emri duhet të ketë të paktën 2 karaktere
        if (strlen(trim($name)) < 2) {
            throw new InvalidArgumentException('Name must be at least 2 characters.');
        }
        $this->name = trim($name);
    }

    public function setEmail(string $email): void {
        // Validon email-in me regex
        if (!self::validateEmail($email)) {
            throw new InvalidArgumentException("Invalid email format: $email");
        }
        $this->email = strtolower(trim($email));
    }

    public function setPhone(string $phone): void {
        // Validon numrin e telefonit me regex
        if (!self::validatePhone($phone)) {
            throw new InvalidArgumentException("Invalid phone format: $phone");
        }
        $this->phone = trim($phone);
    }

    public function setPassword(string $password): void {
        // Password duhet të ketë të paktën 6 karaktere
        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Password must be at least 6 characters.');
        }
        $this->password = $password;
    }

    public function setRole(string $role): void {
        $allowed = ['admin', 'student'];
        if (!in_array($role, $allowed)) {
            throw new InvalidArgumentException("Role must be one of: " . implode(', ', $allowed));
        }
        $this->role = $role;
    }

    // ── Kontrollo password-in ───────────────────────────────
    // Krahason password-in (pa hashing — Faza I)
    // Faza II do përdorë password_verify()
    public function checkPassword(string $input): bool {
        return $input === $this->password;
    }

    // ── Validim me REGEX (statik — i ripërdorshëm) ────────

    /**
     * Validon formatin e email-it
     */
    public static function validateEmail(string $email): bool {
        $pattern = '/^[a-zA-Z0-9._\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';
        return (bool) preg_match($pattern, $email);
    }

    /**
     * Validon numrin e telefonit
     */
    public static function validatePhone(string $phone): bool {
        $pattern = '/^\+?[\d\s\(\)\-]{7,20}$/';
        return (bool) preg_match($pattern, $phone);
    }
        // ── Shndërro përdoruesin në array ───────────────────────
    public function toArray(): array {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
            'phone' => $this->phone,
        ];
    }

    // ── Paraqitje si tekst ────────────────────────────────
    public function __toString(): string {
        return "[User #{$this->id}] {$this->name} ({$this->email}) — Role: {$this->role}";
    }

    // ── Krijo User nga array ───────────────────────────────
    public static function fromArray(array $data): self {
        return new self(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['password'],
            $data['role']  ?? 'student',
            $data['phone'] ?? ''
        );
    }

    // ── Gjej përdorues sipas email-it ───────────────────────
    public static function findByEmail(string $email): ?self {
        foreach ($GLOBALS['users'] as $userData) {
            if (strtolower($userData['email']) === strtolower($email)) {
                return self::fromArray($userData);
            }
        }
        return null;
    }

    // ── Autentikim (login) ──────────────────────────────────
    public static function authenticate(string $email, string $password): ?self {
        $user = self::findByEmail($email);
        if ($user && $user->checkPassword($password)) {
            return $user;
        }
        return null;
    }
}