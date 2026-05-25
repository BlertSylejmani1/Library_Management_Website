<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Mailer.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/UserRepository.php';

header('Content-Type: application/json');

try {
    if (!isPost()) {
        throw new RuntimeException('Invalid request method.');
    }

    verify_csrf_or_fail();

    $email = trim($_POST['email'] ?? '');
    if (!User::validateEmail($email)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    $repository = new UserRepository(Database::connection());
    $user = $repository->findByEmail($email);

    if ($user) {
        Mailer::send(
            $email,
            'Library password reset request',
            "Hello {$user['name']},\n\nA password reset was requested for your account. This Phase II demo logs reset messages locally when mail is not configured.\n\nIf you did not request this, please ignore this email."
        );
    } else {
        Mailer::send(
            $email,
            'Library password reset request',
            "A reset request was received for this email address. If the account exists, you can ignore this demo message."
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Reset request processed successfully.',
    ]);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
