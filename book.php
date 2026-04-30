<?php
require_once __DIR__ . '/config.php';
require_role('user');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['book'])) {
    redirect('/event_project/user.php');
}

$eventId = (int) ($_POST['event_id'] ?? 0);
$tickets = 1;

try {
    if ($eventId <= 0 || $tickets <= 0) {
        throw new RuntimeException('Please choose a valid event and ticket count.');
    }

    $conn = db();
    $hasCapacity = events_has_capacity();
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        'SELECT e.id, e.title, e.capacity, COUNT(b.id) AS booked_tickets
         FROM events e
         LEFT JOIN bookings b ON b.event_id = e.id
         WHERE e.id = ?
         GROUP BY e.id
         FOR UPDATE'
    );

    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$event) {
        throw new RuntimeException('That event no longer exists.');
    }

    $checkExisting = $conn->prepare('SELECT id FROM bookings WHERE user_id = ? AND event_id = ? LIMIT 1');
    $userId = current_user_id();
    $checkExisting->bind_param('ii', $userId, $eventId);
    $checkExisting->execute();
    $existingBooking = $checkExisting->get_result()->fetch_assoc();
    $checkExisting->close();

    if ($existingBooking) {
        throw new RuntimeException('You can book this event only once.');
    }

    if ($hasCapacity) {
        $available = event_available_tickets($event);
        if ($available < 1) {
            throw new RuntimeException('No seats are available for ' . $event['title'] . '.');
        }
    }

    $insert = $conn->prepare('INSERT INTO bookings (user_id, event_id, tickets) VALUES (?, ?, ?)');
    $insert->bind_param('iii', $userId, $eventId, $tickets);
    $insert->execute();
    $insert->close();

    $conn->commit();

    $userStmt = $conn->prepare('SELECT name, email FROM users WHERE id = ? LIMIT 1');
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    $emailSent = false;
    if ($user && !empty($user['email'])) {
        $subject = 'EventHub Booking Confirmation - ' . $event['title'];
        $messageLines = [
            'Hello ' . ($user['name'] ?? 'Guest') . ',',
            '',
            'Your event booking has been confirmed.',
            '',
            'Event: ' . $event['title'],
            'Tickets: 1',
            '',
            'Please check your booking history in EventHub for the latest details.',
            'You will need a working mail setup on the server to receive future email updates automatically.',
            '',
            'Thank you,',
            'EventHub',
        ];
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: EventHub <no-reply@eventhub.local>',
        ];

        $emailSent = @mail($user['email'], $subject, implode("\r\n", $messageLines), implode("\r\n", $headers));
    }

    $flashMessage = 'Booking confirmed for ' . $event['title'] . '. You have reserved 1 seat.';
    if ($emailSent && $user) {
        $flashMessage .= ' Your ticket and event details have been sent to ' . $user['email'] . '. Please check your email for updates.';
    } else {
        $flashMessage .= ' Please check My Bookings for your ticket and event details. Email updates will work once mail is configured on the server.';
    }

    set_flash('flash_success', $flashMessage);
} catch (Throwable $exception) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    set_flash('flash_error', $exception->getMessage());
}

redirect('/event_project/user.php');
?>
