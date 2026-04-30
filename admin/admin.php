<?php
require_once __DIR__ . '/../config.php';
require_role('admin');

$conn = db();
$stats = [
    'events' => 0,
    'bookings' => 0,
    'revenue' => 0.0,
];
$adminId = current_user_id();
$adminFilter = events_has_admin_owner() ? ' WHERE admin_id = ' . $adminId : '';
$adminEventJoinFilter = events_has_admin_owner() ? ' WHERE e.admin_id = ' . $adminId : '';

$stats['events'] = (int) $conn->query('SELECT COUNT(*) AS total FROM events' . $adminFilter)->fetch_assoc()['total'];
$stats['bookings'] = (int) $conn->query('SELECT COUNT(*) AS total FROM bookings b INNER JOIN events e ON e.id = b.event_id' . $adminEventJoinFilter)->fetch_assoc()['total'];
$stats['revenue'] = (float) $conn->query('SELECT COALESCE(SUM(e.price), 0) AS total FROM bookings b INNER JOIN events e ON e.id = b.event_id' . $adminEventJoinFilter)->fetch_assoc()['total'];
$dateColumn = events_date_column();
$capacitySelect = events_has_capacity() ? 'e.capacity,' : '';

$events = $conn->query(
    'SELECT e.id, e.title, e.venue, e.' . $dateColumn . ' AS event_date, e.price, ' . $capacitySelect . ' COUNT(b.id) AS booked_tickets
     FROM events e
     LEFT JOIN bookings b ON b.event_id = e.id
     ' . ($adminEventJoinFilter !== '' ? $adminEventJoinFilter : '') . '
     GROUP BY e.id
     ORDER BY e.' . $dateColumn . ' ASC, e.title ASC'
)->fetch_all(MYSQLI_ASSOC);

$recentBookingsSelect = bookings_has_created_at() ? 'b.created_at' : 'NULL AS created_at';
$recentBookingsOrder = bookings_has_created_at() ? 'b.created_at DESC' : 'b.id DESC';
$recentBookings = $conn->query(
    'SELECT u.name AS user_name, e.title AS event_title, ' . $recentBookingsSelect . '
     FROM bookings b
     INNER JOIN users u ON u.id = b.user_id
     INNER JOIN events e ON e.id = b.event_id
     ' . ($adminEventJoinFilter !== '' ? $adminEventJoinFilter : '') . '
     ORDER BY ' . $recentBookingsOrder . '
     LIMIT 8'
)->fetch_all(MYSQLI_ASSOC);
?>
<?php
app_head('Admin Dashboard | EventHub');
app_shell_start('admin');
?>
<section class="surface-card hero-banner">
    <div class="hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Organizer overview</span>
            <h1>Welcome, <?= e($_SESSION['user_name'] ?? 'Admin') ?></h1>
            <p>Manage the full event lifecycle from one responsive dashboard with cleaner metrics, a clearer inventory view, and quick access to publishing tools.</p>
            <div class="hero-actions">
                <a href="/event_project/admin/create_event.php" class="btn btn-accent">Create Event</a>
                <a href="/event_project/logout.php" class="btn btn-outline-navy">Logout</a>
            </div>
        </div>

        <div class="graphic-panel">
            <p class="sidebar-group-title text-white-50">Admin snapshot</p>
            <div class="hero-stats">
                <div class="stat-chip">
                    <strong><?= $stats['events'] ?></strong>
                    <span>Total events</span>
                </div>
                <div class="stat-chip">
                    <strong><?= $stats['bookings'] ?></strong>
                    <span>Tickets booked</span>
                </div>
                <div class="stat-chip">
                    <strong>INR <?= number_format($stats['revenue'], 2) ?></strong>
                    <span>Revenue tracked</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="surface-card strong p-4 p-lg-5">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Platform metrics</span>
            <h2 class="mt-3 h3">Key Performance Snapshot</h2>
        </div>
    </div>
    <div class="metric-grid mt-4">
        <div class="metric-card">
            <span>Total events</span>
            <strong><?= $stats['events'] ?></strong>
        </div>
        <div class="metric-card">
            <span>Total bookings</span>
            <strong><?= $stats['bookings'] ?></strong>
        </div>
        <div class="metric-card">
            <span>Total revenue</span>
            <strong>INR <?= number_format($stats['revenue'], 2) ?></strong>
        </div>
    </div>
</section>

<section class="split-layout">
    <div class="surface-card strong p-4 p-lg-5">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Event inventory</span>
                <h2 class="mt-3 h3">Published Events</h2>
            </div>
            <a href="/event_project/admin/create_event.php" class="btn btn-outline-navy">Add Event</a>
        </div>

        <?php if (!$events): ?>
            <div class="empty-state mt-4">No events created yet.</div>
        <?php else: ?>
            <div class="table-shell mt-4">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <?php if (events_has_capacity()): ?>
                                    <th>Capacity</th>
                                <?php endif; ?>
                                <th>Booked</th>
                                <?php if (events_has_capacity()): ?>
                                    <th>Available</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($event['title']) ?></div>
                                        <small class="text-secondary">INR <?= number_format((float) $event['price'], 2) ?></small>
                                    </td>
                                    <td><?= e(date('d M Y', strtotime($event['event_date']))) ?></td>
                                    <td><?= e($event['venue']) ?></td>
                                    <?php if (isset($event['capacity'])): ?>
                                        <td><?= (int) $event['capacity'] ?></td>
                                    <?php endif; ?>
                                    <td><?= (int) $event['booked_tickets'] ?></td>
                                    <?php if (isset($event['capacity'])): ?>
                                        <td><?= event_available_tickets($event) ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="surface-card strong p-4 p-lg-5 sticky-card">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Recent activity</span>
                <h2 class="mt-3 h3">Latest Bookings</h2>
            </div>
        </div>

        <?php if (!$recentBookings): ?>
            <div class="empty-state mt-4">No bookings yet.</div>
        <?php else: ?>
            <div class="d-grid gap-3 mt-4">
                <?php foreach ($recentBookings as $booking): ?>
                    <article class="booking-highlight">
                        <div class="fw-semibold"><?= e($booking['user_name']) ?></div>
                        <div><?= e($booking['event_title']) ?></div>
                        <small class="text-secondary">
                            1 seat booked
                            <?php if (!empty($booking['created_at'])): ?>
                                on <?= e(date('d M Y h:i A', strtotime($booking['created_at']))) ?>
                            <?php endif; ?>
                        </small>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
app_shell_end();
?>
