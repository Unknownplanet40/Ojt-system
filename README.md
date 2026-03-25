# 🚀 OJT System (Rebuild)

This project is a **rebuild of my old OJT Coordinator System project**, modernized with a cleaner structure, pre-configured libraries, and improved setup flow for easier maintenance and future development.

> ℹ️ The repository originally started from a template-style base, but it now serves as the active codebase for the rebuilt OJT system.

---

## ✨ Features

- **Server Status Checker** — `index.html` automatically checks if Apache, MySQL, and your database connection are live before loading the app
- **Pre-bundled Libraries** — all dependencies are included locally in `/libs`, no package manager required
- **Clean Directory Structure** — organized folders for assets, source pages, and libraries
- **Apache Ready** — includes a pre-configured `.htaccess` for URL rewriting and access control

---

### BREAKING CHANGE: initial system setup with auth, user management, and academic modules

### Database & Schema

- Configured for MariaDB using `utf8mb4` connection settings via `Assets/database/dbconfig.php`
- Uses a dual-ID approach in the app layer: numeric internal IDs + UUIDs for public references (UUID generation is handled in PHP)
- Core role model supports `admin`, `coordinator`, `student`, and `supervisor`
- Role profile flow is implemented with dedicated profile pages:
   - `Src/Pages/Admin/Admin_Profile.php`
   - `Src/Pages/Coordinator/Coordinator_Profile.php`
   - `Src/Pages/Students/Students_Profile.php`
   - `Src/Pages/Supervisor/Supervisor_Profile.php`
- Audit/auth tracking integrations are present in API layer:
   - `Assets/api/logs.php` (`auditLog()`, `loginAudit()`)
   - `Assets/api/admin_dashboard_queries.php` (activity + alerts feed)
- Batch management is implemented with status transitions and actor tracking in:
   - `Assets/api/batch_functions.php`
- Development/testing data assumptions are supported by dashboard/batch queries, while seed SQL files are maintained outside this repository

### Authentication

- Login UI and no-registration messaging are implemented in `Src/Pages/Login.php`
- Post-login routing chain is implemented in `Assets/api/loginProcess.php`:
   - `must_change_password` → password change screen
   - missing profile → role profile page
   - complete profile → role dashboard
- Forgot password request and reset UX states are implemented in `Src/Pages/ForgotPassword.php`
- Change password supports forced + voluntary modes in:
   - `Src/Pages/ChangePassword.php`
   - `Assets/api/ChangePasswordProcess.php`
- Password hashing uses `password_hash()` (bcrypt-compatible default in PHP)

### User Interface

- Login page includes no-registration notice and first-login guidance (`Src/Pages/Login.php`)
- Profile setup forms exist for all 4 roles (see profile pages above)
- Admin dashboard with stat cards/activity/alerts is implemented in:
   - `Src/Pages/Admin/AdminDashboard.php`
   - `Assets/api/admin_dashboard_queries.php`
- Admin navigation and shared layout are handled through reusable components:
   - `Src/Components/Header.php`
   - `Src/Components/lvl1cards.php`
   - `Src/Components/lvl2cards.php`
   - `Src/Components/lvl3cards.php`
- Forgot password screens include request, sent, reset, expired, and success states (`Src/Pages/ForgotPassword.php`)
- Change password screens include forced and voluntary variants with strength checks (`Src/Pages/ChangePassword.php`)
- Batches module UI is implemented in `Src/Pages/Admin/Batches.php` with:
   - Active batch highlighting and status pills
   - Create/edit batch forms with school-year validation hints and activate toggle
   - Activate confirmation modal (with active-batch close warning)
   - Close confirmation modal (with `CLOSE` typed safety check)

### Backend Functions (MySQLi)

- Dashboard API (`Assets/api/admin_dashboard_queries.php`):
   - `getDashboardData()`
   - `getStatCards()`
   - `getUsersByRole()`
   - `getRecentAccounts()`
   - `getRecentActivity()`
   - `getNeedsAttention()`
   - `timeAgo()`
- Batch API (`Assets/api/batch_functions.php`):
   - `createBatch()`, `updateBatch()`, `activateBatch()`, `closeBatch()`
   - `getAllBatches()`, `getActiveBatch()`
   - `generateUuid()` for UUID generation in MariaDB-compatible flow
- Logging helpers (`Assets/api/logs.php`):
   - `auditLog()`
   - `loginAudit()`

### Current Notes

- UUIDs are generated in PHP before INSERT operations where needed (`generateUuid()` in `batch_functions.php`)
- Single active batch behavior is enforced at application layer through `activateBatch()`
- Program management functions/pages (`createProgram`, `editProgram`, `toggleProgram`, `getAllPrograms`) are **not yet present** in this repository state
- Some company/MOA-related alert logic is still placeholder-level in `getNeedsAttention()` until those modules/tables are added

---

## 🧰 Tech Stack

