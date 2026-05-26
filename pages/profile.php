<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Mailer.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/UserRepository.php';

requireLogin();

$currentUser = getSessionUser();
$activePage = 'profile';
$pageTitle = 'My Profile';
$pageSubtitle = 'Manage your account and preferences.';
$isStudent = ($currentUser['role'] ?? ROLE_STUDENT) === ROLE_STUDENT;
$profileError = '';
$profileFlash = pullFlash('profile');

try {
    $userRepository = new UserRepository(Database::connection());
    $dbUser = $userRepository->findById((int) ($currentUser['id'] ?? 0));

    if (!$dbUser) {
        throw new RuntimeException('Profile not found in the database.');
    }

    if (isPost()) {
        verify_csrf_or_fail();
        $action = $_POST['action'] ?? 'profile';

        if ($action === 'profile') {
            $submittedName = trim($_POST['name'] ?? '');
            $submittedEmail = trim($_POST['email'] ?? '');
            $submittedPhone = trim($_POST['phone'] ?? '');
            $submittedLocation = trim($_POST['location'] ?? '');
            $submittedBio = trim($_POST['bio'] ?? '');
            $submittedStudentId = trim($_POST['student_id'] ?? '');
            $submittedFaculty = trim($_POST['faculty'] ?? '');

            $sessionUser = new User(
                (int) $dbUser['id'],
                $submittedName,
                $submittedEmail,
                $dbUser['password'],
                $dbUser['role'],
                $submittedPhone
            );

            $userRepository->updateProfile((int) $dbUser['id'], [
                'name' => $sessionUser->getName(),
                'email' => $sessionUser->getEmail(),
                'phone' => $sessionUser->getPhone(),
                'location' => $submittedLocation,
                'bio' => $submittedBio,
                'student_id' => $submittedStudentId,
                'faculty' => $submittedFaculty,
            ]);

            $_SESSION['user']['name'] = $sessionUser->getName();
            $_SESSION['user']['email'] = $sessionUser->getEmail();
            $_SESSION['user']['phone'] = $sessionUser->getPhone();

            flash('profile', 'Profile updated successfully.');
            redirect('pages/profile.php');
        }

        if ($action === 'password') {
            $currentPassword = trim($_POST['current_password'] ?? '');
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if (!$userRepository->authenticate($dbUser['email'], $currentPassword)) {
                throw new InvalidArgumentException('Current password is incorrect.');
            }

            if (!User::validatePassword($newPassword)) {
                throw new InvalidArgumentException('New password must be at least 6 characters.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new InvalidArgumentException('New passwords do not match.');
            }

            $userRepository->changePassword((int) $dbUser['id'], $newPassword);
            flash('profile', 'Password changed successfully.');
            redirect('pages/profile.php');
        }

        if ($action === 'contact') {
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if ($subject === '' || strlen($message) < 10) {
                throw new InvalidArgumentException('Please enter a subject and a longer message.');
            }

            $mailResult = Mailer::send(
                'library@uni-pr.edu',
                '[Library App] ' . $subject,
                "Sender: {$dbUser['name']} <{$dbUser['email']}>" . PHP_EOL . PHP_EOL . $message,
                $dbUser['email']
            );

            flash('profile', $mailResult['sent'] ? 'Email sent successfully.' : 'Mail server unavailable, but your message was saved to the local log.');
            redirect('pages/profile.php');
        }
    }

    $profile = [
        'name' => $dbUser['name'],
        'email' => $dbUser['email'],
        'phone' => $dbUser['phone'] ?: ($isStudent ? '+383 44 123 456' : '+383 38 500 600'),
        'role' => roleLabel($dbUser['role']),
        'location' => $dbUser['location'] ?: 'Prishtina, Kosovo',
        'bio' => $dbUser['bio'] ?: ($isStudent
            ? 'Computer Science student at the University of Prishtina. Passionate about algorithms and software engineering.'
            : 'Head librarian with experience in cataloguing and collection management.'),
        'studentId' => $dbUser['student_id'] ?: ($isStudent ? 'UP-2026-0042' : ''),
        'faculty' => $dbUser['faculty'] ?: ($isStudent ? 'Faculty of Electrical and Computer Engineering' : ''),
    ];
} catch (Throwable $exception) {
    $profileError = $exception->getMessage();
    $profile = [
        'name' => $currentUser['name'] ?? 'User',
        'email' => $currentUser['email'] ?? '',
        'phone' => $currentUser['phone'] ?? '',
        'role' => roleLabel($currentUser['role'] ?? ROLE_STUDENT),
        'location' => 'Prishtina, Kosovo',
        'bio' => '',
        'studentId' => '',
        'faculty' => '',
    ];
}

$profileStats = $isStudent
    ? [
        ['label' => 'Active Loans', 'value' => 'Live'],
        ['label' => 'Notifications', 'value' => 'Enabled'],
        ['label' => 'Profile Sync', 'value' => 'DB'],
    ]
    : [
        ['label' => 'Books Managed', 'value' => 'Live'],
        ['label' => 'Mail Logging', 'value' => 'Ready'],
        ['label' => 'Profile Sync', 'value' => 'DB'],
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
    <?php if ($profileFlash): ?>
        <div class="page-alert alert-<?= h($profileFlash['type']) ?>" style="position: fixed; top: 90px; right: 1.75rem; z-index: 999;">
            <?= h($profileFlash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($profileError !== ''): ?>
        <div class="page-alert alert-error" style="position: fixed; top: 90px; right: 1.75rem; z-index: 999;">
            <?= h($profileError) ?>
        </div>
    <?php endif; ?>

    <div class="profile-layout">
        <div class="profile-sidebar-card">
            <div class="profile-avatar-wrap">
                <div class="profile-avatar-xl"><?= h(strtoupper(substr($profile['name'], 0, 1))) ?></div>
                <button class="profile-avatar-edit" type="button">
                    Edit
                </button>
            </div>
            <h3 class="profile-name" data-profile-preview="name"><?= h($profile['name']) ?></h3>
            <span class="profile-role-tag"><?= h($profile['role']) ?></span>
            <p class="profile-bio" data-profile-preview="bio"><?= h($profile['bio']) ?></p>

            <div class="profile-stats">
                <?php foreach ($profileStats as $stat): ?>
                    <div class="ps-stat">
                        <span class="ps-val"><?= h($stat['value']) ?></span>
                        <span class="ps-label"><?= h($stat['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="profile-meta-list">
                <div class="profile-meta-item"><?= h($profile['email']) ?></div>
                <div class="profile-meta-item" data-profile-preview="phone"><?= h($profile['phone']) ?></div>
                <div class="profile-meta-item" data-profile-preview="location"><?= h($profile['location']) ?></div>
                <?php if ($isStudent && $profile['studentId'] !== ''): ?>
                    <div class="profile-meta-item" data-profile-preview="studentId"><?= h($profile['studentId']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-form-area">
            <form method="post" action="<?= BASE_URL ?>/pages/profile.php" data-profile-form>
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                <input type="hidden" name="action" value="profile" />
                <div class="profile-section">
                    <h3>Personal Information</h3>
                    <div class="form-row">
                        <div class="form-field">
                            <label>Full Name</label>
                            <input name="name" value="<?= h($profile['name']) ?>" data-profile-field="name" required />
                        </div>
                        <div class="form-field">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?= h($profile['email']) ?>" required />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label>Phone Number</label>
                            <input name="phone" value="<?= h($profile['phone']) ?>" data-profile-field="phone" required />
                        </div>
                        <div class="form-field">
                            <label>Location</label>
                            <input name="location" value="<?= h($profile['location']) ?>" data-profile-field="location" />
                        </div>
                    </div>
                    <?php if ($isStudent): ?>
                        <div class="form-row">
                            <div class="form-field">
                                <label>Student ID</label>
                                <input name="student_id" value="<?= h($profile['studentId']) ?>" data-profile-field="studentId" />
                            </div>
                            <div class="form-field">
                                <label>Faculty</label>
                                <input name="faculty" value="<?= h($profile['faculty']) ?>" />
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="form-field">
                        <label>Bio</label>
                        <textarea name="bio" rows="3" data-profile-field="bio"><?= h($profile['bio']) ?></textarea>
                    </div>
                    <div class="profile-actions">
                        <button class="btn-primary" type="submit">Save Profile</button>
                    </div>
                </div>
            </form>

            <form method="post" action="<?= BASE_URL ?>/pages/profile.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                <input type="hidden" name="action" value="password" />
                <div class="profile-section">
                    <h3>Security</h3>
                    <div class="form-row">
                        <div class="form-field">
                            <label>Current Password</label>
                            <input type="password" name="current_password" placeholder="Current password" />
                        </div>
                        <div class="form-field">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="At least 6 characters" />
                        </div>
                    </div>
                    <div class="form-field" style="max-width: calc(50% - 0.4375rem);">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Repeat new password" />
                    </div>
                    <div class="profile-actions">
                        <button class="btn-primary" type="submit">Update Password</button>
                    </div>
                </div>
            </form>

            <div class="profile-section">
                <h3>Preferences</h3>
                <div class="prefs-list">
                    <?php foreach ($preferences as $preference): ?>
                        <div class="pref-item">
                            <div>
                                <p><?= h($preference['label']) ?></p>
                                <span><?= h($preference['desc']) ?></span>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" <?= $preference['checked'] ? 'checked' : '' ?> />
                                <span class="toggle-track"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="post" action="<?= BASE_URL ?>/pages/profile.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                <input type="hidden" name="action" value="contact" />
                <div class="profile-section">
                    <h3>Contact Librarian</h3>
                    <div class="form-field">
                        <label>Subject</label>
                        <input type="text" name="subject" placeholder="Question about a loan or book" />
                    </div>
                    <div class="form-field">
                        <label>Message</label>
                        <textarea name="message" rows="4" placeholder="Write your message here..."></textarea>
                    </div>
                    <div class="profile-actions">
                        <button class="btn-primary" type="submit">Send Email</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
