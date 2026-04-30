<?php
require_once __DIR__ . '/config.php';
require_role('user');

$conn = db();
$error = get_flash('flash_error');
$success = get_flash('flash_success');
$dateColumn = events_date_column();
$capacitySelect = events_has_capacity() ? 'e.capacity,' : '';
$orderDate = 'e.' . $dateColumn;
$userId = current_user_id();

$events = $conn->query(
    'SELECT e.id, e.title, e.description, e.venue, e.' . $dateColumn . ' AS event_date, e.price, ' . $capacitySelect . ' COUNT(b.id) AS booked_tickets,
            MAX(CASE WHEN b.user_id = ' . $userId . ' THEN 1 ELSE 0 END) AS already_booked
     FROM events e
     LEFT JOIN bookings b ON b.event_id = e.id
     GROUP BY e.id
     ORDER BY ' . $orderDate . ' ASC, e.title ASC'
)->fetch_all(MYSQLI_ASSOC);
?>
<?php
app_head('User Dashboard | EventHub');
app_shell_start('user');
?>
<section class="surface-card hero-banner">
    <div class="hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Attendee dashboard</span>
            <h1>Welcome, <?= e($_SESSION['user_name'] ?? 'Guest') ?></h1>
            <p>Explore upcoming events, review ticket availability, and book your seat with a cleaner full-screen layout built for comfortable browsing.</p>

            <div class="hero-actions">
                <a href="/event_project/history.php" class="btn btn-accent">View My Bookings</a>
                <a href="/event_project/logout.php" class="btn btn-outline-navy">Logout</a>
            </div>
        </div>

        <div class="graphic-panel">
            <p class="sidebar-group-title text-white-50">Booking at a glance</p>
            <div class="hero-stats">
                <div class="stat-chip">
                    <strong><?= count($events) ?></strong>
                    <span>Published events</span>
                </div>
                <div class="stat-chip">
                    <strong><?= count(array_filter($events, static fn(array $event): bool => (int) $event['already_booked'] === 1)) ?></strong>
                    <span>Your bookings</span>
                </div>
            </div>
            <ul class="list-clean mt-4">
                <li><span>1</span><div>Each event card shows venue, event date, price, and available seats in a consistent structure.</div></li>
                <li><span>2</span><div>Booking actions stay at the bottom of each card so they are easy to spot on any screen size.</div></li>
            </ul>
        </div>
    </div>
</section>

<?php if ($error): ?>
    <div class="alert alert-danger mb-0"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success mb-0"><?= e($success) ?></div>
<?php endif; ?>

<section class="surface-card strong p-4 p-lg-5">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Event listings</span>
            <h2 class="mt-3 h3">Available Events</h2>
        </div>
        <p class="text-secondary mb-0"><?= count($events) ?> event(s) ready for booking</p>
    </div>

    <?php if (!$events): ?>
        <div class="empty-state">No events have been created yet. Please check back soon.</div>
    <?php else: ?>
        <div class="event-grid mt-4">
            <?php foreach ($events as $event): ?>
                <?php $available = event_available_tickets($event); ?>
                <article class="event-card">
                    <div class="event-topline">
                        <h3 class="h4"><?= e($event['title']) ?></h3>
                        <?php if (isset($event['capacity'])): ?>
                            <span class="meta-pill"><?= $available ?> seat(s) left</span>
                        <?php endif; ?>
                    </div>

                    <p><?= e($event['description']) ?></p>

                    <div class="meta-stack">
                        <div class="mini-meta"><strong>Venue</strong><span class="text-secondary"><?= e($event['venue']) ?></span></div>
                        <div class="mini-meta"><strong>Date</strong><span class="text-secondary"><?= e(date('d M Y', strtotime($event['event_date']))) ?></span></div>
                        <div class="mini-meta"><strong>Booking rule</strong><span class="text-secondary">One seat per user</span></div>
                    </div>

                    <div class="event-topline mt-auto">
                        <span class="price-tag">INR <?= number_format((float) $event['price'], 2) ?></span>
                        <?php if ((int) $event['already_booked'] === 1): ?>
                            <span class="badge text-bg-success">Booked</span>
                        <?php elseif (isset($event['capacity']) && $available === 0): ?>
                            <span class="badge text-bg-danger">Sold Out</span>
                        <?php else: ?>
                            <span class="badge text-bg-primary">Open</span>
                        <?php endif; ?>
                    </div>

                    <form action="/event_project/book.php" method="POST" class="mt-2">
                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                        <button name="book" class="btn btn-accent w-100" <?= ((isset($event['capacity']) && $available === 0) || (int) $event['already_booked'] === 1) ? 'disabled' : '' ?>>
                            <?=
                                (int) $event['already_booked'] === 1
                                    ? 'Already Booked'
                                    : ((isset($event['capacity']) && $available === 0) ? 'Sold Out' : 'Book 1 Seat')
                            ?>
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
app_shell_end();
?>
