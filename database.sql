CREATE DATABASE IF NOT EXISTS event_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE event_db;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    venue VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    tickets INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@eventhub.local', '$2y$10$JaSMB4QX6RG3CzfhcicCmODArHD7hmTMQQN0bxKrWbq64Ju7XDAlu', 'admin');

INSERT INTO events (title, description, venue, event_date, price, capacity) VALUES
('Spring Tech Summit', 'A one-day conference focused on practical web and PHP tooling.', 'City Convention Center', '2026-05-15', 1499.00, 120),
('Design Futures Workshop', 'Hands-on design and prototyping sessions for product teams.', 'Studio Hall', '2026-05-22', 899.00, 40),
('Startup Networking Night', 'An evening meetup for founders, builders, and investors.', 'Riverfront Hub', '2026-06-05', 499.00, 80);
