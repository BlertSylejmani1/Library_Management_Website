<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

define('APP_NAME',    'Library Management System');
define('APP_VERSION', '1.0.0');
define('APP_ROOT',    dirname(__DIR__));
define('BASE_URL',    'http://localhost/library-backend');
define('COOKIE_THEME',  'ath_theme');
define('COOKIE_EXPIRE', time() + 60 * 60 * 24 * 30);
define('ROLE_ADMIN',   'admin');
define('ROLE_STUDENT', 'student');

$GLOBALS['users'] = [
    ['id'=>1,'name'=>'Alexandra Reed','email'=>'admin@library.com','password'=>'admin123','role'=>ROLE_ADMIN,'phone'=>'+383 38 500 600'],
    ['id'=>2,'name'=>'James Carter','email'=>'student@library.com','password'=>'student123','role'=>ROLE_STUDENT,'phone'=>'+383 44 123 456'],
];

$GLOBALS['books'] = [
    ['id'=>1,'title'=>'Clean Code','author'=>'Robert C. Martin','genre'=>'Software Eng.','isbn'=>'978-0132350884','year'=>2008,'copies'=>5,'available'=>3,'status'=>'available'],
    ['id'=>2,'title'=>'The Pragmatic Programmer','author'=>'Hunt & Thomas','genre'=>'Software Eng.','isbn'=>'978-0135957059','year'=>2019,'copies'=>4,'available'=>0,'status'=>'loaned'],
    ['id'=>3,'title'=>'Introduction to Algorithms','author'=>'Cormen et al.','genre'=>'CS Theory','isbn'=>'978-0262046305','year'=>2022,'copies'=>6,'available'=>4,'status'=>'available'],
    ['id'=>4,'title'=>'Computer Networks','author'=>'Tanenbaum & Wetherall','genre'=>'Networking','isbn'=>'978-0132126953','year'=>2010,'copies'=>3,'available'=>1,'status'=>'available'],
    ['id'=>5,'title'=>'Operating System Concepts','author'=>'Silberschatz et al.','genre'=>'OS','isbn'=>'978-1119800361','year'=>2018,'copies'=>4,'available'=>2,'status'=>'available'],
    ['id'=>6,'title'=>'Design Patterns','author'=>'Gang of Four','genre'=>'Software Eng.','isbn'=>'978-0201633610','year'=>1994,'copies'=>3,'available'=>0,'status'=>'loaned'],
    ['id'=>7,'title'=>'Database System Concepts','author'=>'Silberschatz et al.','genre'=>'Databases','isbn'=>'978-0078022159','year'=>2019,'copies'=>4,'available'=>3,'status'=>'available'],
    ['id'=>8,'title'=>'Artificial Intelligence: A Modern Approach','author'=>'Russell & Norvig','genre'=>'AI / ML','isbn'=>'978-0134610993','year'=>2020,'copies'=>3,'available'=>2,'status'=>'available'],
    ['id'=>9,'title'=>'Refactoring','author'=>'Martin Fowler','genre'=>'Software Eng.','isbn'=>'978-0134757599','year'=>2018,'copies'=>5,'available'=>2,'status'=>'available'],
    ['id'=>10,'title'=>'Structure and Interpretation of Computer Programs','author'=>'Abelson & Sussman','genre'=>'CS Theory','isbn'=>'978-0262510875','year'=>1996,'copies'=>2,'available'=>1,'status'=>'available'],
    ['id'=>11,'title'=>'Modern Operating Systems','author'=>'Andrew S. Tanenbaum','genre'=>'OS','isbn'=>'978-0133591620','year'=>2014,'copies'=>3,'available'=>1,'status'=>'available'],
    ['id'=>12,'title'=>'Computer Architecture: A Quantitative Approach','author'=>'Hennessy & Patterson','genre'=>'Architecture','isbn'=>'978-0123838728','year'=>2011,'copies'=>3,'available'=>2,'status'=>'available'],
    ['id'=>13,'title'=>'Deep Learning','author'=>'Goodfellow, Bengio & Courville','genre'=>'AI / ML','isbn'=>'978-0262035613','year'=>2016,'copies'=>4,'available'=>1,'status'=>'available'],
    ['id'=>14,'title'=>'Compilers: Principles, Techniques, and Tools','author'=>'Aho, Lam, Sethi & Ullman','genre'=>'Software Eng.','isbn'=>'978-0321486813','year'=>2006,'copies'=>2,'available'=>0,'status'=>'loaned'],
    ['id'=>15,'title'=>'Distributed Systems','author'=>'Maarten van Steen & Andrew Tanenbaum','genre'=>'Networking','isbn'=>'978-1292025523','year'=>2017,'copies'=>4,'available'=>3,'status'=>'available'],
];