| Technology | Purpose |
|---|---|
| **PHP** | Server-side scripting & database connectivity |
| **HTML5** | Page structure & markup |
| **JavaScript** | Client-side logic |
| **jQuery** | DOM manipulation & AJAX |
| **Bootstrap** | Responsive UI components & grid layout |
| **Anime.js** | Smooth JavaScript animations |
| **AOS** (Animate On Scroll) | Scroll-triggered animations |
| **Driver.js** | Interactive user onboarding / guided tours |
| **SweetAlert2** | Beautiful, customizable alert dialogs |

---

## 📁 Directory Structure

```
Ojt-system/
├── Assets/
│   └── database/
│       └── dbconfig.php    # Database connection configuration
├── Src/
│   └── Pages/          # PHP/HTML page files
├── libs/               # All bundled front-end libraries (offline-ready)
│   └── composer/       # Optional: PHPMailer & Ratchet (via Composer)
├── index.html          # Entry point with server status checking
├── .htaccess           # Apache configuration (URL rewriting, access rules)
├── InstallDependencies.md  # Guide for setting up server dependencies
└── LICENSE             # MIT License
```

---

## ⚙️ Requirements

- **Apache** web server (with `mod_rewrite` enabled)
- **MySQL** / MariaDB
- **PHP** 7.4 or higher
- A local dev environment such as [XAMPP](https://www.apachefriends.org/), [WAMP](https://www.wampserver.com/), [LAMP](https://ubuntu.com/server/docs/lamp-applications), or [Laragon](https://laragon.org/)

---

## 🔁 Enabling mod_rewrite

`mod_rewrite` is required for the `.htaccess` URL rewriting rules to work. Here's how to enable it on common setups:

### XAMPP (Windows)
1. Open `C:/xampp/apache/conf/httpd.conf`
2. Find and uncomment this line (remove the `#`):
   ```
   #LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Find `AllowOverride None` under your `<Directory>` block and change it to:
   ```
   AllowOverride All
   ```
4. Save the file and restart Apache from the XAMPP Control Panel

### WAMP (Windows)
1. Left-click the WAMP tray icon → **Apache** → **Apache Modules**
2. Find and click **rewrite_module** to enable it (a checkmark will appear)
3. WAMP will restart Apache automatically

### Linux / LAMP (Ubuntu)
Run the following commands in your terminal:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```
Then make sure your site config (usually `/etc/apache2/sites-available/000-default.conf`) has:
```
AllowOverride All
```

### Laragon (Windows)
`mod_rewrite` is enabled by default in Laragon — no extra steps needed.

---

## 🛠️ Getting Started

### 1. Clone the Repository

```bash
git clone <your-repository-url>
cd Ojt-system
```

### 2. Place in Your Web Server Root

Copy the project folder into your server's document root:

- **XAMPP:** `C:/xampp/htdocs/`
- **WAMP:** `C:/wamp64/www/`
- **Linux/LAMP:** `/var/www/html/`

### 3. Configure Your Database

Update the database connection settings in:

```
Assets/database/dbconfig.php
```

Set your database host, name, username, and password here before running the project.

### 4. Start Your Server

Make sure **Apache** and **MySQL** are running, then navigate to:

```
http://localhost/Ojt-system/
```

### 5. How It Works — Server Check & Redirect

<div align="center">
  <img src="https://assets.grok.com/users/2e437696-c849-40e3-a1cc-7c15721185f1/generated/fefb7b66-fe31-4146-acaa-059c59b9a510/image.jpg" width="256" />
</div>

When a user visits the site, `index.html` acts as a **pre-flight gate** before anything else loads:

- **All checks pass** → the user is automatically redirected to your main page or web app
- **Any check fails** → the page displays a clear status indicator showing which service is down, preventing the app from loading in a broken state

This ensures users and developers always know the server environment is healthy before the application runs.

---

## 🔧 Optional Add-ons

These are not required to run the core system but are available if your project needs them. Both are set up via Composer and their packages are located in `libs/composer/`.

### 📧 PHPMailer — Email Support

Adds the ability to send emails from your PHP application (contact forms, notifications, password resets, etc.).

> 📖 See [`InstallDependencies.md`](InstallDependencies.md) for full installation instructions.

**Folder:** `libs/composer/`

---

### 🔌 Ratchet — WebSocket Support

Adds real-time, two-way communication between the server and clients using WebSockets. Great for live chat, notifications, or any feature that needs a persistent connection.

> 📖 See [`InstallDependencies.md`](InstallDependencies.md) for full installation instructions.

**Folder:** `libs/composer/`

---

## 📦 Included Libraries (in `/libs`)

All libraries are bundled locally — no CDN or internet connection required:

- [Bootstrap](https://getbootstrap.com/)
- [jQuery](https://jquery.com/)
- [Anime.js](https://animejs.com/)
- [AOS – Animate On Scroll](https://michalsnik.github.io/aos/)
- [Driver.js](https://driverjs.com/)
- [SweetAlert2](https://sweetalert2.github.io/)
- [Quill 2.0](https://quilljs.com/)

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

## 🤝 Contributing

Pull requests are welcome! If you'd like to suggest improvements or add features, feel free to fork the repo and open a PR.

---

## 🔗 Live Demo

_To be updated for the rebuilt OJT System._
