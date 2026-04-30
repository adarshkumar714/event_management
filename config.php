<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'event_db';

function db(): mysqli
{
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
    ensure_schema_updates($conn);

    return $conn;
}

function ensure_schema_updates(mysqli $conn): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $hasAdminId = $conn->query("SHOW COLUMNS FROM events LIKE 'admin_id'")->num_rows > 0;
    if (!$hasAdminId) {
        $conn->query('ALTER TABLE events ADD COLUMN admin_id INT NULL AFTER id');
        $conn->query('ALTER TABLE events ADD INDEX idx_events_admin_id (admin_id)');
        $conn->query('ALTER TABLE events ADD CONSTRAINT fk_events_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL');
    }

    $done = true;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function current_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function require_role(string $role): void
{
    if (!is_logged_in() || current_role() !== $role) {
        $_SESSION['flash_error'] = 'Please sign in to continue.';
        redirect('/event_project/auth.php?role=' . $role);
    }
}

function set_flash(string $key, string $message): void
{
    $_SESSION[$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION[$key])) {
        return null;
    }

    $message = $_SESSION[$key];
    unset($_SESSION[$key]);

    return $message;
}

function event_available_tickets(array $event): int
{
    if (!isset($event['capacity'])) {
        return 9999;
    }

    return max(0, (int) $event['capacity'] - (int) $event['booked_tickets']);
}

function events_date_column(): string
{
    static $column = null;

    if ($column !== null) {
        return $column;
    }

    $query = db()->query("SHOW COLUMNS FROM events LIKE 'event_date'");
    $column = $query->num_rows > 0 ? 'event_date' : 'date';

    return $column;
}

function events_has_capacity(): bool
{
    static $hasCapacity = null;

    if ($hasCapacity !== null) {
        return $hasCapacity;
    }

    $query = db()->query("SHOW COLUMNS FROM events LIKE 'capacity'");
    $hasCapacity = $query->num_rows > 0;

    return $hasCapacity;
}

function bookings_has_created_at(): bool
{
    static $hasCreatedAt = null;

    if ($hasCreatedAt !== null) {
        return $hasCreatedAt;
    }

    $query = db()->query("SHOW COLUMNS FROM bookings LIKE 'created_at'");
    $hasCreatedAt = $query->num_rows > 0;

    return $hasCreatedAt;
}

function events_has_admin_owner(): bool
{
    static $hasAdminOwner = null;

    if ($hasAdminOwner !== null) {
        return $hasAdminOwner;
    }

    $query = db()->query("SHOW COLUMNS FROM events LIKE 'admin_id'");
    $hasAdminOwner = $query->num_rows > 0;

    return $hasAdminOwner;
}

function verify_user_password(string $plainPassword, string $storedPassword): bool
{
    if ($plainPassword === $storedPassword) {
        return true;
    }

    return password_verify($plainPassword, $storedPassword);
}

