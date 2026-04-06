-- ============================================
-- Chef Jon Chung — Feature Update Migration
-- Run this in phpMyAdmin on Hostinger
-- ============================================

-- ── Recipe enhancements ───────────────────
ALTER TABLE recipes ADD COLUMN spice_level ENUM('None', 'Mild', 'Medium', 'Hot', 'Extra Hot') DEFAULT 'None' AFTER difficulty;
ALTER TABLE recipes ADD COLUMN allergens JSON AFTER spice_level;
ALTER TABLE recipes ADD COLUMN dietary JSON AFTER allergens;
ALTER TABLE recipes ADD COLUMN sort_order INT DEFAULT 0 AFTER is_featured;

-- ── Recipe photo gallery ──────────────────
CREATE TABLE IF NOT EXISTS recipe_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    caption VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Recipe ratings ────────────────────────
CREATE TABLE IF NOT EXISTS recipe_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    visitor_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Page view tracking ────────────────────
CREATE TABLE IF NOT EXISTS page_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_type ENUM('recipe', 'blog', 'home', 'about', 'contact', 'recipes', 'other') NOT NULL,
    page_id INT DEFAULT NULL,
    visitor_ip VARCHAR(45),
    user_agent VARCHAR(500),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page (page_type, page_id),
    INDEX idx_date (viewed_at)
) ENGINE=InnoDB;

-- ── Newsletter subscribers ────────────────
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
