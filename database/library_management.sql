CREATE DATABASE IF NOT EXISTS library_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS book_requests;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

DROP FUNCTION IF EXISTS request_status_label;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    phone VARCHAR(30) NOT NULL,
    location VARCHAR(120) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    student_id VARCHAR(40) DEFAULT NULL,
    faculty VARCHAR(120) DEFAULT NULL,
    department VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    author VARCHAR(180) NOT NULL,
    genre VARCHAR(80) NOT NULL,
    isbn VARCHAR(32) NOT NULL UNIQUE,
    publication_year SMALLINT UNSIGNED NOT NULL,
    copies_total INT UNSIGNED NOT NULL DEFAULT 1,
    copies_available INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('available', 'loaned') NOT NULL DEFAULT 'available',
    cover_image VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    issued_at DATE NOT NULL,
    due_at DATE NOT NULL,
    returned_at DATE DEFAULT NULL,
    status ENUM('active', 'overdue', 'returned') NOT NULL DEFAULT 'active',
    notes VARCHAR(255) DEFAULT NULL,
    created_by INT UNSIGNED NOT NULL,
    renewal_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_loans_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_loans_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE book_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    message VARCHAR(255) DEFAULT NULL,
    admin_note VARCHAR(255) DEFAULT NULL,
    reviewed_by INT UNSIGNED DEFAULT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_book_requests_status (status),
    INDEX idx_book_requests_user_status (user_id, status),
    CONSTRAINT fk_book_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_book_requests_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_book_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

DELIMITER //

CREATE TRIGGER trg_books_status_before_insert
BEFORE INSERT ON books
FOR EACH ROW
BEGIN
    SET NEW.copies_available = LEAST(NEW.copies_available, NEW.copies_total);
    SET NEW.status = CASE WHEN NEW.copies_available > 0 THEN 'available' ELSE 'loaned' END;
END//

CREATE TRIGGER trg_books_status_before_update
BEFORE UPDATE ON books
FOR EACH ROW
BEGIN
    SET NEW.copies_available = LEAST(NEW.copies_available, NEW.copies_total);
    SET NEW.status = CASE WHEN NEW.copies_available > 0 THEN 'available' ELSE 'loaned' END;
END//

CREATE TRIGGER trg_loans_validate_dates_before_insert
BEFORE INSERT ON loans
FOR EACH ROW
BEGIN
    IF NEW.due_at < NEW.issued_at THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Loan due date cannot be before issue date.';
    END IF;
END//

CREATE FUNCTION request_status_label(p_status VARCHAR(20))
RETURNS VARCHAR(20)
DETERMINISTIC
NO SQL
BEGIN
    RETURN CASE p_status
        WHEN 'pending' THEN 'Pending'
        WHEN 'approved' THEN 'Approved'
        WHEN 'rejected' THEN 'Rejected'
        ELSE 'Unknown'
    END;
END//

DELIMITER ;


INSERT INTO users (id, name, email, password, role, phone, location, bio, student_id, faculty, department) VALUES
(1, 'Alexandra Reed', 'admin@library.com', '$2y$10$dOCo5jvFFveRe6uA5Druc.0fuu9Oc7OYRC2qtVhkkifogJbD3Bjne', 'admin', '+383 38 500 600', 'Prishtina, Kosovo', 'Head librarian with experience in collection management.', NULL, NULL, 'Library Administration'),
(2, 'James Carter', 'student@library.com', '$2y$10$1xjQ9RcNZ3V8BARnh/LpieYgZu95ygbxdsOWxRQJv.e9mHgxdY4IC', 'student', '+383 44 123 456', 'Prishtina, Kosovo', 'Computer Science student interested in algorithms and software engineering.', 'UP-2026-0042', 'Faculty of Electrical and Computer Engineering', NULL),
(3, 'Arta Berisha', 'arta.berisha@uni-pr.edu', '$2y$10$1xjQ9RcNZ3V8BARnh/LpieYgZu95ygbxdsOWxRQJv.e9mHgxdY4IC', 'student', '+383 45 222 333', 'Prishtina, Kosovo', 'Undergraduate student and frequent library visitor.', 'UP-2026-0103', 'Faculty of Computer Science', NULL),
(4, 'Liridon Krasniqi', 'liridon.krasniqi@uni-pr.edu', '$2y$10$1xjQ9RcNZ3V8BARnh/LpieYgZu95ygbxdsOWxRQJv.e9mHgxdY4IC', 'student', '+383 49 555 777', 'Prishtina, Kosovo', 'Networks enthusiast and lab assistant.', 'UP-2025-0188', 'Faculty of Engineering', NULL);

