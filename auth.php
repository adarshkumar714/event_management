<?php
require_once __DIR__ . '/config.php';

$role = $_GET['role'] ?? 'user';
$role = $role === 'admin' ? 'admin' : 'user';
$error = '';
$success = get_flash('flash_success');
$flashError = get_flash('flash_error');
if ($flashError) {
    $error = $flashError;
}

if (is_logged_in()) {
    redirect(current_role() === 'admin' ? '/event_project/admin/admin.php' : '/event_project/user.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $conn = db();

        if ($action === 'register') {
            $name = trim($_POST['name'] ?? '');

            if ($name === '' || $email === '' || $password === '') {
                throw new RuntimeException('Please fill in every required field.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid email address.');
            }

            $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->bind_param('s', $email);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if ($exists) {
                throw new RuntimeException('An account with that email already exists.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $name, $email, $hash, $role);
            $stmt->execute();
            $stmt->close();

            set_flash('flash_success', 'Registration complete. You can sign in now.');
            redirect('/event_project/auth.php?role=' . $role);
        }

        if ($action === 'login') {
            if ($email === '' || $password === '') {
                throw new RuntimeException('Email and password are required.');
            }

            $stmt = $conn->prepare('SELECT id, name, password, role FROM users WHERE email = ? AND role = ? LIMIT 1');
            $stmt->bind_param('ss', $email, $role);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user || !verify_user_password($password, $user['password'])) {
                throw new RuntimeException('Invalid credentials for this panel.');
            }

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            redirect($user['role'] === 'admin' ? '/event_project/admin/admin.php' : '/event_project/user.php');
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<?php
app_head(ucfirst($role) . ' Access | EventHub');
app_shell_start($role === 'admin' ? 'admin' : 'user');
?>
<section class="surface-card hero-banner">
    <div class="auth-layout">
        <div class="auth-visual">
            <span class="eyebrow text-bg-light border-0"><?= e($role) ?> access</span>
            <h1 class="mt-3"><?= $role === 'admin' ? 'Manage your events with a focused admin workspace.' : 'Book events through a simple and polished attendee flow.' ?></h1>
            <p class="mt-3 text-white-50"><?= $role === 'admin' ? 'Sign in to publish events, track attendees, and review booking activity from one dashboard.' : 'Create your account or sign in to discover events, reserve seats, and keep your booking history organized.' ?></p>

            <div class="mini-graphic mt-4">
                <svg viewBox="0 0 360 260" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="38" y="38" width="284" height="182" rx="28" fill="rgba(255,255,255,0.14)"/>
                    <rect x="62" y="66" width="236" height="24" rx="12" fill="#F9B74A"/>
                    <rect x="62" y="106" width="84" height="84" rx="18" fill="#F4EFE6"/>
                    <rect x="160" y="108" width="108" height="16" rx="8" fill="rgba(255,255,255,0.82)"/>
                    <rect x="160" y="136" width="84" height="16" rx="8" fill="rgba(255,255,255,0.58)"/>
                    <rect x="160" y="164" width="92" height="26" rx="13" fill="#0F766E"/>
                </svg>
            </div>
        </div>

        <div class="surface-card strong p-4 p-lg-5">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="section-heading">
                <div>
                    <span class="eyebrow">Secure access</span>
                    <h2 class="mt-3 h3 mb-0"><?= $role === 'admin' ? 'Admin authentication' : 'User authentication' ?></h2>
                </div>
            </div>

            <div class="auth-grid mt-4">
                <div class="info-card">
                    <h3 class="h4 mb-3">Sign In</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="<?= $role === 'admin' ? 'admin@eventhub.local' : 'you@example.com' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control password-field" placeholder="Enter your password" required>
                                <button type="button" class="btn btn-outline-secondary password-toggle">Show</button>
                            </div>
                        </div>
                        <button class="btn btn-accent w-100">Sign In</button>
                    </form>
                </div>

                <div class="info-card">
                    <h3 class="h4 mb-3">Create Account</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="register">
                        <div class="mb-3">
                            <label class="form-label">Full name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control password-field" required>
                                <button type="button" class="btn btn-outline-secondary password-toggle">Show</button>
                            </div>
                        </div>
                        <button class="btn btn-outline-navy w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var field = button.parentElement.querySelector('.password-field');
            if (!field) {
                return;
            }

            var isHidden = field.type === 'password';
            field.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Hide' : 'Show';
        });
    });

    document.querySelectorAll('form').forEach(function (form) {
        var fields = Array.from(
            form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), textarea, select, button[type="submit"]')
        ).filter(function (field) {
            return !field.disabled;
        });

        fields.forEach(function (field, index) {
            field.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' || field.tagName === 'TEXTAREA') {
                    return;
                }

                var nextField = fields[index + 1];
                if (!nextField) {
                    return;
                }

                event.preventDefault();
                nextField.focus();

                if (typeof nextField.select === 'function' && nextField.tagName === 'INPUT') {
                    nextField.select();
                }
            });
        });
    });
});
</script>
<?php
app_shell_end();
?>