$GLOBALS['loans'] = [
    ['id'=>'L-0841','user_id'=>2,'book_id'=>1,'book'=>'Clean Code','member'=>'James Carter','issued'=>'2026-04-14','due'=>'2026-04-28','status'=>'active'],
    ['id'=>'L-0840','user_id'=>2,'book_id'=>3,'book'=>'Introduction to Algorithms','member'=>'James Carter','issued'=>'2026-04-08','due'=>'2026-04-22','status'=>'overdue'],
    ['id'=>'L-0839','user_id'=>2,'book_id'=>5,'book'=>'Operating System Concepts','member'=>'James Carter','issued'=>'2026-04-05','due'=>'2026-04-19','status'=>'returned'],
    ['id'=>'L-0838','user_id'=>2,'book_id'=>9,'book'=>'Refactoring','member'=>'James Carter','issued'=>'2026-04-01','due'=>'2026-04-15','status'=>'returned'],
    ['id'=>'L-0837','user_id'=>1,'book_id'=>2,'book'=>'The Pragmatic Programmer','member'=>'Alexandra Reed','issued'=>'2026-04-10','due'=>'2026-04-24','status'=>'active'],
    ['id'=>'L-0836','user_id'=>1,'book_id'=>6,'book'=>'Design Patterns','member'=>'Alexandra Reed','issued'=>'2026-04-03','due'=>'2026-04-17','status'=>'overdue'],
    ['id'=>'L-0835','user_id'=>2,'book_id'=>13,'book'=>'Deep Learning','member'=>'James Carter','issued'=>'2026-03-28','due'=>'2026-04-11','status'=>'returned'],
    ['id'=>'L-0834','user_id'=>1,'book_id'=>14,'book'=>'Compilers: Principles, Techniques, and Tools','member'=>'Alexandra Reed','issued'=>'2026-04-12','due'=>'2026-04-26','status'=>'active'],
    ['id'=>'L-0833','user_id'=>2,'book_id'=>4,'book'=>'Computer Networks','member'=>'James Carter','issued'=>'2026-03-30','due'=>'2026-04-13','status'=>'returned'],
    ['id'=>'L-0832','user_id'=>1,'book_id'=>10,'book'=>'Structure and Interpretation of Computer Programs','member'=>'Alexandra Reed','issued'=>'2026-04-07','due'=>'2026-04-21','status'=>'returned'],
];

function getCurrentTheme(): string { return $_COOKIE[COOKIE_THEME] ?? 'dark'; }
function isLoggedIn(): bool { return isset($_SESSION['user']); }
function requireLogin(): void { if (!isLoggedIn()) { header('Location: '.BASE_URL.'/login.php'); exit; } }
function requireAdmin(): void { requireLogin(); if ($_SESSION['user']['role'] !== ROLE_ADMIN) { header('Location: '.BASE_URL.'/pages/dashboard.php'); exit; } }
function getSessionUser(): ?array { return $_SESSION['user'] ?? null; }
function roleLabel(string $role): string { return $role === ROLE_STUDENT ? 'Student' : 'Administrator'; }
