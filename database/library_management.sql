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
