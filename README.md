# 🚀 Project Template

A lightweight starter template designed to accelerate web development with pre-configured libraries, a clean directory structure, and essential boilerplate — so you can skip setup and start building.

---

## ✨ Features

- **Server Status Checker** — `index.html` automatically checks if Apache, MySQL, and your database connection are live before loading the app
- **Pre-bundled Libraries** — all dependencies are included locally in `/libs`, no package manager required
- **Clean Directory Structure** — organized folders for assets, source pages, and libraries
- **Apache Ready** — includes a pre-configured `.htaccess` for URL rewriting and access control

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
Project-Template/
├── Assets/
│   └── database/
│       └── dbconfig    # Database connection configuration
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
git clone https://github.com/Unknownplanet40/Project-Template.git
cd Project-Template
```

### 2. Place in Your Web Server Root

Copy the project folder into your server's document root:

- **XAMPP:** `C:/xampp/htdocs/`
- **WAMP:** `C:/wamp64/www/`
- **Linux/LAMP:** `/var/www/html/`

### 3. Configure Your Database

Update the database connection settings in:

```
Assets/database/dbconfig
```

Set your database host, name, username, and password here before running the project.

### 4. Start Your Server

Make sure **Apache** and **MySQL** are running, then navigate to:

```
http://localhost/Project-Template/
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

These are not required to run the template but are available if your project needs them. Both are set up via Composer and their packages are located in `libs/composer/`.

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

[unknownplanet40.github.io/Project-Template](https://unknownplanet40.github.io/Project-Template/)
