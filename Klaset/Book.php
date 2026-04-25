<?php
// ============================================================
//  classes/Book.php
//  Book class — OOP with encapsulation
//  Phase I — No Database
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

    // ── Constructor ─────────────────────────────────────────
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

    // ── Getters ─────────────────────────────────────────────
    public function getId(): int        { return $this->id; }
    public function getTitle(): string  { return $this->title; }
    public function getAuthor(): string { return $this->author; }
    public function getGenre(): string  { return $this->genre; }
    public function getIsbn(): string   { return $this->isbn; }
    public function getYear(): int      { return $this->year; }
    public function getCopies(): int    { return $this->copies; }
    public function getAvailable(): int { return $this->available; }
    public function getStatus(): string { return $this->status; }