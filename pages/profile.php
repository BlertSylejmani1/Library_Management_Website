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