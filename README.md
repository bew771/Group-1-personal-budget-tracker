Personal Budget Tracker (PHP + MySQL)

Requirements
- XAMPP (Apache + MySQL)
- PHP 7.4+ (or compatible)

Setup
1. Copy the `budget-tracker` folder to your XAMPP `htdocs` directory.
2. Create a MySQL database or run `setup.php` to create it automatically. (If `schema.sql` exists, `setup.php` will import it.)
3. Edit `db.php` with your MySQL credentials if not using the default (root, no password).
4. Start Apache and MySQL in XAMPP Control Panel.
5. Visit `http://localhost/budget-tracker/` to access the landing page.

Quick setup script
- If you see the error "Unknown database 'budget_tracker'", run `setup.php` once to create the DB and import `schema.sql` automatically:
  - Open in browser: http://localhost/swe%20group1/budget-tracker/setup.php (or http://localhost/swe-group1/budget-tracker/setup.php if you renamed the folder)
  - After it runs, delete `setup.php` and load `db_test.php` to confirm the connection.

Troubleshooting
- If `setup.php` reports a MySQL connection error, verify MySQL is running in XAMPP Control Panel and you have the correct user/password in `db.php` and `setup.php`.
- If you see an error in `setup.php` about parsing `schema.sql`, ensure `schema.sql` is plain SQL (no `<?php`/`?>` tags) — the project now strips PHP tags automatically, but
  you can also open `schema.sql` and ensure it starts with `CREATE DATABASE`.
- If you get a PDO driver error, enable `extension=pdo_mysql` in your `php.ini` and restart Apache.

Notes
- This is a starter scaffold: pages include authentication, dashboard, and placeholders for Income, Expense, Category, Reports, and Budget pages.
- Security: use HTTPS for production and improve CSRF protections for forms.
