-- phpMyAdmin SQL Dump
-- Database: ethioeventhub
CREATE DATABASE IF NOT EXISTS ethioeventhub;
USE ethioeventhub;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in reverse order of dependencies
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Table: users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('attendee', 'organizer', 'admin') DEFAULT 'attendee',
    phone VARCHAR(20),
    reset_token VARCHAR(255),
    token_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Table: categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-calendar',
    description TEXT
);
-- Table: events
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    venue VARCHAR(200),
    capacity INT DEFAULT 100,
    price DECIMAL(10, 2) DEFAULT 0.00,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
-- Table: bookings
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    booking_reference VARCHAR(20) UNIQUE,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'confirmed',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);
-- Table: reviews
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    rating INT CHECK (
        rating BETWEEN 1 AND 5
    ),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (user_id, event_id)
);
-- Table: wishlist
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, event_id)
);
-- Table: notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Insert sample categories
INSERT INTO categories (name, icon, description)
VALUES ('Music', 'fa-music', 'Concerts, live bands, music festivals'),
    (
        'Cultural',
        'fa-landmark',
        'Traditional events, Irreecha, Meskel'
    ),
    ('Sports', 'fa-running', 'Football matches, marathons'),
    ('Tech', 'fa-microchip', 'Workshops, hackathons, tech meetups');
-- Insert sample users (password = "password123" hashed)
INSERT INTO users (name, email, password, role, phone)
VALUES (
        'Alemu Organizer',
        'alemu@example.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'organizer',
        '0912345678'
    ),
    (
        'Biftu Attendee',
        'biftu@example.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'attendee',
        '0922334455'
    );
-- Insert sample events
INSERT INTO events (
        organizer_id,
        category_id,
        title,
        description,
        event_date,
        venue,
        capacity,
        price,
        image
    )
VALUES (
        1,
        2,
        'Irreecha Festival 2026',
        'Celebration of thanksgiving in Addis Ababa',
        '2026-10-05',
        'Meskel Square',
        5000,
        50.00,
        'irreecha.jpg'
    ),
    (
        1,
        1,
        'Ethio Jazz Night',
        'Live jazz with local artists',
        '2026-06-15',
        'Ghion Hotel',
        300,
        30.00,
        'jazz.jpg'
    ),
    (
        1,
        4,
        'Addis Tech Summit',
        'Meet tech entrepreneurs',
        '2026-07-20',
        'Addis Ababa University',
        200,
        0.00,
        'tech.jpg'
    );
-- Insert a sample booking
INSERT INTO bookings (user_id, event_id, quantity, total_price, booking_reference)
VALUES (2, 1, 2, 100.00, 'ETH-SAMPLE-1');
-- Insert a sample review
INSERT INTO reviews (user_id, event_id, rating, comment)
VALUES (2, 1, 5, 'Amazing cultural experience!');

SET FOREIGN_KEY_CHECKS = 1;