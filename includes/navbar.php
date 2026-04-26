<?php
require_once __DIR__ . '/../config/config.php';

$currentUser = getSessionUser();
$activePage = $activePage ?? 'dashboard';
$pageSubtitle = $pageSubtitle ?? '';
$sidebarCollapsed = ($_COOKIE['ath_sidebar'] ?? 'expanded') === 'collapsed';

$pageTitles = [
    'dashboard' => ['title' => 'Dashboard', 'sub' => "Welcome back, let's see what's happening today."],
    'books' => ['title' => 'Books', 'sub' => 'Browse and manage the library catalogue.'],
    'loans' => ['title' => 'Loans', 'sub' => 'Track all active and past book loans.'],
    'users' => ['title' => 'Users', 'sub' => 'Manage registered library members.'],
    'profile' => ['title' => 'My Profile', 'sub' => 'Manage your account and preferences.'],
];

$currentPageMeta = $pageTitles[$activePage] ?? $pageTitles['dashboard'];

$adminNav = [
    'Main' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => BASE_URL . '/pages/dashboard.php', 'icon' => 'dashboard'],
        ['key' => 'books', 'label' => 'Books', 'href' => BASE_URL . '/pages/books.php', 'icon' => 'books'],
        ['key' => 'loans', 'label' => 'Loans', 'href' => BASE_URL . '/pages/loans.php', 'icon' => 'loans', 'badge' => 3],
    ],
    'Management' => [
        ['key' => 'users', 'label' => 'Users', 'href' => BASE_URL . '/pages/users.php', 'icon' => 'users'],
        ['key' => 'profile', 'label' => 'Profile', 'href' => BASE_URL . '/pages/profile.php', 'icon' => 'profile'],
    ],
];

$studentNav = [
    'My Library' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => BASE_URL . '/pages/dashboard.php', 'icon' => 'dashboard'],
        ['key' => 'profile', 'label' => 'Profile', 'href' => BASE_URL . '/pages/profile.php', 'icon' => 'profile'],
    ],
];

$navigation = ($currentUser['role'] ?? ROLE_STUDENT) === ROLE_STUDENT ? $studentNav : $adminNav;
$notifications = ($currentUser['role'] ?? ROLE_STUDENT) === ROLE_STUDENT
    ? [
        ['icon' => 'warning', 'message' => 'Introduction to Algorithms is due in 2 days', 'time' => '1h ago'],
        ['icon' => 'success', 'message' => 'Your renewal request for Clean Code was approved', 'time' => 'Today'],
        ['icon' => 'mail', 'message' => 'Deep Learning is now available to request', 'time' => 'Yesterday'],
    ]
    : [
        ['icon' => 'warning', 'message' => 'The Great Gatsby is 3 days overdue', 'time' => '2h ago'],
        ['icon' => 'success', 'message' => 'Dune returned by Marcus L.', 'time' => '4h ago'],
        ['icon' => 'mail', 'message' => 'New loan request from Sarah K.', 'time' => 'Yesterday'],
    ];

