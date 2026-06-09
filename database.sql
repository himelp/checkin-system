-- Users table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `lang` VARCHAR(10) NOT NULL DEFAULT 'en',
    `timezone` VARCHAR(50) NOT NULL DEFAULT 'UTC',
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Check log table
CREATE TABLE `check_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `checkin_time` DATETIME NOT NULL,
    `checkout_time` DATETIME NULL,
    `duration_minutes` INT NULL,
    `date` DATE NOT NULL,
    `status` ENUM('active', 'done') NOT NULL DEFAULT 'active',
    `sheets_row` INT NULL,
    `ip_address` VARCHAR(45) NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rate limiting table
CREATE TABLE `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table
CREATE TABLE `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_receiver` (`receiver_id`, `is_read`),
    INDEX `idx_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`name`, `username`, `password`, `role`, `lang`, `timezone`, `status`) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'en', 'UTC', 1);
