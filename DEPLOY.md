# Deploying to Hostinger — Step by Step

## 1. Log into Hostinger hPanel

Go to [hpanel.hostinger.com](https://hpanel.hostinger.com) and select your `chefjonchung.com` hosting plan.

---

## 2. Create the Database

1. In hPanel, go to **Databases** → **MySQL Databases**
2. Create a new database:
   - Database name: `chefjon` (Hostinger will prefix it, e.g., `u123456789_chefjon`)
   - Username: `chefjon` (will become `u123456789_chefjon`)
   - Password: pick a strong password and **write it down**
3. Click **Create**
4. Note down the full database name, username, and password

---

## 3. Import the Schema

1. In hPanel, go to **Databases** → **phpMyAdmin**
2. Click on your new database in the left sidebar
3. Click the **Import** tab
4. Choose `schema.sql` from your project files
5. Click **Go**
6. You should see all 6 tables created + seed data

---

## 4. Upload the Files

**Option A — File Manager (easiest):**
1. In hPanel, go to **Files** → **File Manager**
2. Navigate to `public_html/`
3. Delete any default files (like `index.html`)
4. Upload the project zip file
5. Extract it into `public_html/`
6. Make sure all files are directly in `public_html/` (not in a subfolder)

**Option B — FTP (if you prefer):**
1. In hPanel, go to **Files** → **FTP Accounts**
2. Note the FTP hostname, username, and password
3. Use FileZilla or similar to upload all project files to `public_html/`

Your `public_html/` should look like:
```
public_html/
├── admin/
├── assets/
├── includes/
├── index.php
├── recipes.php
├── recipe.php
├── ...etc
```

---

## 5. Create the Production Config

1. In File Manager, navigate to `public_html/includes/`
2. Create a new file called `config.local.php`
3. Paste this content (update with YOUR credentials from step 2):

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_chefjon');
define('DB_USER', 'u123456789_chefjon');
define('DB_PASS', 'YourPasswordFromStep2');
define('SITE_URL', 'https://chefjonchung.com');
define('SITE_NAME', 'Chef Jon Chung');
ini_set('display_errors', 0);
error_reporting(0);
```

---

## 6. Set the Admin Password

Since you can't run `setup.php` from the command line on shared hosting, do this instead:

1. Go to phpMyAdmin (hPanel → Databases → phpMyAdmin)
2. Click on your database
3. Click on the `users` table
4. Click **Edit** on the `chefjon` row
5. In the `password_hash` field, paste this temporary hash:
   `$2y$10$YourHashHere`

**Or easier:** Open your browser to `https://chefjonchung.com/admin/login.php` — if the hash from your local setup was imported with the schema, your existing password should work. If not, you can generate a new hash:

1. Create a temporary file `public_html/temp_hash.php`:
   ```php
   <?php echo password_hash('YourNewPassword', PASSWORD_DEFAULT); ?>
   ```
2. Visit `https://chefjonchung.com/temp_hash.php`
3. Copy the hash it shows
4. Paste it into phpMyAdmin in the `password_hash` field
5. **Delete `temp_hash.php` immediately**

---

## 7. Enable SSL (HTTPS)

1. In hPanel, go to **Security** → **SSL**
2. Enable the free SSL certificate for `chefjonchung.com`
3. Wait a few minutes for it to activate
4. Once active, edit `.htaccess` and uncomment the HTTPS redirect:
   ```
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

---

## 8. Set File Permissions

1. In File Manager, navigate to `public_html/assets/images/uploads/`
2. Right-click the `uploads` folder → **Permissions**
3. Set to `755`

---

## 9. Clean Up

Delete these files from `public_html/` (they shouldn't be on the live server):
- `setup.php`
- `schema.sql`
- `temp_hash.php` (if you created it)

---

## 10. Test Everything

- [ ] Homepage loads: `https://chefjonchung.com`
- [ ] Recipes page: `https://chefjonchung.com/recipes`
- [ ] Admin login: `https://chefjonchung.com/admin/login.php`
- [ ] Add a test recipe from admin
- [ ] Check it appears on the public site
- [ ] Test the contact form
- [ ] Check on mobile

---

## Done!

Your site is live at **https://chefjonchung.com** 🎉