INSERT INTO books (id, title, author, genre, isbn, publication_year, copies_total, copies_available, status, cover_image, description) VALUES
(1, 'Clean Code', 'Robert C. Martin', 'Software Eng.', '9780132350884', 2008, 5, 4, 'available', NULL, 'A handbook of agile software craftsmanship.'),
(2, 'The Pragmatic Programmer', 'Andrew Hunt & David Thomas', 'Software Eng.', '9780135957059', 2019, 4, 3, 'available', NULL, 'Classic practical advice for software developers.'),
(3, 'Introduction to Algorithms', 'Cormen et al.', 'CS Theory', '9780262046305', 2022, 6, 5, 'available', NULL, 'Foundational algorithms reference.'),
(4, 'Computer Networks', 'Tanenbaum & Wetherall', 'Networking', '9780132126953', 2010, 3, 3, 'available', NULL, 'Computer networking concepts and protocols.'),
(5, 'Operating System Concepts', 'Silberschatz et al.', 'OS', '9781119800361', 2018, 4, 4, 'available', NULL, 'Operating systems fundamentals.'),
(6, 'Design Patterns', 'Gang of Four', 'Software Eng.', '9780201633610', 1994, 3, 2, 'available', NULL, 'Elements of reusable object-oriented software.'),
(7, 'Database System Concepts', 'Silberschatz et al.', 'Databases', '9780078022159', 2019, 4, 3, 'available', NULL, 'Database systems theory and implementation.'),
(8, 'Artificial Intelligence: A Modern Approach', 'Russell & Norvig', 'AI / ML', '9780134610993', 2020, 3, 2, 'available', NULL, 'Comprehensive AI textbook.'),
(9, 'Refactoring', 'Martin Fowler', 'Software Eng.', '9780134757599', 2018, 5, 4, 'available', NULL, 'Improving existing code safely.'),
(10, 'Structure and Interpretation of Computer Programs', 'Abelson & Sussman', 'CS Theory', '9780262510875', 1996, 2, 2, 'available', NULL, 'Classic computer science text.'),
(11, 'Modern Operating Systems', 'Andrew S. Tanenbaum', 'OS', '9780133591620', 2014, 3, 1, 'available', NULL, 'Modern operating system architecture and design.'),
(12, 'Computer Architecture: A Quantitative Approach', 'Hennessy & Patterson', 'Architecture', '9780123838728', 2011, 3, 2, 'available', NULL, 'Architecture performance and design.'),
(13, 'Deep Learning', 'Goodfellow, Bengio & Courville', 'AI / ML', '9780262035613', 2016, 4, 3, 'available', NULL, 'Deep learning principles and applications.'),
(14, 'Compilers: Principles, Techniques, and Tools', 'Aho, Lam, Sethi & Ullman', 'Software Eng.', '9780321486813', 2006, 2, 1, 'available', NULL, 'Compiler design fundamentals.'),
(15, 'Distributed Systems', 'Maarten van Steen & Andrew Tanenbaum', 'Networking', '9781292025523', 2017, 4, 3, 'available', NULL, 'Distributed systems concepts and practice.'),
(16, 'Fluent Python', 'Luciano Ramalho', 'Software Eng.', '9781492056355', 2022, 3, 2, 'available', NULL, 'Practical Python programming techniques.'),
(17, 'You Don''t Know JS Yet', 'Kyle Simpson', 'Software Eng.', '9781098124045', 2020, 2, 2, 'available', NULL, 'Deep JavaScript language fundamentals.'),
(18, 'Data-Intensive Applications', 'Martin Kleppmann', 'Databases', '9781449373320', 2017, 4, 3, 'available', NULL, 'Reliable and scalable data systems.'),
(19, 'Computer Security', 'William Stallings', 'Networking', '9780134794105', 2018, 2, 1, 'available', NULL, 'Security principles and practice.'),
(20, 'Hands-On Machine Learning', 'Aurelien Geron', 'AI / ML', '9781098125974', 2022, 3, 3, 'available', NULL, 'Applied machine learning with Python.'),
(21, 'SQL Antipatterns', 'Bill Karwin', 'Databases', '9781934356555', 2010, 2, 2, 'available', NULL, 'Common SQL mistakes and better designs.'),
(22, 'Software Architecture in Practice', 'Bass, Clements & Kazman', 'Architecture', '9780136886099', 2021, 3, 3, 'available', NULL, 'Architecture design and evaluation.');

