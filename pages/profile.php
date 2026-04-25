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
<div class="profile-page">
    <?php if ($profileSaved): ?>
        <div class="page-alert alert-success" style="position: fixed; top: 90px; right: 1.75rem; z-index: 999;">
            ✅ Profile updated successfully.
        </div>
    <?php endif; ?>

    <?php if ($profileError !== ''): ?>
        <div class="page-alert alert-error" style="position: fixed; top: 90px; right: 1.75rem; z-index: 999;">
            ❌ <?= htmlspecialchars($profileError) ?>
        </div>
    <?php endif; ?>

    <div class="profile-layout">
        <div class="profile-sidebar-card">
            <div class="profile-avatar-wrap">
                <div class="profile-avatar-xl"><?= strtoupper(substr($profile['name'], 0, 1)) ?></div>
                <button class="profile-avatar-edit" type="button">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
            </div>
            <h3 class="profile-name" data-profile-preview="name"><?= htmlspecialchars($profile['name']) ?></h3>
            <span class="profile-role-tag"><?= htmlspecialchars($profile['role']) ?></span>
            <p class="profile-bio" data-profile-preview="bio"><?= htmlspecialchars($profile['bio']) ?></p>

            <div class="profile-stats">
                <?php foreach ($profileStats as $stat): ?>
                    <div class="ps-stat">
                        <span class="ps-val"><?= htmlspecialchars($stat['value']) ?></span>
                        <span class="ps-label"><?= htmlspecialchars($stat['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="profile-meta-list">
                <div class="profile-meta-item"><?= htmlspecialchars($profile['email']) ?></div>
                <div class="profile-meta-item" data-profile-preview="phone"><?= htmlspecialchars($profile['phone']) ?></div>
                <div class="profile-meta-item" data-profile-preview="location"><?= htmlspecialchars($profile['location']) ?></div>
                <?php if ($isStudent && $profile['studentId'] !== ''): ?>
                    <div class="profile-meta-item" data-profile-preview="studentId"><?= htmlspecialchars($profile['studentId']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-form-area">
            <form method="post" action="<?= BASE_URL ?>/pages/profile.php" data-profile-form>
                <div class="profile-section">
                    <h3>Personal Information</h3>
                    <div class="form-row">
                        <div class="form-field">
                            <label>Full Name</label>
                            <input name="name" value="<?= htmlspecialchars($profile['name']) ?>" data-profile-field="name" required />
                        </div>
                        <div class="form-field">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label>Phone Number</label>
                            <input name="phone" value="<?= htmlspecialchars($profile['phone']) ?>" data-profile-field="phone" required />
                        </div>
                        <div class="form-field">
                            <label>Location</label>
                            <input name="location" value="<?= htmlspecialchars($profile['location']) ?>" data-profile-field="location" />
                        </div>
                    </div>
                    <?php if ($isStudent): ?>
                        <div class="form-row">
                            <div class="form-field">
                                <label>Student ID</label>
                                <input name="studentId" value="<?= htmlspecialchars($profile['studentId']) ?>" data-profile-field="studentId" />
                            </div>
                            <div class="form-field">
                                <label>Faculty</label>
                                <input name="faculty" value="<?= htmlspecialchars($profile['faculty']) ?>" />
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="form-field">
                        <label>Bio</label>
                        <textarea name="bio" rows="3" data-profile-field="bio"><?= htmlspecialchars($profile['bio']) ?></textarea>
                    </div>
                </div>
