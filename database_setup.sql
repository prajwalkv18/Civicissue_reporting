CREATE DATABASE IF NOT EXISTS civictrack_db;
USE civictrack_db;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(15) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    ward_locality VARCHAR(100),
    city VARCHAR(100),
    whatsapp_opt_in TINYINT(1) DEFAULT 0,
    role ENUM('resident', 'admin', 'engineer') DEFAULT 'resident',
    employee_id VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Issues/Reports Table
CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    issue_type VARCHAR(50) NOT NULL,
    location_text VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    ward VARCHAR(100),
    description TEXT,
    priority ENUM('Normal', 'High', 'Urgent') DEFAULT 'Normal',
    status ENUM('Open', 'In Progress', 'Resolved') DEFAULT 'Open',
    photo_path VARCHAR(255),
    resolved_at TIMESTAMP NULL,
    is_citizen_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    action_description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE
);

-- Insert a default admin user for testing purposes
INSERT IGNORE INTO users (phone, full_name, ward_locality, city, whatsapp_opt_in, role) 
VALUES ('9999999999', 'System Admin', 'Central', 'Mumbai', 1, 'admin');

-- 4. Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    issue_id INT NULL,
    title VARCHAR(100) NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE SET NULL
);
