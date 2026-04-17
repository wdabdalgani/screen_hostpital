# 🏥 Hospital Doctor Display System
### نظام عرض الأطباء للمستشفيات

A full-featured PHP/MySQL web application that lets hospitals manage and display **real-time doctor availability** on digital screens (TVs, kiosks, lobby displays).

---

## ✨ Features

### 🖥️ Display Engine
- Unique token-based URLs for each screen (`display.php?token=...`)
- Auto-refreshing slides with configurable intervals
- Cinematic transitions between doctor cards
- **Two built-in themes**: Classic Medical & Hariri Template
- **PWA support** — works offline via Service Worker
- Kiosk-ready (Chrome/Edge `--kiosk` flag compatible)

### 👨‍⚕️ Doctor Management
- Add/edit doctors with photo, specialty, department, and work hours
- **Auto mode**: availability computed from weekly schedule & work hours
- **Manual mode**: force available / unavailable status
- Weekly schedule per doctor (day-by-day)
- Search & filter by screen, department, or status

### 📺 Screen Management
- Create unlimited display screens, each with a unique secure token
- Configure slide duration and auto-refresh interval per screen
- Two display modes: **Doctors** or **Media Content**
- Assign custom display styles per screen

### 🎨 Display Styles
- Multiple built-in styles: `Hero Medical`, `Card Social`, `Hariri Template`, `Minimal Clear`
- Custom styles with full JSON config + custom CSS
- Per-style typography, color palette, overlay, and animation settings

### 🗂️ Content Management
- Upload images, videos (MP4/WebM), and GIFs
- Organize content into groups and assign to screens
- Link content items to specific doctors or departments
- Control display duration per item

### 🏨 Hospital Settings
- Hospital name, logo, contact info
- Social media links (Facebook, Instagram, X, YouTube)

### 📊 Dashboard & Analytics
- **13 interactive charts** powered by Chart.js:
  1. Availability distribution (Doughnut)
  2. Doctors by department (Bar)
  3. Screen status breakdown
  4. Display styles usage
  5. Active content types
  6. Doctors per screen
  7. Doctor status mode (auto vs manual)
  8. Content groups active items
  9. Availability trend (last 48 hours)
  10. Availability heatmap (day × hour)
  11. Data quality funnel
  12. Pareto — unavailability by department
  13. KPI vs SLA target (Radar)
- Real-time stat cards with animated slider
- System health: upload folder checks, schema version, data completeness

### 📣 Welcome Broadcast
- Configurable welcome overlay shown on display screens
- Supports logo, title, subtitle, and background image

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ (strict types, PDO) |
| Database | MySQL / MariaDB (utf8mb4) |
| Frontend | Vanilla JS, CSS3 (no frameworks) |
| Charts | Chart.js 4.x |
| PWA | Service Worker + Web App Manifest |
| Server | Apache / XAMPP |

---

## 📁 Project Structure

```
/
├── admin/              # Admin panel pages
│   ├── login.php
│   ├── dashboard.php
│   ├── doctors.php
│   ├── screens.php
│   ├── departments.php
│   ├── hospital.php
│   ├── display_styles.php
│   └── display_content.php
├── api/
│   └── display.php     # JSON API consumed by display screens
├── assets/
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript modules
├── config/
│   ├── config.php      # App configuration
│   └── database.php    # PDO connection
├── includes/           # Shared PHP helpers & partials
├── sql/
│   ├── schema.sql      # Full DB schema
│   └── migration_v*.sql
├── uploads/            # User-uploaded media (auto-created)
├── display.php         # Public display screen (token-protected)
├── display-sw.js       # Service Worker for PWA/offline
├── display_manifest.php
└── install.php         # One-time setup wizard
```

---

## 🚀 Installation

### Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` (XAMPP recommended for local)

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git
cd YOUR_REPO
```

**2. Configure the database**

Edit `config/config.php`:
```php
'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'your_database_name',
    'user' => 'your_db_user',
    'pass' => 'your_db_password',
],
'base_url' => '',   // e.g. '/myapp' if in a subfolder
```

**3. Run the installer**

Open in your browser:
```
http://localhost/YOUR_FOLDER/install.php
```
Enter your admin username and password — the installer creates all tables automatically.

> ⚠️ **Delete or protect `install.php` after setup.**

**4. Log in**
```
http://localhost/YOUR_FOLDER/admin/login.php
```

---

## 📺 Using Display Screens

1. Go to **Admin → Screens** and create a new screen
2. Copy the generated token link
3. Open the link on any TV/monitor browser
4. For kiosk mode on Windows:
   ```
   chrome.exe --kiosk "https://yourdomain.com/display.php?token=TOKEN"
   ```
5. For offline/PWA mode: open the link with `?offline_sync=1` once to cache assets

---

## 🗄️ Database Migrations

The system uses versioned migrations (`sql/migration_v*.sql`). After updating from an older version, run any new migration files manually in your MySQL client, or let the app auto-detect and apply them.

Current schema version: **v16**

---

## 🔒 Security Notes

- Admin routes are protected via session-based authentication (`includes/auth.php`)
- Display screens are token-protected (32-char hex token)
- All user inputs are escaped with `htmlspecialchars` before output
- File uploads are validated by MIME type and size
- Passwords are hashed with `password_hash()` (bcrypt)

---

## 📸 Screenshots

> Add screenshots here showing the admin dashboard, display screen, and doctor cards.

---

## 📄 License

MIT License — free to use, modify, and distribute.

---

## 🤝 Contributing

Pull requests are welcome. For major changes, please open an issue first.
