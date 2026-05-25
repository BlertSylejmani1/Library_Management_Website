<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/LoanRepository.php';

header('Content-Type: application/json');

try {
    requireLogin();

    if (!isPost()) {
        throw new RuntimeException('Invalid request method.');
    }

    verify_csrf_or_fail();

    $action = $_POST['action'] ?? '';
    $loanId = (int) ($_POST['loan_id'] ?? 0);
    $repository = new LoanRepository(Database::connection());
    $currentUser = getSessionUser();
    $isAdmin = ($currentUser['role'] ?? ROLE_STUDENT) === ROLE_ADMIN;

    if ($loanId < 1) {
        throw new RuntimeException('Loan ID is required.');
    }

    if ($action === 'return') {
        if (!$isAdmin) {
            throw new RuntimeException('Only administrators can mark loans as returned.');
        }

        $result = $repository->markReturned($loanId);
        echo json_encode([
            'success' => true,
            'message' => 'Loan marked as returned.',
            'loan_id' => $loanId,
            'status' => 'returned',
            'returned_at' => $result['returned_at'],
        ]);
        exit;
    }