INSERT INTO loans (id, user_id, book_id, issued_at, due_at, returned_at, status, notes, created_by, renewal_count) VALUES
(1, 2, 1, '2026-05-18', '2026-06-01', NULL, 'active', 'Borrowed from front desk.', 1, 0),
(2, 2, 3, '2026-05-01', '2026-05-15', NULL, 'overdue', 'Reminder sent by librarian.', 1, 1),
(3, 2, 5, '2026-04-20', '2026-05-04', '2026-05-02', 'returned', 'Returned in good condition.', 1, 0),
(4, 2, 9, '2026-04-17', '2026-05-01', '2026-04-30', 'returned', 'Returned one day early.', 1, 0),
(5, 2, 2, '2026-05-20', '2026-06-03', NULL, 'active', 'Course assignment reading.', 1, 0),
(6, 3, 6, '2026-04-28', '2026-05-12', NULL, 'overdue', 'Flagged for follow-up.', 1, 0),
(7, 3, 13, '2026-04-22', '2026-05-06', '2026-05-05', 'returned', 'Returned via circulation desk.', 1, 0),
(8, 4, 14, '2026-05-06', '2026-06-02', NULL, 'active', 'Compiler course reading.', 1, 1),
(9, 3, 4, '2026-04-18', '2026-05-02', '2026-05-01', 'returned', 'No remarks.', 1, 0),
(10, 4, 10, '2026-04-25', '2026-05-09', '2026-05-08', 'returned', 'Returned with notes attached.', 1, 0),
(11, 3, 9, '2026-05-19', '2026-06-02', NULL, 'active', 'Refactoring workshop.', 1, 0),
(12, 4, 13, '2026-05-11', '2026-05-25', NULL, 'active', 'Machine learning seminar.', 1, 0),
(13, 2, 15, '2026-05-02', '2026-05-16', NULL, 'overdue', 'Second reminder pending.', 1, 1),
(14, 3, 16, '2026-05-21', '2026-06-04', NULL, 'active', 'Python lab reference.', 1, 0),
(15, 4, 18, '2026-05-15', '2026-05-29', NULL, 'active', 'Database systems project.', 1, 0),
(16, 2, 19, '2026-05-03', '2026-05-17', NULL, 'overdue', 'Security module reading.', 1, 0);

INSERT INTO book_requests (id, user_id, book_id, status, message, admin_note, reviewed_by, requested_at, reviewed_at) VALUES
(1, 2, 20, 'pending', 'Needed for machine learning practice.', NULL, NULL, '2026-05-24 09:15:00', NULL),
(2, 3, 21, 'pending', 'Useful for the database assignment.', NULL, NULL, '2026-05-24 11:20:00', NULL),
(3, 4, 17, 'approved', 'Requested for JavaScript revision.', 'Approved and converted to a loan.', 1, '2026-05-18 10:00:00', '2026-05-18 10:30:00'),
(4, 2, 22, 'rejected', 'Wanted for architecture reading group.', 'Please request again next week.', 1, '2026-05-17 12:10:00', '2026-05-17 15:45:00');
