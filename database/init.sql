-- Create database
CREATE DATABASE IF NOT EXISTS tm_customer_data;
USE tm_customer_data;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    otp_secret VARCHAR(32) DEFAULT NULL,
    otp_valid_until TIMESTAMP NULL DEFAULT NULL
);

-- Create customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    birthday DATE NOT NULL,
    ic_number VARCHAR(12) NOT NULL,
    payment_method ENUM('credit_card', 'online_banking', 'cash') NOT NULL,
    subscription_plan VARCHAR(50) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create audit_logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    changes TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create default admin account (password: admin123)
INSERT IGNORE INTO users (username, password, role, email) 
VALUES ('admin', '$2y$10$8tl8.x9XAzXNp.mDQ8Dadu2T9VSfSH5f4/0mJJw0pfYE.K3.8nZ8q', 'admin', 'teckangoh@gmail.com');
VALUES ('wx', '$2y$10$8tl8.x9XAzXNp.mDQ8Dadu2T9VSfSH5f4/0mJJw0pfYE.K3.8nZ8q', 'admin', 'wenxuanyeah@gmail.com');
VALUES ('najmi', '$2y$10$8tl8.x9XAzXNp.mDQ8Dadu2T9VSfSH5f4/0mJJw0pfYE.K3.8nZ8q', 'admin', 'knajmi@gmail.com');
VALUES ('irfan', '$2y$10$8tl8.x9XAzXNp.mDQ8Dadu2T9VSfSH5f4/0mJJw0pfYE.K3.8nZ8q', 'admin', 'lrfanyazid16@gmail.com');
VALUES ('mustaqim', '$2y$10$8tl8.x9XAzXNp.mDQ8Dadu2T9VSfSH5f4/0mJJw0pfYE.K3.8nZ8q', 'admin', 'mohamadmustaqim02@gmail.com');
