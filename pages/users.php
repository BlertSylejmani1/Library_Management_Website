<?php
require_once __DIR__ . '/../config/config.php';

requireAdmin();

$activePage = 'users';
$pageTitle = 'Users';
$pageSubtitle = 'Manage registered library members.';
$users = array_map(function ($user, $index) {
    $user['location'] = $user['role'] === ROLE_ADMIN ? 'Prishtina, Kosovo' : 'Prishtina, Kosovo';
    $user['phone'] = $user['phone'] ?: '—';
    $user['avatarClass'] = 'avatar-color-' . ($index % 5);
    return $user;
}, $GLOBALS['users'], array_keys($GLOBALS['users']));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="books-page users-page">
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
                    <button class="genre-tab <?= $role === 'all' ? 'active' : '' ?>" type="button" data-user-role="<?= htmlspecialchars($role) ?>"><?= $label ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="page-header-right">
            <button class="add-btn" type="button" data-modal-open="addMemberModal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add Member
            </button>
        </div>
    </div>
     <div class="users-grid" data-filter-scope-id="users-grid">
        <?php foreach ($users as $user): ?>
            <div class="user-card" data-filter-item data-name="<?= htmlspecialchars(strtolower($user['name'])) ?>" data-email="<?= htmlspecialchars(strtolower($user['email'])) ?>" data-role="<?= htmlspecialchars($user['role']) ?>">
                <div class="user-card-top">
                    <div class="user-avatar-lg <?= htmlspecialchars($user['avatarClass']) ?>">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div class="user-card-info">
                        <h4><?= htmlspecialchars($user['name']) ?></h4>
                        <span><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                </div>
                <div class="user-card-stats">
                    <div class="user-stat">
                        <span class="user-stat-val"><?= htmlspecialchars($user['phone']) ?></span>
                        <span class="user-stat-label">Phone</span>
                    </div>
                    <div class="user-stat-divider"></div>
                    <div class="user-stat">
                        <span class="user-stat-val"><?= htmlspecialchars(roleLabel($user['role'])) ?></span>
                        <span class="user-stat-label">Role</span>
                    </div>
                    <div class="user-stat-divider"></div>
                    <div class="user-stat">
                        <span class="user-stat-val"><?= htmlspecialchars($user['location']) ?></span>
                        <span class="user-stat-label">Location</span>
                    </div>
                </div>
                <div class="user-card-footer">
                    <span><?= htmlspecialchars($user['email']) ?></span>
                    <div class="action-btns">
                        <button class="act-btn act-edit" type="button" data-toast-message="Profile editor is ready for server-side integration.">
                            Edit
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <p class="empty-row modal-hidden" data-filter-empty>No users found.</p>
    </div>
</div>

<div class="modal-overlay modal-hidden" id="addMemberModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Register New Member</h2>
            <button class="modal-close" type="button" data-modal-close="addMemberModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form data-static-form data-success-message="Member registered successfully." data-close-modal="addMemberModal">
                <div class="form-row">
                    <div class="form-field">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" placeholder="e.g. Arta Berisha" required />
                    </div>
                    <div class="form-field">
                        <label>Email <span class="req">*</span></label>
                        <input type="email" placeholder="student@uni-pr.edu" required />
                    </div>
                </div>
                <div class="form-field">
                    <label>Role</label>
                    <select>
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-footer" style="padding-top: 1.25rem;">
                    <button class="btn-secondary" type="button" data-modal-close="addMemberModal">Cancel</button>
                    <button class="btn-primary" type="submit">Register Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