function app_head(string $title): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            :root {
                --page-bg: #f4efe6;
                --page-bg-deep: #dce8f0;
                --panel: rgba(255, 255, 255, 0.84);
                --panel-strong: #ffffff;
                --stroke: rgba(15, 23, 42, 0.08);
                --stroke-strong: rgba(15, 23, 42, 0.12);
                --ink: #172033;
                --muted: #5f6b7f;
                --accent: #0f766e;
                --accent-dark: #134e4a;
                --accent-soft: #e0f2ef;
                --warm: #c97a12;
                --navy: #10213d;
                --shadow-lg: 0 24px 80px rgba(15, 23, 42, 0.14);
                --shadow-md: 0 18px 45px rgba(15, 23, 42, 0.10);
                --radius-xl: 30px;
                --radius-lg: 24px;
                --radius-md: 18px;
                --sidebar-width: 280px;
            }

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                min-height: 100vh;
                margin: 0;
                color: var(--ink);
                background:
                    radial-gradient(circle at top left, rgba(201, 122, 18, 0.22), transparent 22%),
                    radial-gradient(circle at right 20%, rgba(15, 118, 110, 0.16), transparent 28%),
                    linear-gradient(135deg, var(--page-bg), var(--page-bg-deep));
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            }

            a {
                color: inherit;
            }

            .app-shell {
                min-height: 100vh;
                display: grid;
                grid-template-columns: var(--sidebar-width) minmax(0, 1fr);
            }

            .app-sidebar {
                position: sticky;
                top: 0;
                min-height: 100vh;
                padding: 1.5rem;
                background: rgba(16, 33, 61, 0.95);
                color: #f8fafc;
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .brand-mark {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 52px;
                height: 52px;
                border-radius: 18px;
                background: linear-gradient(160deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.06));
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
            }

            .brand-block {
                display: flex;
                align-items: center;
                gap: 0.95rem;
            }

            .brand-block h1,
            .brand-block p {
                margin: 0;
            }

            .brand-block h1 {
                font-size: 1.15rem;
                font-weight: 700;
            }

            .brand-block p {
                color: rgba(248, 250, 252, 0.74);
                font-size: 0.92rem;
            }

            .sidebar-group-title {
                margin: 0 0 0.8rem;
                font-size: 0.78rem;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: rgba(248, 250, 252, 0.55);
            }

            .quick-nav {
                display: grid;
                gap: 0.7rem;
            }

            .quick-link {
                display: flex;
                align-items: center;
                gap: 0.85rem;
                padding: 0.95rem 1rem;
                border-radius: 18px;
                text-decoration: none;
                color: rgba(248, 250, 252, 0.88);
                background: rgba(255, 255, 255, 0.04);
                border: 1px solid rgba(255, 255, 255, 0.05);
                transition: transform 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
            }

            .quick-link:hover,
            .quick-link:focus-visible {
                transform: translateX(4px);
                background: rgba(255, 255, 255, 0.10);
                border-color: rgba(255, 255, 255, 0.16);
                color: #ffffff;
            }

            .quick-link.active {
                background: linear-gradient(145deg, rgba(15, 118, 110, 0.94), rgba(19, 78, 74, 0.94));
                color: #ffffff;
                border-color: transparent;
                box-shadow: 0 12px 28px rgba(15, 118, 110, 0.30);
            }

            .quick-link svg {
                flex-shrink: 0;
            }

            .sidebar-note {
                margin-top: auto;
                padding: 1rem;
                border-radius: 20px;
                background: rgba(255, 255, 255, 0.06);
                border: 1px solid rgba(255, 255, 255, 0.08);
                color: rgba(248, 250, 252, 0.82);
            }

            .sidebar-note strong {
                display: block;
                margin-bottom: 0.35rem;
                color: #ffffff;
            }

            .app-main {
                padding: 1.5rem;
            }

            .page-stack {
                display: grid;
                gap: 1.5rem;
            }

            .surface-card {
                background: var(--panel);
                border: 1px solid var(--stroke);
                border-radius: var(--radius-xl);
                box-shadow: var(--shadow-lg);
                backdrop-filter: blur(18px);
            }

            .surface-card.strong {
                background: var(--panel-strong);
            }

            .hero-banner {
                position: relative;
                overflow: hidden;
                padding: 2rem;
            }

            .hero-banner::before,
            .hero-banner::after {
                content: "";
                position: absolute;
                border-radius: 999px;
                opacity: 0.9;
            }

            .hero-banner::before {
                width: 240px;
                height: 240px;
                right: -60px;
                top: -80px;
                background: radial-gradient(circle, rgba(15, 118, 110, 0.24), transparent 70%);
            }

            .hero-banner::after {
                width: 200px;
                height: 200px;
                left: -40px;
                bottom: -80px;
                background: radial-gradient(circle, rgba(201, 122, 18, 0.18), transparent 70%);
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.45rem 0.9rem;
                border-radius: 999px;
                background: rgba(15, 118, 110, 0.10);
                color: var(--accent-dark);
                font-size: 0.82rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                font-weight: 700;
            }

            .hero-grid,
            .about-grid,
            .auth-layout,
            .split-layout {
                display: grid;
                gap: 1.5rem;
            }

            .hero-grid {
                grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.9fr);
                align-items: center;
            }

            .hero-copy h1,
            .hero-copy h2 {
                margin-top: 1rem;
                margin-bottom: 0.8rem;
                font-weight: 700;
                line-height: 1.08;
            }

            .hero-copy p {
                color: var(--muted);
                font-size: 1.05rem;
                line-height: 1.7;
                margin-bottom: 0;
            }

            .hero-actions,
            .hero-stats,
            .metric-grid,
            .event-grid,
            .auth-grid,
            .feature-list {
                display: grid;
                gap: 1rem;
            }

            .hero-actions {
                grid-template-columns: repeat(auto-fit, minmax(180px, max-content));
                margin-top: 1.5rem;
            }

            .hero-stats {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                margin-top: 1.5rem;
            }

            .stat-chip,
            .metric-card,
            .feature-card,
            .event-card,
            .info-card,
            .booking-highlight {
                border-radius: var(--radius-lg);
                background: rgba(255, 255, 255, 0.74);
                border: 1px solid var(--stroke);
                box-shadow: var(--shadow-md);
            }

            .stat-chip,
            .metric-card,
            .feature-card,
            .info-card,
            .booking-highlight {
                padding: 1.25rem;
            }

            .stat-chip strong,
            .metric-card strong {
                display: block;
                font-size: 1.6rem;
                line-height: 1.1;
            }

            .stat-chip span,
            .metric-card span,
            .feature-card p,
            .info-card p {
                color: var(--muted);
            }

            .metric-grid {
                grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            }

            .section-heading {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                gap: 1rem;
                margin-bottom: 1rem;
            }

            .section-heading h2,
            .section-heading h3,
            .section-heading p {
                margin: 0;
            }

            .event-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }

            .event-card {
                padding: 1.4rem;
                transition: transform 0.18s ease, box-shadow 0.18s ease;
                display: flex;
                flex-direction: column;
                gap: 1rem;
                height: 100%;
            }

            .event-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 24px 45px rgba(15, 23, 42, 0.12);
            }

            .event-card-link {
                text-decoration: none;
                color: inherit;
                display: block;
                height: 100%;
            }

            .event-topline,
            .meta-row,
            .mini-meta {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.75rem;
            }

            .event-topline h3,
            .event-card p,
            .mini-meta p {
                margin: 0;
            }

            .event-card p {
                color: var(--muted);
                line-height: 1.65;
            }

            .meta-stack {
                display: grid;
                gap: 0.75rem;
            }

            .meta-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.45rem 0.8rem;
                border-radius: 999px;
                background: var(--accent-soft);
                color: var(--accent-dark);
                font-size: 0.9rem;
                font-weight: 600;
            }

            .price-tag {
                font-size: 1.35rem;
                font-weight: 700;
                color: var(--navy);
            }

            .table-shell {
                border-radius: var(--radius-lg);
                overflow: hidden;
                border: 1px solid var(--stroke);
                background: rgba(255, 255, 255, 0.72);
            }

            .table-modern {
                margin: 0;
            }

            .table-modern thead th {
                background: rgba(16, 33, 61, 0.95);
                color: #ffffff;
                border-bottom: 0;
                font-weight: 600;
                white-space: nowrap;
            }

            .table-modern tbody td {
                color: var(--ink);
                vertical-align: middle;
            }

            .table-modern tbody tr:nth-child(even) td {
                background: rgba(244, 239, 230, 0.48);
            }

            .empty-state {
                padding: 1.2rem 1.25rem;
                border-radius: var(--radius-md);
                background: rgba(15, 118, 110, 0.08);
                border: 1px dashed rgba(15, 118, 110, 0.30);
                color: var(--accent-dark);
            }

            .about-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }

            .auth-layout {
                grid-template-columns: minmax(280px, 0.95fr) minmax(0, 1.2fr);
            }

            .auth-visual,
            .graphic-panel {
                padding: 2rem;
                border-radius: var(--radius-xl);
                background: linear-gradient(160deg, #10213d, #0f766e);
                color: #ffffff;
                position: relative;
                overflow: hidden;
            }

            .auth-visual::after,
            .graphic-panel::after {
                content: "";
                position: absolute;
                inset: auto -40px -50px auto;
                width: 220px;
                height: 220px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(255, 255, 255, 0.20), transparent 70%);
            }

            .auth-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }

            .mini-graphic {
                display: grid;
                place-items: center;
                min-height: 220px;
            }

            .mini-graphic svg {
                width: min(100%, 320px);
                height: auto;
                filter: drop-shadow(0 20px 30px rgba(15, 23, 42, 0.18));
            }

            .list-clean {
                list-style: none;
                padding: 0;
                margin: 0;
                display: grid;
                gap: 0.9rem;
            }

            .list-clean li {
                display: flex;
                gap: 0.75rem;
                align-items: flex-start;
            }

            .list-clean li span:first-child {
                display: inline-flex;
                width: 1.8rem;
                height: 1.8rem;
                border-radius: 999px;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                background: rgba(255, 255, 255, 0.16);
            }

            .sticky-card {
                position: sticky;
                top: 1.5rem;
            }

            .btn-accent {
                background: var(--accent);
                border-color: var(--accent);
                color: #ffffff;
            }

            .btn-accent:hover,
            .btn-accent:focus-visible {
                background: var(--accent-dark);
                border-color: var(--accent-dark);
                color: #ffffff;
            }

            .btn-outline-navy {
                border-color: rgba(16, 33, 61, 0.24);
                color: var(--navy);
            }

            .btn-outline-navy:hover,
            .btn-outline-navy:focus-visible {
                background: var(--navy);
                border-color: var(--navy);
                color: #ffffff;
            }

            .form-control,
            .form-select {
                border-radius: 14px;
                padding: 0.78rem 0.95rem;
                border-color: rgba(15, 23, 42, 0.12);
            }

            .form-control:focus,
            .form-select:focus {
                border-color: rgba(15, 118, 110, 0.55);
                box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.14);
            }

            @media (max-width: 991.98px) {
                .app-shell {
                    grid-template-columns: 1fr;
                }

                .app-sidebar {
                    position: static;
                    min-height: auto;
                }

                .hero-grid,
                .auth-layout,
                .split-layout {
                    grid-template-columns: 1fr;
                }

                .sticky-card {
                    position: static;
                }
            }

            @media (max-width: 767.98px) {
                .app-main,
                .app-sidebar {
                    padding: 1rem;
                }

                .hero-banner,
                .auth-visual,
                .graphic-panel {
                    padding: 1.35rem;
                }

                .section-heading {
                    align-items: flex-start;
                    flex-direction: column;
                }
            }
        </style>
    </head>
    <body>
    <?php
}

