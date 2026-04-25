<?php
// ============================================================
//  classes/Book.php
//  Book class — OOP me enkapsulim
//  Phase I — jo databaze
// ============================================================

class Book {

    // ── Private properties ───────────────────────────────────
    private int    $id;
    private string $title;
    private string $author;
    private string $genre;
    private string $isbn;
    private int    $year;
    private int    $copies;
    private int    $available;
    private string $status; // 'available' | 'loaned'

    // ── konstruktor ─────────────────────────────────────────
    public function __construct(
        int    $id,
        string $title,
        string $author,
        string $genre     = '',
        string $isbn      = '',
        int    $year      = 0,
        int    $copies    = 1,
        int    $available = 1,
        string $status    = 'available'
    ) {
        $this->id        = $id;
        $this->title     = $title;
        $this->author    = $author;
        $this->genre     = $genre;
        $this->isbn      = $isbn;
        $this->year      = $year;
        $this->copies    = $copies;
        $this->available = $available;
        $this->status    = $available > 0 ? 'available' : 'loaned';
    }

    // ── Marresi ─────────────────────────────────────────────
    public function getId(): int        { return $this->id; }
    public function getTitle(): string  { return $this->title; }
    public function getAuthor(): string { return $this->author; }
    public function getGenre(): string  { return $this->genre; }
    public function getIsbn(): string   { return $this->isbn; }
    public function getYear(): int      { return $this->year; }
    public function getCopies(): int    { return $this->copies; }
    public function getAvailable(): int { return $this->available; }
    public function getStatus(): string { return $this->status; }
        // ── Vendosesi ─────────────────────────────────────────────
    public function setTitle(string $title): void {
        if (strlen(trim($title)) < 1) {
            throw new InvalidArgumentException('Title cannot be empty.');
        }
        $this->title = trim($title);
    }

    public function setAuthor(string $author): void {
        if (strlen(trim($author)) < 1) {
            throw new InvalidArgumentException('Author cannot be empty.');
        }
        $this->author = trim($author);
    }

    public function setCopies(int $copies): void {
        if ($copies < 0) {
            throw new InvalidArgumentException('Copies cannot be negative.');
        }
        $this->copies = $copies;
        if ($this->available > $this->copies) {
            $this->available = $this->copies;
        }
        $this->updateStatus();
    }

    // ── Borrow: decrease available count ────────────────────
    public function borrow(): bool {
        if ($this->available <= 0) {
            return false;
        }
        $this->available--;
        $this->updateStatus();
        return true;
    }

    // ── Return: increase available count ────────────────────
    public function returnBook(): bool {
        if ($this->available >= $this->copies) {
            return false;
        }
        $this->available++;
        $this->updateStatus();
        return true;
    }

    // ── zhvillim statusi ─────────────────
    private function updateStatus(): void {
        $this->status = $this->available > 0 ? 'available' : 'loaned';
    }

    // ── Shiko nese libri eshte i qasshme ───────────────────────────
    public function isAvailable(): bool {
        return $this->available > 0;
    }