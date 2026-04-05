-- ============================================
-- Sir Jon's Kitchen — Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS chef_jon_chung
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE chef_jon_chung;

-- ============================================
-- Admin users
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Recipe categories
-- ============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

-- ============================================
-- Recipes
-- ============================================
CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    youtube_url VARCHAR(500),
    thumbnail_url VARCHAR(500),
    ingredients JSON,
    steps JSON,
    cook_time_minutes INT,
    servings INT,
    difficulty ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Easy',
    category_id INT,
    is_featured TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Blog posts
-- ============================================
CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    body TEXT,
    thumbnail_url VARCHAR(500),
    youtube_url VARCHAR(500),
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Contact form submissions
-- ============================================
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(100),
    sender_email VARCHAR(255),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Editable site settings (about page, social links, etc.)
-- ============================================
CREATE TABLE site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Seed data
-- ============================================

-- Default admin account (password: changeme123)
-- IMPORTANT: Change this password immediately after first login!
INSERT INTO users (username, password_hash, display_name) VALUES
('chefjon', '$2y$10$placeholder_hash_replace_on_setup', 'Chef Jon');

-- Default categories
INSERT INTO categories (name, slug, sort_order) VALUES
('Korean', 'korean', 1),
('Fusion', 'fusion', 2),
('Quick Meals', 'quick-meals', 3),
('Desserts', 'desserts', 4),
('Side Dishes', 'side-dishes', 5);

-- Default site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Chef Jon Chung'),
('site_tagline', 'Authentic Korean Recipes & More'),
('about_content', 'Welcome to my kitchen! I share my favorite Korean recipes and cooking tips.'),
('social_youtube', ''),
('social_instagram', ''),
('social_tiktok', ''),
('footer_text', '© 2026 Chef Jon Chung. All rights reserved.');