function app_nav_link(string $href, string $label, string $active, string $current, string $icon): string
{
    $class = $active === $current ? 'quick-link active' : 'quick-link';

    return '<a href="' . e($href) . '" class="' . $class . '">' . $icon . '<span>' . e($label) . '</span></a>';
}

function render_sidebar(string $active = 'home'): void
{
    $home = '/event_project/index.php';
    $userEntry = '/event_project/auth.php?role=user';
    $adminEntry = '/event_project/auth.php?role=admin';
    $userPanel = '/event_project/user.php';
    $adminPanel = '/event_project/admin/admin.php';
    $isHome = $active === 'home';
    $isLoggedIn = is_logged_in();
    $role = current_role();
    $quickLinks = [];
    $sidebarTitle = 'Quick Access';
    $sidebarNoteTitle = 'One platform, two journeys';
    $sidebarNoteText = 'Attendees can discover and reserve events while administrators publish programs, track attendance, and review bookings.';

    if ($isHome) {
        $quickLinks = [
            app_nav_link($userEntry, 'User Panel', $active, 'user', app_icon('user')),
            app_nav_link($adminEntry, 'Admin Panel', $active, 'admin', app_icon('admin')),
        ];
        $sidebarTitle = 'Quick Access';
        $sidebarNoteTitle = 'EventHub Overview';
        $sidebarNoteText = 'Choose the correct panel to continue. Sign in and account creation happen only inside that panel.';
    } elseif (!$isLoggedIn && $active === 'user') {
        $quickLinks = [
            app_nav_link($home, 'Home', $active, 'home', app_icon('home')),
            app_nav_link($userEntry, 'User Panel', $active, 'user', app_icon('user')),
            app_nav_link($adminEntry, 'Admin Panel', $active, 'admin', app_icon('admin')),
        ];
        $sidebarTitle = 'Access Options';
        $sidebarNoteTitle = 'User Access';
        $sidebarNoteText = 'Use this page to sign in or create a user account before booking events.';
    } elseif (!$isLoggedIn && $active === 'admin') {
        $quickLinks = [
            app_nav_link($home, 'Home', $active, 'home', app_icon('home')),
            app_nav_link($userEntry, 'User Panel', $active, 'user', app_icon('user')),
            app_nav_link($adminEntry, 'Admin Panel', $active, 'admin', app_icon('admin')),
        ];
        $sidebarTitle = 'Access Options';
        $sidebarNoteTitle = 'Admin Access';
        $sidebarNoteText = 'Use this page to sign in or create an admin account before managing events.';
    } elseif ($isLoggedIn && $role === 'user') {
        $quickLinks = [
            app_nav_link($home, 'Home', $active, 'home', app_icon('home')),
            app_nav_link($userPanel, 'User Dashboard', $active, 'user', app_icon('user')),
            app_nav_link('/event_project/history.php', 'Booking History', $active, 'history', app_icon('history')),
            app_nav_link('/event_project/logout.php', 'Logout', $active, 'logout', app_icon('logout')),
        ];
        $sidebarTitle = 'User Menu';
        $sidebarNoteTitle = 'Attendee Workspace';
        $sidebarNoteText = 'Browse events, review reservations, and manage your booking activity from this panel.';
    } elseif ($isLoggedIn && $role === 'admin') {
        $quickLinks = [
            app_nav_link($home, 'Home', $active, 'home', app_icon('home')),
            app_nav_link($adminPanel, 'Admin Dashboard', $active, 'admin', app_icon('admin')),
            app_nav_link('/event_project/admin/create_event.php', 'Create Event', $active, 'create-event', app_icon('calendar')),
            app_nav_link('/event_project/logout.php', 'Logout', $active, 'logout', app_icon('logout')),
        ];
        $sidebarTitle = 'Admin Menu';
        $sidebarNoteTitle = 'Organizer Workspace';
        $sidebarNoteText = 'Review your event data, create new programs, and control admin activity from this panel.';
    } else {
        $quickLinks = [
            app_nav_link($home, 'Home', $active, 'home', app_icon('home')),
        ];
    }
    ?>
    <aside class="app-sidebar">
        <div class="brand-block">
            <div class="brand-mark"><?= app_icon('spark') ?></div>
            <div>
                <h1>EventHub</h1>
                <p>Event booking and management</p>
            </div>
        </div>

        <div>
            <p class="sidebar-group-title"><?= e($sidebarTitle) ?></p>
            <nav class="quick-nav">
                <?= implode('', $quickLinks) ?>
            </nav>
        </div>

        <div class="sidebar-note">
            <strong><?= e($sidebarNoteTitle) ?></strong>
            <?= e($sidebarNoteText) ?>
        </div>
    </aside>
    <?php
}

