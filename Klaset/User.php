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