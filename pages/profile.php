<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';

requireLogin();

$currentUser = getSessionUser();
$activePage = 'profile';
$pageTitle = 'My Profile';
$pageSubtitle = 'Manage your account and preferences.';
$isStudent = ($currentUser['role'] ?? ROLE_STUDENT) === ROLE_STUDENT;

$profileError = '';
$profileSaved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedName = trim($_POST['name'] ?? '');
    $submittedEmail = trim($_POST['email'] ?? '');
    $submittedPhone = trim($_POST['phone'] ?? '');
    $submittedLocation = trim($_POST['location'] ?? '');
    $submittedBio = trim($_POST['bio'] ?? '');
    $submittedStudentId = trim($_POST['studentId'] ?? '');
    $submittedFaculty = trim($_POST['faculty'] ?? '');

    try {
        $sessionUser = User::fromArray([
            'id' => $currentUser['id'],
            'name' => $currentUser['name'],
            'email' => $currentUser['email'],
            'password' => '',
            'role' => $currentUser['role'],
            'phone' => $currentUser['phone'] ?? '',
        ]);

        $sessionUser->setName($submittedName);

        if (!User::validateEmail($submittedEmail)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }
        if (!User::validatePhone($submittedPhone)) {
            throw new InvalidArgumentException('Please enter a valid phone number.');
        }

        $sessionUser->setEmail($submittedEmail);
        $sessionUser->setPhone($submittedPhone);

        $_SESSION['user']['name'] = $sessionUser->getName();
        $_SESSION['user']['email'] = $sessionUser->getEmail();
        $_SESSION['user']['phone'] = $sessionUser->getPhone();

        $currentUser = getSessionUser();
        $profileSaved = true;
    } catch (InvalidArgumentException $exception) {
        $profileError = $exception->getMessage();
    }
}

$profile = [
    'name' => $currentUser['name'] ?? 'Alexandra Reed',
    'email' => $currentUser['email'] ?? 'admin@library.com',
    'phone' => $currentUser['phone'] ?? ($isStudent ? '+383 44 123 456' : '+383 38 500 600'),
    'role' => roleLabel($currentUser['role'] ?? ROLE_ADMIN),
    'location' => 'Prishtina, Kosovo',
    'bio' => $isStudent
        ? 'Computer Science student at the University of Prishtina. Passionate about algorithms and software engineering.'
        : 'Head librarian with 12 years of experience in cataloguing and collection management.',
    'studentId' => $isStudent ? 'UP-2024-0042' : '',
    'faculty' => $isStudent ? 'Faculty of Electrical and Computer Engineering' : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profile['name'] = trim($_POST['name'] ?? $profile['name']);
    $profile['email'] = trim($_POST['email'] ?? $profile['email']);
    $profile['phone'] = trim($_POST['phone'] ?? $profile['phone']);
    $profile['location'] = trim($_POST['location'] ?? $profile['location']);
    $profile['bio'] = trim($_POST['bio'] ?? $profile['bio']);
    if ($isStudent) {
        $profile['studentId'] = trim($_POST['studentId'] ?? $profile['studentId']);
        $profile['faculty'] = trim($_POST['faculty'] ?? $profile['faculty']);
    }
}

$profileStats = $isStudent
    ? [
        ['label' => 'Active Loans', 'value' => '1'],
        ['label' => 'Total Borrowed', 'value' => '3'],
        ['label' => 'Books Returned', 'value' => '2'],
    ]
    : [
        ['label' => 'Books Managed', 'value' => '12,847'],
        ['label' => 'Active Loans', 'value' => '384'],
        ['label' => 'Members', 'value' => '2,491'],
    ];

$preferences = $isStudent
    ? [
        ['label' => 'Email reminders for due dates', 'desc' => 'Get notified 3 days before a book is due', 'checked' => true],
        ['label' => 'New book alerts', 'desc' => 'Be notified when new CS books are added', 'checked' => true],
        ['label' => 'Overdue notifications', 'desc' => 'Receive alerts when a loan is overdue', 'checked' => false],
    ]
    : [
        ['label' => 'Email notifications for overdue books', 'desc' => 'Receive daily digest of overdue loans', 'checked' => true],
        ['label' => 'New member alerts', 'desc' => 'Be notified when new members register', 'checked' => true],
        ['label' => 'Low stock alerts', 'desc' => 'Alert when book copies fall below 1', 'checked' => false],
    ];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>