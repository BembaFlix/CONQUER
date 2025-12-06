-- Create database
CREATE DATABASE IF NOT EXISTS conquer_gym;
USE conquer_gym;

-- Members table (existing)
CREATE TABLE gym_members (
    ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Age INT(3) NOT NULL,
    MembershipPlan VARCHAR(50) NOT NULL,
    ContactNumber VARCHAR(15) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    JoinDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    MembershipStatus ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active'
);

-- Users table for login system
CREATE TABLE users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('member', 'trainer', 'admin') DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Success stories table
CREATE TABLE success_stories (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED,
    title VARCHAR(200) NOT NULL,
    before_image VARCHAR(255),
    after_image VARCHAR(255),
    story_text TEXT NOT NULL,
    weight_loss DECIMAL(5,2),
    months_taken INT(3),
    trainer_id INT(11) UNSIGNED,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Trainers table
CREATE TABLE trainers (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED UNIQUE,
    specialty VARCHAR(100) NOT NULL,
    certification VARCHAR(200),
    years_experience INT(3),
    bio TEXT,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Classes table
CREATE TABLE classes (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    trainer_id INT(11) UNSIGNED,
    schedule DATETIME NOT NULL,
    duration_minutes INT NOT NULL,
    max_capacity INT NOT NULL,
    current_enrollment INT DEFAULT 0,
    class_type ENUM('yoga', 'hiit', 'strength', 'cardio', 'crossfit', 'others'),
    difficulty_level ENUM('beginner', 'intermediate', 'advanced'),
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL,
);

-- Bookings table
CREATE TABLE bookings (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED,
    class_id INT(11) UNSIGNED,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled', 'attended', 'no-show'),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('credit_card', 'debit_card', 'paypal', 'bank_transfer', 'cash'),
    status ENUM('completed', 'pending', 'failed', 'refunded'),
    subscription_period VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Equipment table
CREATE TABLE equipment (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_name VARCHAR(100) NOT NULL,
    brand VARCHAR(100),
    purchase_date DATE,
    last_maintenance DATE,
    next_maintenance DATE,
    status ENUM('active', 'maintenance', 'retired'),
    location VARCHAR(100)
);

-- Contact messages table
CREATE TABLE contact_messages (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'read', 'replied', 'closed')
);

-- Insert sample data
INSERT INTO users (username, email, password_hash, full_name, user_type) VALUES
('admin', 'admin@conquergym.com', '$2y$10$YourHashedPasswordHere', 'Administrator', 'admin'),
('markj', 'mark@conquergym.com', '$2y$10$YourHashedPasswordHere', 'Mark Johnson', 'trainer'),
('sarahc', 'sarah@conquergym.com', '$2y$10$YourHashedPasswordHere', 'Sarah Chen', 'trainer'),
('john_doe', 'john@email.com', '$2y$10$YourHashedPasswordHere', 'John Doe', 'member');

INSERT INTO trainers (user_id, specialty, certification, years_experience, bio) VALUES
(2, 'Strength & Conditioning', 'NASM Certified, CrossFit Level 2', 10, 'Former professional athlete with 10+ years training experience'),
(3, 'Yoga & Mobility', 'RYT 500, ACE Certified', 8, 'Specialized in yoga therapy and mobility training');

INSERT INTO gym_members (Name, Age, MembershipPlan, ContactNumber, Email) VALUES
('John Doe', 28, 'Legend', '555-0101', 'john@email.com'),
('Jane Smith', 32, 'Champion', '555-0102', 'jane@email.com'),
('Bob Wilson', 45, 'Warrior', '555-0103', 'bob@email.com');

-- Create indexes for performance
CREATE INDEX idx_members_email ON gym_members(Email);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_stories_featured ON success_stories(is_featured, approved);
CREATE INDEX idx_classes_schedule ON classes(schedule);
CREATE INDEX idx_payments_user ON payments(user_id, payment_date);