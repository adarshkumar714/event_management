<?php
require_once __DIR__ . '/../config.php';
require_role('admin');

$error = '';
$success = get_flash('flash_success');
$hasCapacity = events_has_capacity();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $eventDate = $_POST['event_date'] ?? '';
        $price = (float) ($_POST['price'] ?? 0);
        $capacity = (int) ($_POST['capacity'] ?? 0);

        if ($title === '' || $description === '' || $venue === '' || $eventDate === '') {
            throw new RuntimeException('Please complete all fields.');
        }

        if ($price < 0) {
            throw new RuntimeException('Price cannot be negative.');
        }

        if ($hasCapacity && $capacity <= 0) {
            throw new RuntimeException('Capacity must be at least 1 ticket.');
        }

        $conn = db();
        $dateColumn = events_date_column();
        $adminId = current_user_id();

        if ($hasCapacity && events_has_admin_owner()) {
            $stmt = $conn->prepare('INSERT INTO events (admin_id, title, description, venue, ' . $dateColumn . ', price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issssdi', $adminId, $title, $description, $venue, $eventDate, $price, $capacity);
        } elseif ($hasCapacity) {
            $stmt = $conn->prepare('INSERT INTO events (title, description, venue, ' . $dateColumn . ', price, capacity) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssdi', $title, $description, $venue, $eventDate, $price, $capacity);
        } elseif (events_has_admin_owner()) {
            $stmt = $conn->prepare('INSERT INTO events (admin_id, title, description, venue, ' . $dateColumn . ', price) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issssd', $adminId, $title, $description, $venue, $eventDate, $price);
        } else {
            $stmt = $conn->prepare('INSERT INTO events (title, description, venue, ' . $dateColumn . ', price) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssd', $title, $description, $venue, $eventDate, $price);
        }
        $stmt->execute();
        $stmt->close();

        set_flash('flash_success', 'Event created successfully.');
        redirect('/event_project/admin/create_event.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<?php
app_head('Create Event | EventHub');
app_shell_start('create-event');
?>
<section class="surface-card hero-banner">
    <div class="hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Event publishing</span>
            <h1>Create a new event with clear details and structured ticket data.</h1>
            <p>Use this form to publish event information for attendees and keep your event inventory organized for the admin dashboard.</p>
            <div class="hero-actions">
                <a href="/event_project/admin/admin.php" class="btn btn-accent">Back to Dashboard</a>
                <a href="/event_project/logout.php" class="btn btn-outline-navy">Logout</a>
            </div>
        </div>

        <div class="graphic-panel">
            <ul class="list-clean">
                <li><span>1</span><div>Add a title, description, venue, and date so attendees can quickly understand the event.</div></li>
                <li><span>2</span><div>Set pricing and capacity to keep booking information accurate in both panels.</div></li>
                <li><span>3</span><div>New events immediately appear in the admin inventory and the user event listing.</div></li>
            </ul>
        </div>
    </div>
</section>

<section class="surface-card strong p-4 p-lg-5">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Event form</span>
            <h2 class="mt-3 h3">Publish a New Event</h2>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-4 mt-1">
        <div class="col-12">
            <label class="form-label">Event title</label>
            <input type="text" name="title" class="form-control" placeholder="Annual developer summit" required>
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" rows="5" class="form-control" placeholder="Describe the audience, agenda, and event experience." required></textarea>
        </div>
        <div class="col-lg-6">
            <label class="form-label">Venue</label>
            <input type="text" name="venue" class="form-control" placeholder="Convention Center Hall A" required>
        </div>
        <div class="col-lg-6">
            <label class="form-label">Event date</label>
            <input type="date" name="event_date" class="form-control" required>
        </div>
        <div class="col-lg-6">
            <label class="form-label">Ticket price</label>
            <input type="number" name="price" step="0.01" min="0" class="form-control" placeholder="0.00" required>
        </div>
        <?php if ($hasCapacity): ?>
            <div class="col-lg-6">
                <label class="form-label">Capacity</label>
                <input type="number" name="capacity" min="1" class="form-control" placeholder="100" required>
            </div>
        <?php endif; ?>
        <div class="col-12">
            <button class="btn btn-accent btn-lg w-100">Create Event</button>
        </div>
    </form>
</section>
<?php
app_shell_end();
?>
