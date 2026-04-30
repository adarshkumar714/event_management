<?php
require_once __DIR__ . '/config.php';
require_role('user');

$conn = db();
$dateColumn = events_date_column();
$bookedOnSelect = bookings_has_created_at() ? 'b.created_at' : 'NULL AS created_at';
$orderColumn = bookings_has_created_at() ? 'b.created_at DESC' : 'b.id DESC';
$stmt = $conn->prepare(
    'SELECT e.title, e.venue, e.' . $dateColumn . ' AS event_date, e.price, b.tickets, ' . $bookedOnSelect . '
     FROM bookings b
     INNER JOIN events e ON e.id = b.event_id
     WHERE b.user_id = ?
     ORDER BY ' . $orderColumn
);
$userId = current_user_id();
$stmt->bind_param('i', $userId);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php
app_head('Booking History | EventHub');
app_shell_start('history');
?>
<section class="surface-card hero-banner">
    <div class="hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Booking history</span>
            <h1>Your reservations in one organized view.</h1>
            <p>Review where you are going, when each event happens, and how much you paid through a responsive table designed for quick scanning.</p>
            <div class="hero-actions">
                <a href="/event_project/user.php" class="btn btn-accent">Back to Events</a>
                <a href="/event_project/logout.php" class="btn btn-outline-navy">Logout</a>
            </div>
        </div>

        <div class="graphic-panel">
            <div class="hero-stats">
                <div class="stat-chip">
                    <strong><?= count($bookings) ?></strong>
                    <span>Total booking(s)</span>
                </div>
                <div class="stat-chip">
                    <strong>INR <?= number_format(array_reduce($bookings, static fn(float $sum, array $booking): float => $sum + (float) $booking['price'], 0.0), 2) ?></strong>
                    <span>Total amount paid</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="surface-card strong p-4 p-lg-5">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Booking details</span>
            <h2 class="mt-3 h3">Reservation Summary</h2>
        </div>
    </div>

    <?php if (!$bookings): ?>
        <div class="empty-state">You have not booked any events yet.</div>
    <?php else: ?>
        <div class="table-shell mt-4">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Venue</th>
                            <th>Date</th>
                            <th>Seat</th>
                            <th>Total Paid</th>
                            <?php if (bookings_has_created_at()): ?>
                                <th>Booked On</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($booking['title']) ?></td>
                                <td><?= e($booking['venue']) ?></td>
                                <td><?= e(date('d M Y', strtotime($booking['event_date']))) ?></td>
                                <td>1</td>
                                <td>INR <?= number_format((float) $booking['price'], 2) ?></td>
                                <?php if (bookings_has_created_at()): ?>
                                    <td><?= e(date('d M Y h:i A', strtotime($booking['created_at']))) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php
app_shell_end();
?>
