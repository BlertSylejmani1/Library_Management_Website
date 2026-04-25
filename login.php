<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/User.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$pageTitle = 'Sign In';
$useAppShell = false;
$bodyClass = 'login-body';
$error = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
        $emailValue = $email;
    } elseif (!User::validateEmail($email)) {
        $error = 'Please enter a valid email address.';
        $emailValue = $email;
    } else {
        $user = User::authenticate($email, $password);
        if ($user) {
            $_SESSION['user'] = $user->toArray();
            header('Location: ' . BASE_URL . '/pages/dashboard.php');
            exit;
        }

        $error = 'Invalid credentials.';
        $emailValue = $email;
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="login-root">
    <div class="login-bg-art">
        <div class="login-orb orb-1"></div>
        <div class="login-orb orb-2"></div>
        <div class="login-orb orb-3"></div>
        <div class="login-grid-overlay"></div>
    </div>

    <div class="login-left">
        <div class="login-brand-panel">
            <div class="login-brand-logo">
                <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <rect width="48" height="48" rx="14" fill="url(#grad1)" />
                    <rect x="12" y="14" width="6" height="20" rx="1" fill="white" opacity="0.9" />
                    <rect x="20" y="14" width="6" height="20" rx="1" fill="white" opacity="0.7" />
                    <rect x="28" y="14" width="8" height="20" rx="1" fill="white" opacity="0.5" />
                    <defs>
                        <linearGradient id="grad1" x1="0" y1="0" x2="48" y2="48">
                            <stop stop-color="#3B7BE6" />
                            <stop offset="1" stop-color="#1A4FA0" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h1 class="login-brand-name"><?= APP_NAME ?></h1>
            <!-- <p class="login-brand-sub">Library Management System</p> -->
            <div class="login-feature-list">
                <?php foreach ([
                    ['icon' => '📚', 'label' => 'CS & Software Eng. Books'],
                    ['icon' => '👥', 'label' => 'Multi-role Access Control'],
                    ['icon' => '📊', 'label' => 'Real-time Loan Analytics'],
                    ['icon' => '🔍', 'label' => 'Advanced Search & Filter'],
                ] as $index => $feature): ?>
                    <div class="login-feature-item" style="animation-delay: <?= number_format($index * 0.1, 1) ?>s">
                        <span class="feature-icon"><?= $feature['icon'] ?></span>
                        <span><?= htmlspecialchars($feature['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="login-testimonial">
                <p>"A reader lives a thousand lives before he dies ... The man who never reads lives only one"</p>
                <span>- George R.R Martin -</span>
            </div>
        </div>
    </div>

    <div class="login-right">
        <div class="login-form-card">
            <div class="login-form-header">
                <h2>Welcome back</h2>
                <p>Sign in to your account - University of Prishtina</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="login-alert-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/login.php" novalidate>
                <div class="login-field">
                    <label for="email">Email Address</label>
                    <div class="login-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" />
                        </svg>
                        <input id="email" type="email" name="email" value="<?= htmlspecialchars($emailValue) ?>" placeholder="admin@library.com" autocomplete="email" />
                    </div>
                </div>

                <div class="login-field">
                    <label for="password">
                        Password
                        <button type="button" class="forgot-link" data-modal-open="forgotPasswordModal">Forgot password?</button>
                    </label>
                    <div class="login-input-wrap">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" data-password-input />
                        <button type="button" class="toggle-pw" data-password-toggle aria-label="Toggle password">
                            <svg class="pw-icon-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg class="pw-icon-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="login-remember">
                    <label class="checkbox-label">
                        <input type="checkbox" />
                        <span class="checkbox-custom"></span>
                        Keep me signed in
                    </label>
                </div>

                <button type="submit" class="login-btn">
                    <span>Sign In</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12" /><polyline points="12 5 19 12 12 19" />
                    </svg>
                </button>
            </form>

            <div class="login-demo-hint">
                <div style="margin-bottom: 0.4rem;">
                    <span>🔑 Admin:</span> <code>admin@library.com</code> / <code>admin123</code>
                </div>
                <div>
                    <span>🎓 Student:</span> <code>student@library.com</code> / <code>student123</code>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay modal-hidden" id="forgotPasswordModal">
    <div class="modal-box modal-box-sm">
        <div class="modal-header">
            <h2>Reset Password</h2>
            <button class="modal-close" type="button" data-modal-close="forgotPasswordModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form data-forgot-form>
                <p class="modal-copy">Enter your email and we'll send a reset link.</p>
                <div class="login-alert-error modal-hidden" data-forgot-error></div>
                <div class="form-field">
                    <label>Email Address</label>
                    <input type="email" placeholder="your@email.com" data-forgot-email />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-modal-close="forgotPasswordModal">Cancel</button>
                    <button type="submit" class="btn-primary">Send Reset Link</button>
                </div>
            </form>
            <div class="forgot-success modal-hidden" data-forgot-success>
                <div class="forgot-success-icon">📧</div>
                <h3>Check your inbox</h3>
                <p>If the address is registered, a reset link has been sent.</p>
                <div class="modal-footer" style="padding-top: 1rem;">
                    <button class="btn-primary" type="button" data-modal-close="forgotPasswordModal">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
