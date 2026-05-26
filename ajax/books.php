<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/BookRepository.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/FileUploader.php';

header('Content-Type: application/json');

try {
    requireAdmin();

    if (!isPost()) {
        throw new RuntimeException('Invalid request method.');
    }

    verify_csrf_or_fail();

    $action = $_POST['action'] ?? '';
    $bookId = (int) ($_POST['book_id'] ?? 0);
    $repository = new BookRepository(Database::connection());

    if ($action !== 'delete' || $bookId < 1) {
        throw new RuntimeException('Invalid book action.');
    }

    $cover = $repository->delete($bookId);
    FileUploader::deleteBookCover($cover);

    echo json_encode([
        'success' => true,
        'message' => 'Book deleted successfully.',
        'book_id' => $bookId,
    ]);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
