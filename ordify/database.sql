CREATE DATABASE IF NOT EXISTS ordify CHARACTER SET utf8mb4;
USE ordify;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','customer','owner','courier') NOT NULL DEFAULT 'customer',
  status ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  verify_token VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
