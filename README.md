## EthioEventHub — Quick Setup & Submission README

This README provides safe, step-by-step setup and a short checklist for submission without changing project functionality.

1) Import database
- Open your MySQL client (phpMyAdmin, MySQL Workbench, or CLI) and import `db.sql`.

2) Update configuration
- Edit `includes/config.php` and set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `SITE_URL` as needed.

3) Ensure PHP requirements
- PHP 7.4+ with PDO MySQL extension enabled.

4) Run locally for testing
```bash
cd "C:\Flash\software\EthioEventHub"
php -S localhost:8000 -t .
# open http://localhost:8000 in your browser
```

5) Non-destructive security checklist (apply on a branch)
- Use prepared statements for any SQL that concatenates variables.
- Validate & sanitize all `$_GET`/`$_POST` using `filter_input()`.
- Escape output with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` before printing user data.
- Use `password_hash()` / `password_verify()` for passwords (already used).
- Set session cookie flags in `includes/config.php` before `session_start()` (cookie_httponly, cookie_secure when HTTPS).

6) Files of interest (concatenation or dynamic strings found)
- `pages/book_event.php` (booking redirect & notification strings)
- `pages/forgot_password.php` (reset link building)
- `pages/send_ticket_email.php` (email body concatenation)
- `pages/admin_backup.php` (backup generation)
- `index.php`, `pages/events.php`, `pages/profile.php` (image path assembly)

7) How to submit
- Create a zip of the project root (include `db.sql`, `README.md`, `Project_Documentation.md`, and `docs/ERD.svg`).

8) If you want me to harden code I can open a PR on branch `harden-and-docs` and:
- convert any unsafe concatenated SQL to prepared statements
- add server-side input validation examples
- add secure session settings

-- End of README
# 🎉 EthioEvent Hub - Enterprise Edition

EthioEvent Hub is a high-performance, full-stack event management ecosystem engineered for the modern Ethiopian market. It combines **Dark Glassmorphism** aesthetics with an enterprise-grade PHP/MySQL backend to deliver a secure, scalable, and visually stunning experience.

---

## 💎 Advanced Enterprise Features

### 🛡️ Security & Resilience

- **Brute-Force Protection**: Integrated **Rate Limiting** system to protect the platform from automated attacks.
- **Audit Trail & Logging**: A comprehensive **Activity Logger** tracks every critical action (logins, bookings, system changes) for security auditing.
- **Transactional Integrity**: Uses **PDO Prepared Statements** and database transactions to ensure 100% data consistency and block SQL injection.
- **One-Click Backup**: Admin-exclusive tool to generate full **SQL Database Backups** instantly for disaster recovery.

### 📊 Business Intelligence & Fintech

- **Advanced Analytics Hub**: Real-time platform metrics powered by **Chart.js**, featuring revenue growth, market share distribution, and booking velocity.
- **Simulated Payment Gateway**: A multi-channel payment interface supporting **Telebirr**, **M-PESA**, and **Credit Cards** (featuring **Luhn Algorithm** validation).
- **Digital Ticket Engine**: Generation of high-fidelity, print-ready digital passes with **QR Codes** and **Barcodes** for on-site check-ins.
- **Automated HTML Emails**: Professional notification system for booking confirmations and welcome onboarding.

### 🔍 Discovery & Engagement

- **Advanced Search Engine**: Multi-parameter filtering (Price sliders, Date presets) and smart **Popularity-based sorting**.
- **Community Feedback System**: Advanced review module featuring **User Avatars**, **Helpful Voting**, and **Verified Purchase** badges.
- **Social Virality**: Full **Social Media Integration** with Open Graph (OG) meta-tags and one-click sharing for Facebook, Twitter, and WhatsApp.

---

## 🛠️ Technology Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | PHP 8.x (Modern OOP & Native) |
| **Database** | MariaDB / MySQL (Optimized Relational Schema) |
| **Security** | SHA-256 Hashing, Rate Limiting, JSON-based Audit Logs |
| **Frontend** | CSS3 (Glassmorphism), Bootstrap 5.3, FontAwesome 6.0 |
| **Real-time** | AJAX Fetch API & Dynamic Polling Hub |

---

## 📂 Installation & Deployment

1.  **Host**: Deploy the project to `xampp/htdocs/`.
2.  **Database Setup**:
    - Create a database: `ethioeventhub`.
    - Import `db.sql` via phpMyAdmin.
3.  **Configure**: Update `includes/config.php` with your local DB credentials.
4.  **Access**: Navigate to `http://localhost/Wep_Programming_Pro/EthioEventHub/`

---

## 👥 Development Team

*Developed for the Web Programming Course Submission - 2026*

- **Mesfin Alemayehu**
- **Biruktawit Geresu**
- **Yonas Tadese**
- **Edget Adissu**
- **Ebsitu**

---

© 2026 EthioEvent Hub. Proudly built for the Ethiopian event ecosystem.