function renderSidebarIcon(string $name): string
{
    $icons = [
        'dashboard' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'books' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'loans' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>',
        'users' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'profile' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'warning' => '<span aria-hidden="true">⚠️</span>',
        'success' => '<span aria-hidden="true">✅</span>',
        'mail' => '<span aria-hidden="true">📩</span>',
    ];

    return $icons[$name] ?? '';
}
?>
<div class="app-shell">
    <aside class="sidebar <?= $sidebarCollapsed ? 'collapsed' : '' ?>" id="appSidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <svg width="28" height="28" viewBox="0 0 48 48" fill="none">
                    <rect width="48" height="48" rx="12" fill="url(#sgrad)" />
                    <rect x="12" y="14" width="6" height="20" rx="1" fill="white" opacity="0.9" />
                    <rect x="20" y="14" width="6" height="20" rx="1" fill="white" opacity="0.7" />
                    <rect x="28" y="14" width="8" height="20" rx="1" fill="white" opacity="0.5" />
                    <defs>
                        <linearGradient id="sgrad" x1="0" y1="0" x2="48" y2="48">
                            <stop stop-color="#3B7BE6" />
                            <stop offset="1" stop-color="#1A4FA0" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <button class="sidebar-collapse-btn" type="button" data-sidebar-toggle title="Toggle sidebar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6" />
                </svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($navigation as $section => $links): ?>
                <div class="sidebar-group">
                    <span class="sidebar-group-label"><?= htmlspecialchars($section) ?></span>
                    <?php foreach ($links as $link): ?>
                        <a
                            class="sidebar-link <?= $activePage === $link['key'] ? 'active' : '' ?>"
                            href="<?= htmlspecialchars($link['href']) ?>"
                            title="<?= htmlspecialchars($link['label']) ?>"
                        >
                            <span class="sidebar-link-icon"><?= renderSidebarIcon($link['icon']) ?></span>
                            <span class="sidebar-link-label"><?= htmlspecialchars($link['label']) ?></span>
                            <?php if (!empty($link['badge'])): ?>
                                <span class="sidebar-badge"><?= (int) $link['badge'] ?></span>
                                <span class="sidebar-badge-dot"></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?></div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></span>
                    <span class="sidebar-user-role role-<?= ($currentUser['role'] ?? ROLE_ADMIN) === ROLE_STUDENT ? 'student' : 'admin' ?>">
                        <?= htmlspecialchars(roleLabel($currentUser['role'] ?? ROLE_ADMIN)) ?>
                    </span>
                </div>
            </div>
        </div>
    </aside>

    <div class="app-main <?= $sidebarCollapsed ? 'sidebar-collapsed' : '' ?>" id="appMain">
        <header class="topnav">
            <div class="topnav-left">
                <div class="topnav-page-info">
                    <h1 class="topnav-title"><?= htmlspecialchars($currentPageMeta['title']) ?></h1>
                    <p class="topnav-sub"><?= htmlspecialchars($pageSubtitle ?: $currentPageMeta['sub']) ?></p>
                </div>
            </div>

            <div class="topnav-right">
                <div class="topnav-search">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" placeholder="Quick search..." class="search-input" />
                    <kbd>⌘K</kbd>
                </div>

                <button class="topnav-icon-btn" type="button" data-theme-toggle title="Toggle theme">
                    <span class="theme-icon theme-dark">☀️</span>
                    <span class="theme-icon theme-light">🌙</span>
                </button>

                <div class="topnav-dropdown-wrap">
                    <button class="topnav-icon-btn notif-btn" type="button" data-dropdown-toggle="notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span class="notif-dot"></span>
                    </button>
                    <div class="topnav-dropdown notif-dropdown" data-dropdown="notifications">
                        <div class="dropdown-header">
                            <span>Notifications</span>
                            <button class="dropdown-clear" type="button">Mark all read</button>
                        </div>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notif-item">
                                <span class="notif-item-icon"><?= renderSidebarIcon($notification['icon']) ?></span>
                                <div class="notif-item-body">
                                    <p><?= htmlspecialchars($notification['message']) ?></p>
                                    <span><?= htmlspecialchars($notification['time']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="topnav-dropdown-wrap">
                    <button class="topnav-profile-btn" type="button" data-dropdown-toggle="profileMenu">
                        <div class="topnav-avatar"><?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?></div>
                        <div class="topnav-profile-info">
                            <span class="profile-name"><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></span>
                            <span class="profile-role"><?= htmlspecialchars(roleLabel($currentUser['role'] ?? ROLE_ADMIN)) ?></span>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="topnav-dropdown profile-dropdown" data-dropdown="profileMenu">
                        <div class="dropdown-header">
                            <span><?= htmlspecialchars($currentUser['email'] ?? 'admin@library.com') ?></span>
                        </div>
                        <a class="profile-menu-item" href="<?= BASE_URL ?>/pages/profile.php">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            View Profile
                        </a>
                        <a class="profile-menu-item" href="<?= BASE_URL ?>/pages/profile.php">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="profile-menu-item logout-item" href="<?= BASE_URL ?>/logout.php">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <main class="app-content">
