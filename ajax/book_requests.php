<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/BookRequestRepository.php';

header('Content-Type: application/json');

try {
    requireLogin();

    if (!isPost()) {
        throw new RuntimeException('Invalid request method.');
    }

    verify_csrf_or_fail();

    $currentUser = getSessionUser();
    $repository = new BookRequestRepository(Database::connection());
    $action = $_POST['action'] ?? '';
    $isAdmin = ($currentUser['role'] ?? ROLE_STUDENT) === ROLE_ADMIN;

    if ($action === 'create') {
        if ($isAdmin) {
            throw new RuntimeException('Administrators cannot submit student requests.');
        }

        $bookId = (int) ($_POST['book_id'] ?? 0);
        $request = $repository->create((int) ($currentUser['id'] ?? 0), $bookId, trim($_POST['message'] ?? ''));

        echo json_encode([
            'success' => true,
            'message' => 'Request submitted.',
            'request_id' => (int) $request['id'],
            'book_id' => $bookId,
            'book_title' => $request['book_title'],
            'status' => $request['status'],
            'requested_at' => $request['requested_at'],
            'requests' => $repository->byUser((int) ($currentUser['id'] ?? 0)),
        ]);
        exit;
    }
    if ($action === 'list_mine') {
        if ($isAdmin) {
            throw new RuntimeException('Only students can view personal requests here.');
        }

        echo json_encode([
            'success' => true,
            'requests' => $repository->byUser((int) ($currentUser['id'] ?? 0)),
        ]);
        exit;
    }

    if (!$isAdmin) {
        throw new RuntimeException('Only administrators can review requests.');
    }

    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId < 1) {
        throw new RuntimeException('Request ID is required.');
    }

    if ($action === 'approve') {
        $result = $repository->approve($requestId, (int) ($currentUser['id'] ?? 0));
        echo json_encode([
            'success' => true,
            'message' => 'Request approved and loan created.',
            'request_id' => $requestId,
            'status' => 'approved',
            'due_at' => $result['due_at'],
        ]);
        exit;
    }

    if ($action === 'reject') {
        $repository->reject($requestId, (int) ($currentUser['id'] ?? 0), trim($_POST['note'] ?? ''));
        echo json_encode([
            'success' => true,
            'message' => 'Request rejected.',
            'request_id' => $requestId,
            'status' => 'rejected',
        ]);
        exit;
    }

    throw new RuntimeException('Unsupported request action.');
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
