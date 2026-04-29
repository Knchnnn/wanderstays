# WanderStays – Tourism Booking Platform

A full-stack tourism booking platform built with **PHP**, **MySQL**, **HTML5**, **CSS3** and **vanilla JavaScript**.

---

## ⚡ Quick Setup (5 minutes)

### Prerequisites
- XAMPP / WAMP / LAMP with **PHP 8.0+** and **MySQL 5.7+**

### Step 1 — Copy files
Place the `wanderstays/` folder in your web root:
- **XAMPP Windows:** `C:\xampp\htdocs\wanderstays\`
- **WAMP Windows:** `C:\wamp64\www\wanderstays\`
- **Linux/Mac:** `/var/www/html/wanderstays/`

### Step 2 — Import the database
**Option A — phpMyAdmin (recommended):**
1. Open `http://localhost/phpmyadmin`
2. Click the **Import** tab (from the home screen, not inside a database)
3. Click **Choose File** → select `wanderstays/database.sql`
4. Scroll down → click **Go**
5. You'll see ✓ "Import has been successfully finished"
6. The `wanderstays` database with 7 tables now exists

**Option B — Command line:**
```bash
# Windows (XAMPP)
cd C:\xampp\mysql\bin
mysql.exe -u root -p < C:\xampp\htdocs\wanderstays\database.sql

# Linux / Mac
mysql -u root -p < /var/www/html/wanderstays/database.sql
```

### Step 3 — Configure credentials
Edit `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // your MySQL username
define('DB_PASS', '');          // your MySQL password (blank for XAMPP default)
define('DB_NAME', 'wanderstays');
```

### Step 4 — Permissions (Linux/Mac only)
```bash
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

### Step 5 — Open in browser
```
http://localhost/wanderstays/
```

---

## 📁 Project Structure

```
wanderstays/
├── index.php                  ← Main router (all pages go through here)
├── database.sql               ← Full MySQL schema — import this first
├── 404.html                   ← Custom error page
├── .htaccess                  ← Apache config + 404 redirect
├── README.md
│
├── auth/
│   ├── login.php              ← Login form handler
│   ├── register.php           ← Registration handler
│   └── logout.php             ← Session destroy + redirect
│
├── includes/
│   ├── db.php                 ← ⚙ Database credentials (edit this)
│   ├── auth.php               ← Session helpers (isLoggedIn, requireRole)
│   ├── functions.php          ← uploadPhotos(), sanitize(), formatPrice()
│   ├── header.php             ← HTML <head> + sticky navbar
│   └── footer.php             ← Footer + JS includes
│
├── pages/
│   ├── auth.php               ← Login / Register page (pure HTML form)
│   ├── explore.php            ← Traveller home: filter + sort destinations
│   ├── place.php              ← Destination detail + property list
│   ├── property.php           ← Property detail + booking form + reviews
│   ├── dashboard.php          ← Traveller trip history + status tracking
│   ├── payment.php            ← Card / UPI payment page
│   ├── owner-home.php         ← Owner dashboard: stats, listings, requests
│   ├── add-place.php          ← Owner: add new destination with photos
│   └── add-property.php       ← Owner: list property with photos + amenities
│
├── static/                    ← Pure HTML pages (no PHP required)
│   ├── landing.html           ← Public landing / marketing page
│   ├── about.html             ← About WanderStays
│   ├── how-it-works.html      ← Step-by-step guide for travellers & owners
│   ├── contact.html           ← Contact form (JS-only, no PHP)
│   ├── terms.html             ← Terms, Privacy, Cancellation, Cookies
│   └── setup-guide.html       ← Detailed developer setup documentation
│
├── css/
│   └── style.css              ← All styles (dark luxury theme, responsive)
│
├── js/
│   └── main.js                ← Gallery, price calculator, upload preview, tabs
│
└── uploads/
    ├── places/                ← Destination photos (auto-created on upload)
    └── properties/            ← Property photos (auto-created on upload)
```

---

## 🗄 Database Tables

| Table | Description |
|---|---|
| `users` | Accounts for both travellers and owners |
| `places` | Destinations added by owners |
| `place_photos` | Photos for each destination (multiple) |
| `properties` | Listings linked to destinations |
| `property_photos` | Photos for each property (multiple) |
| `booking_requests` | All booking requests with status, dates, prices |
| `reviews` | Verified guest reviews (only after paid stay) |

---

## 🌐 Static HTML Pages (no PHP needed)

These pages work without a server — just open in a browser:

| File | Purpose |
|---|---|
| `static/landing.html` | Marketing homepage with animated counters |
| `static/about.html` | Company story, values, team |
| `static/how-it-works.html` | Interactive guide with role tabs + FAQ accordion |
| `static/contact.html` | Contact form with full JS validation |
| `static/terms.html` | Terms, Privacy, Cancellation, Cookie policies (tabbed) |
| `static/setup-guide.html` | Developer setup guide with OS tabs + troubleshooting |
| `404.html` | Custom 404 error page |

---

## ✨ Features

### Traveller
- Register / Login with validated form
- Browse destinations — filter by state, type, sort by price
- Photo gallery with dot navigation and thumbnail strip
- Book with date picker, guest count, price bargaining
- Request lifecycle: Pending → Accepted / Rejected → Paid
- Revise offer once after rejection
- Pay via Card (with auto-formatting) or UPI
- Write verified reviews after paid stays
- My Trips dashboard with full history and stats

### Owner
- Register / Login as owner
- Add destinations with drag-and-drop multi-photo upload
- List properties with photos, amenities, price, max guests
- Accept / Reject requests with optional acceptance deadline
- Revenue tracker (paid bookings only)
- Property cards show average rating and latest review quote
- Sort own listings by price

### Technical
- Passwords hashed with `bcrypt` via `password_hash()`
- Parameterised SQL queries (no injection risk)
- Uploaded images stored with randomised filenames
- Flash messages passed via URL parameters
- Responsive layout — works on mobile and desktop

---

## 🔧 Troubleshooting

| Problem | Solution |
|---|---|
| "Database connection failed" | Check `includes/db.php` credentials. Ensure MySQL is running. |
| Photos not uploading | Check `uploads/` folder exists and is writable. Set PHP `upload_max_filesize=8M`. |
| Raw PHP code showing | Access via `http://localhost/wanderstays/` not `file:///...` |
| "Table doesn't exist" | Re-run Step 2 — database.sql was not fully imported. |
| CSS not loading | Folder must be named exactly `wanderstays` (lowercase). |

Full setup guide: `static/setup-guide.html`

---

## 📋 Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled
- `fileinfo` PHP extension (enabled by default in XAMPP/WAMP)
