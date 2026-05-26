<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/UserRepository.php';

requireAdmin();

$activePage = 'users';
$pageTitle = 'Users';
$pageSubtitle = 'Manage registered library members.';
$flash = pullFlash('users');
$pageError = '';

try {
    $userRepository = new UserRepository(Database::connection());

    if (isPost()) {
        try {
            verify_csrf_or_fail();

            $action = $_POST['action'] ?? 'create';
            $userId = (int) ($_POST['user_id'] ?? 0);

            if ($action === 'delete') {
                if ($userId < 1) {
                    throw new InvalidArgumentException('User ID is required.');
                }

                if ($userId === (int) ($_SESSION['user']['id'] ?? 0)) {
                    throw new RuntimeException('You cannot delete your own account while logged in.');
                }

                $userRepository->delete($userId);
                flash('users', 'User deleted successfully.');
                redirect('pages/users.php');
            }

            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = trim($_POST['role'] ?? ROLE_STUDENT);
            $phone = trim($_POST['phone'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $studentId = trim($_POST['student_id'] ?? '');
            $faculty = trim($_POST['faculty'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $password = trim($_POST['password'] ?? '');

            $userModel = $role === ROLE_ADMIN
                ? new Admin(max(1, $userId), $name, $email, $password === '' ? 'temporary' : $password, $phone === '' ? '+383 38 000 000' : $phone, $department === '' ? 'Library Administration' : $department)
                : new User(max(1, $userId), $name, $email, $password === '' ? 'temporary' : $password, $role, $phone === '' ? '+383 44 000 000' : $phone);

            if ($action === 'create' && !User::validatePassword($password)) {
                throw new InvalidArgumentException('Password must be at least 6 characters for new users.');
            }

            $payload = [
                'name' => $userModel->getName(),
                'email' => $userModel->getEmail(),
                'role' => $role,
                'phone' => $userModel->getPhone(),
                'location' => $location,
                'bio' => $bio,
                'student_id' => $studentId,
                'faculty' => $faculty,
                'department' => $department,
                'password' => $password,
            ];

            if ($action === 'update' && $userId > 0) {
                $userRepository->update($userId, $payload);
                flash('users', 'User updated successfully.');
            } else {
                $userRepository->create($payload);
                flash('users', 'Member registered successfully.');
            }
        } catch (Throwable $exception) {
            flash('users', $exception->getMessage(), 'error');
        }

        redirect('pages/users.php');
    }

    $users = $userRepository->all();
} catch (Throwable $exception) {
    $users = [];
    $pageError = $exception->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="books-page users-page">
    <?php if ($flash): ?>
        <div class="page-alert alert-<?= h($flash['type']) ?>" style="position: fixed; top: 90px; right: 1.75rem; z-index: 999;">
            <?= h($flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($pageError !== ''): ?>
        <div class="page-alert alert-error" style="position: fixed; top: 90px; right: 1.75rem; z-index: 999;">
            <?= h($pageError) ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <div class="page-header-left">
            <div class="search-wrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" placeholder="Search members..." value="" class="page-search" data-filter-input data-filter-scope="users-grid" />
            </div>
            <div class="genre-tabs">
                <?php foreach (['all' => 'All', 'admin' => 'Admin', 'student' => 'Student'] as $role => $label): ?>
                    <button class="genre-tab <?= $role === 'all' ? 'active' : '' ?>" type="button" data-user-role="<?= h($role) ?>"><?= h($label) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="page-header-right">
            <button class="add-btn" type="button" data-modal-open="memberEditorModal" data-member-mode="add">
                Add Member
            </button>
        </div>
    </div>

    <div class="users-grid" data-filter-scope-id="users-grid">
        <?php foreach ($users as $index => $user): ?>
            <?php $avatarClass = 'avatar-color-' . ($index % 5); ?>
            <div class="user-card" data-filter-item data-name="<?= h(strtolower($user['name'])) ?>" data-email="<?= h(strtolower($user['email'])) ?>" data-role="<?= h($user['role']) ?>" data-user='<?= h(json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
                <div class="user-card-top">
                    <div class="user-avatar-lg <?= h($avatarClass) ?>">
                        <?= h(strtoupper(substr($user['name'], 0, 1))) ?>
                    </div>
                    <div class="user-card-info">
                        <h4><?= h($user['name']) ?></h4>
                        <span><?= h($user['email']) ?></span>
                    </div>
                </div>
                <div class="user-card-stats">
                    <div class="user-stat">
                        <span class="user-stat-val"><?= h($user['phone'] ?: '—') ?></span>
                        <span class="user-stat-label">Phone</span>
                    </div>
                    <div class="user-stat-divider"></div>
                    <div class="user-stat">
                        <span class="user-stat-val"><?= h(roleLabel($user['role'])) ?></span>
                        <span class="user-stat-label">Role</span>
                    </div>
                    <div class="user-stat-divider"></div>
                    <div class="user-stat">
                        <span class="user-stat-val"><?= h($user['location'] ?: 'Prishtina, Kosovo') ?></span>
                        <span class="user-stat-label">Location</span>
                    </div>
                </div>
                <div class="user-card-footer">
                    <span><?= h($user['email']) ?></span>
                    <div class="action-btns">
                        <button class="act-btn act-edit" type="button" data-edit-member>Edit</button>
                        <?php if ((int) $user['id'] !== (int) ($_SESSION['user']['id'] ?? 0)): ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>" />
                                <button class="act-btn act-delete" type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <p class="empty-row modal-hidden" data-filter-empty>No users found.</p>
    </div>
</div>

<div class="modal-overlay modal-hidden" id="memberEditorModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2 data-member-modal-title>Register New Member</h2>
            <button class="modal-close" type="button" data-modal-close="memberEditorModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="post" data-member-form>
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                <input type="hidden" name="action" value="create" data-member-form-action />
                <input type="hidden" name="user_id" value="" data-member-id />
                <div class="form-row">
                    <div class="form-field">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" name="name" placeholder="e.g. Arta Berisha" required />
                    </div>
                    <div class="form-field">
                        <label>Email <span class="req">*</span></label>
                        <input type="email" name="email" placeholder="student@uni-pr.edu" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Role</label>
                        <select name="role">
                            <option value="student">Student</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="+383 44 123 456" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="Prishtina, Kosovo" />
                    </div>
                    <div class="form-field">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Only required for create or reset" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Student ID</label>
                        <input type="text" name="student_id" placeholder="UP-2026-0001" />
                    </div>
                    <div class="form-field">
                        <label>Faculty / Department</label>
                        <input type="text" name="faculty" placeholder="Faculty of Engineering" />
                    </div>
                </div>
                <div class="form-field">
                    <label>Admin Department</label>
                    <input type="text" name="department" placeholder="Library Administration" />
                </div>
                <div class="form-field">
                    <label>Bio</label>
                    <textarea name="bio" rows="3" placeholder="Short profile summary..."></textarea>
                </div>
                <div class="modal-footer" style="padding-top: 1.25rem;">
                    <button class="btn-secondary" type="button" data-modal-close="memberEditorModal">Cancel</button>
                    <button class="btn-primary" type="submit">Save Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