function app_shell_start(string $active = 'home'): void
{
    ?>
    <div class="app-shell">
        <?php render_sidebar($active); ?>
        <main class="app-main">
            <div class="page-stack">
    <?php
}

function app_shell_end(): void
{
    ?>
            </div>
        </main>
    </div>
    </body>
    </html>
    <?php
}

function app_icon(string $name): string
{
    $icons = [
        'spark' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2l1.9 5.1L19 9l-5.1 1.9L12 16l-1.9-5.1L5 9l5.1-1.9L12 2zm7 12l.9 2.1L22 17l-2.1.9L19 20l-.9-2.1L16 17l2.1-.9L19 14zM5 14l1.1 2.9L9 18l-2.9 1.1L5 22l-1.1-2.9L1 18l2.9-1.1L5 14z" fill="currentColor"/></svg>',
        'home' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5L12 4l8 6.5V20a1 1 0 01-1 1h-4.8v-6h-4.4v6H5a1 1 0 01-1-1v-9.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
        'user' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 0114 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'admin' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l7 4v5c0 4.4-3 7.9-7 9-4-1.1-7-4.6-7-9V7l7-4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9.5 12l1.7 1.7L14.8 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'history' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12a8 8 0 108-8 8.7 8.7 0 00-6.2 2.6L4 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 4v4h4M12 8v4l2.5 2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'calendar' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 3v4M8 3v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'logout' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 8l4 4-4 4M18 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 4H6a2 2 0 00-2 2v12a2 2 0 002 2h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'login' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17l-5-5 5-5M5 12h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 4h4a2 2 0 012 2v12a2 2 0 01-2 2h-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    ];

    return $icons[$name] ?? '';
}
?>
