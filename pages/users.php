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