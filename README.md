# 🍳 Chef Jon Chung

A dynamic, PHP-powered cooking recipe website with a full-featured admin panel. Built for a Korean cooking enthusiast to independently manage recipes, blog posts, and site content — no code required.

**Live:** [chefjonchung.com](https://chefjonchung.com)

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

---

## Features

### Public-Facing Site
- **Recipe pages** with embedded YouTube video, ingredients, step-by-step instructions, cook time, difficulty, and servings
- **Blog / Tips section** for cooking advice and stories
- **Category filtering** (Korean, Fusion, Quick Meals, etc.)
- **About & Contact pages** with form submissions stored in database
- **Responsive design** — mobile-first with Korean-inspired warm visual identity

### Admin Panel
- **Dashboard** with stats overview, recent recipes, and unread messages
- **Full CRUD** for recipes and blog posts (add, edit, delete, publish/draft toggle)
- **Recipe form** — paste a YouTube URL, type ingredients line-by-line, auto-generated thumbnails
- **Category management** for organizing recipes
- **Contact message inbox** with read/unread status
- **Site settings editor** — update about page, social links, and footer text
- **Password management** — secure account settings
- **Login rate limiting** — brute force protection (5 attempts / 15 min lockout)

### Security
- All SQL queries use **PDO prepared statements** (no raw queries)
- Passwords hashed with `password_hash()` / `password_verify()` (bcrypt)
- **CSRF protection** on all forms
- Input sanitization with `htmlspecialchars()` on all output
- File upload validation (type and size)
- Session-based authentication with `session_regenerate_id()`
- SQL injection prevention via **table name whitelisting** in dynamic queries
- **Login rate limiting** to prevent brute force attacks

---

## Tech Stack

| Layer       | Technology                      |
|-------------|--------------------------------|
| Backend     | PHP 8.0+                      |
| Database    | MySQL 8.0 (InnoDB, utf8mb4)   |
| Frontend    | Vanilla CSS + JavaScript       |
| Typography  | Google Fonts (DM Sans, Playfair Display) |
| Video       | YouTube embed API              |
| Server      | Apache (mod_rewrite)           |
| Hosting     | Hostinger                      |

---

## Project Structure

```
chef-jon-chung/
├── index.php                  # Homepage
├── recipes.php                # Recipes listing
├── recipe.php                 # Single recipe page
├── blog.php                   # Blog listing
├── post.php                   # Single blog post
├── about.php                  # About page
├── contact.php                # Contact form
├── admin/                     # Admin panel (auth-protected)
│   ├── index.php              # Dashboard
│   ├── login.php / logout.php # Authentication
│   ├── recipes.php            # Manage recipes
│   ├── recipe-edit.php        # Add/edit recipe
│   ├── blog-posts.php         # Manage blog posts
│   ├── blog-edit.php          # Add/edit blog post
│   ├── categories.php         # Manage categories
│   ├── messages.php           # Contact submissions
│   ├── settings.php           # Site settings
│   └── account.php            # Change password
├── includes/                  # Core PHP modules
│   ├── config.php             # Configuration & constants
│   ├── db.php                 # PDO wrapper & helpers
│   ├── auth.php               # Authentication logic
│   ├── header.php / footer.php
│   └── admin-header.php / admin-footer.php
├── assets/css/                # Stylesheets
├── assets/js/                 # Client-side scripts
├── schema.sql                 # Database schema
└── setup.php                  # Initial setup script
```

---

## Database Schema

```
users ─────────── Admin credentials
categories ────── Recipe categories (Korean, Fusion, etc.)
recipes ──────── Recipe data (title, video, ingredients, steps, metadata)
blog_posts ────── Blog entries
contact_messages ─ Form submissions from visitors
site_settings ──── Key-value store for editable site content
```

Recipes store ingredients and steps as **JSON arrays**, allowing flexible, schema-free content managed through simple line-by-line text inputs in the admin.

---

## Setup

### Prerequisites
- PHP 8.0+ with PDO MySQL extension
- MySQL 8.0+
- Apache with mod_rewrite (or equivalent)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/JasemAliKwt/chef-jon-chung.git
   cd chef-jon-chung
   ```

2. **Create the database**
   ```bash
   mysql -u root -p < schema.sql
   ```

3. **Configure database credentials**
   
   Create `includes/config.local.php` with your production credentials:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'chef_jon_chung');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('SITE_URL', 'https://chefjonchung.com');
   ```

4. **Set the admin password**
   ```bash
   php setup.php
   ```

5. **Set file permissions**
   ```bash
   chmod 755 assets/images/uploads/
   ```

6. **Delete the setup script**
   ```bash
   rm setup.php
   ```

7. **Access the site**
   - Public: `http://localhost/chef-jon-chung/`
   - Admin: `http://localhost/chef-jon-chung/admin/login.php`

---

## Design Philosophy

The visual design draws from Korean culinary culture:
- **Color palette** inspired by gochugaru red, sesame oil gold, and banchan greens
- **Warm, inviting tones** on clean off-white backgrounds
- **Food-forward layout** with large thumbnails and generous whitespace
- **Admin panel** designed for non-technical users with clear, accessible controls

---

## Author

**Jasem Ali**  
MS Electrical & Computer Engineering, UC Davis  
[jasemali.net](https://jasemali.net) · [GitHub](https://github.com/JasemAliKwt)

---

## License

MIT License — see [LICENSE](LICENSE) for details.
