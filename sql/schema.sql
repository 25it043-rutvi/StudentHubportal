-- =====================================================================
-- StudentHub Portal - Complete Relational Database Schema & Seed Data
-- Subject: Web Development Frameworks (ITUE203) - FTE CHARUSAT
-- Covers Practicals 8, 9, 10, 11, 12, 14
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `studenthub_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `studenthub_db`;

-- -------------------------------------------------------------
-- 1. Users Table (Students & Administrators)
-- Practicals 8, 9, 10, 11, 14
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `remember_tokens`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `phone` VARCHAR(15) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `course` VARCHAR(80) NOT NULL DEFAULT 'B.Tech IT',
  `year` VARCHAR(20) NOT NULL DEFAULT '3rd Year',
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Other',
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 2. Events Table (Campus Events with Poster Uploads)
-- Practicals 8, 12, 13
-- -------------------------------------------------------------
CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `category` ENUM('Technical', 'Workshop', 'Cultural', 'Sports') NOT NULL DEFAULT 'Technical',
  `event_date` DATE NOT NULL,
  `event_time` VARCHAR(50) NOT NULL,
  `venue` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `poster_image` VARCHAR(255) DEFAULT 'default_event.jpg',
  `max_seats` INT NOT NULL DEFAULT 100,
  `status` ENUM('Open', 'Closed') NOT NULL DEFAULT 'Open',
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 3. Registrations Table (Student Event Enrollment)
-- Practicals 8, 12, 14
-- -------------------------------------------------------------
CREATE TABLE `registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Confirmed', 'Waitlisted', 'Cancelled') DEFAULT 'Confirmed',
  `remarks` VARCHAR(255) DEFAULT NULL,
  UNIQUE KEY `unique_user_event` (`user_id`, `event_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 4. Audit Logs Table (System Activity & Traceability)
-- Practical 14
-- -------------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(50) NOT NULL,
  `details` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 5. Remember Tokens Table (Secure Persistent Login Tokens)
-- Practical 10 Advanced Extension
-- -------------------------------------------------------------
CREATE TABLE `remember_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 6. Seed Data (Default Admin, Sample Students & Events)
-- Passwords hashed using bcrypt:
-- 'Admin@123' => $2y$10$eO0V6j9Bv4g9V7C8z7u1veM5eG5Iq7hH7w0W7s0K8w2s5w9a8b1qK (example)
-- We use standard known bcrypt hashes for Admin@123 and Student@123
-- -------------------------------------------------------------

-- Admin account: admin@studenthub.com / Admin@123
-- Student account: student@studenthub.com / Student@123
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `course`, `year`, `gender`, `status`) VALUES
(1, 'System Administrator', 'admin@studenthub.com', '9876543200', '$2y$10$wGxeFtL4olcZdixc/4RTkuGqHgQjHKeEScWYhAiSYZW5w//7.tRc6', 'admin', 'Faculty Administration', 'Faculty', 'Other', 'Active'),
(2, 'Rutvi Patel', 'student@studenthub.com', '9876543210', '$2y$10$WTya1jX9iQp33Mp6BHU2FugjPIwF.Ny/9WnYAppSdI7TFcAscnxkC', 'student', 'B.Tech IT', '3rd Year', 'Female', 'Active'),
(3, 'Aarav Shah', 'aarav.shah@studenthub.edu', '9812345678', '$2y$10$WTya1jX9iQp33Mp6BHU2FugjPIwF.Ny/9WnYAppSdI7TFcAscnxkC', 'student', 'B.Tech CE', '2nd Year', 'Male', 'Active'),
(4, 'Diya Mehta', 'diya.mehta@studenthub.edu', '9823456789', '$2y$10$WTya1jX9iQp33Mp6BHU2FugjPIwF.Ny/9WnYAppSdI7TFcAscnxkC', 'student', 'B.Tech AIML', '3rd Year', 'Female', 'Active'),
(5, 'Kabir Verma', 'kabir.verma@studenthub.edu', '9834567890', '$2y$10$WTya1jX9iQp33Mp6BHU2FugjPIwF.Ny/9WnYAppSdI7TFcAscnxkC', 'student', 'B.Tech IT', '4th Year', 'Male', 'Inactive');

-- Seed Events
INSERT INTO `events` (`id`, `title`, `category`, `event_date`, `event_time`, `venue`, `description`, `poster_image`, `max_seats`, `status`, `created_by`) VALUES
(1, 'National Level Web Hackathon 2026', 'Technical', '2026-10-25', '09:00 AM - 05:00 PM', 'Campus Auditorium & Lab Complex', 'A 24-hour thrilling hackathon focusing on AI-driven web apps and modern framework solutions with exciting prizes.', 'hackathon.jpg', 150, 'Open', 1),
(2, 'Cloud Computing & DevOps Workshop', 'Workshop', '2026-11-05', '10:30 AM - 02:30 PM', 'Seminar Hall B', 'Hands-on industrial session on Docker, Kubernetes, CI/CD pipelines, and AWS cloud deployment architectures.', 'cloud_workshop.jpg', 80, 'Open', 1),
(3, 'Annual Inter-College Cultural Fest - Spandan', 'Cultural', '2026-11-18', '04:00 PM - 10:00 PM', 'Central Amphitheatre', 'Experience vibrant music, dance, theatrical acts, and art exhibitions celebrating campus creativity.', 'cultural_fest.jpg', 500, 'Open', 1),
(4, 'Cybersecurity & Ethical Hacking Symposium', 'Technical', '2026-11-22', '10:00 AM - 03:00 PM', 'Virtual & IT Seminar Hall', 'Explore network defense, web vulnerabilities, penetration testing, and zero-trust security paradigms.', 'cybersecurity.jpg', 120, 'Open', 1),
(5, 'Inter-Department Football Championship', 'Sports', '2026-12-02', '08:00 AM - 06:00 PM', 'Main Sports Ground', 'Annual 7-a-side football tournament featuring teams across all engineering and IT departments.', 'football.jpg', 32, 'Closed', 1);

-- Seed Registrations
INSERT INTO `registrations` (`id`, `user_id`, `event_id`, `registered_at`, `status`) VALUES
(1, 2, 1, '2026-10-09 10:30:00', 'Confirmed'),
(2, 2, 2, '2026-10-09 11:15:00', 'Confirmed'),
(3, 3, 1, '2026-10-09 14:20:00', 'Confirmed'),
(4, 4, 3, '2026-10-10 09:00:00', 'Confirmed');

-- Seed Audit Logs
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'SYSTEM_INIT', 'StudentHub database schema installed and initialized.', '127.0.0.1', '2026-10-08 09:00:00'),
(2, 1, 'EVENT_CREATED', 'Admin created event #1: National Level Web Hackathon 2026', '127.0.0.1', '2026-10-08 10:00:00'),
(3, 2, 'USER_REGISTERED', 'Student Rutvi Patel registered account.', '192.168.1.15', '2026-10-09 10:25:00'),
(4, 2, 'EVENT_REGISTER', 'Student enrolled in event #1', '192.168.1.15', '2026-10-09 10:30:00');
