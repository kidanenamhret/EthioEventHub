# 🛠️ Technical Documentation - Premium Edition

## 1. System Architecture
EthioEvent Hub is built on the **LAMP Stack** (Linux/Windows, Apache, MySQL, PHP). The architecture follows a modular procedural pattern with a dedicated API layer for dynamic features.

### Key Components:
- **Frontend**: Vanilla CSS with **Glassmorphism**, Bootstrap 5.3, and **Chart.js** for interactive data visualization.
- **Backend**: Native PHP 8.2 with **PDO** for secure, high-performance database interaction.
- **Dynamic Engine**: Asynchronous JSON endpoints powered by the **Fetch API** for real-time interactivity.
- **Security**: Multi-layer defense strategy including sanitization, validation, and session-based tokens.

## 2. Security Implementations
- **File Upload Hardening**: Implemented strict extension whitelisting (`jpg`, `jpeg`, `png`, `webp`) for event posters to block PHP script injection.
- **SQL Injection Prevention**: 100% usage of **PDO Prepared Statements** for all database transactions.
- **XSS Protection**: All user-generated content is sanitized using `htmlspecialchars()` before rendering.
- **CSRF Protection**: All sensitive POST operations utilize unique, session-bound tokens to prevent cross-site request forgery.
- **Password Security**: Passwords are hashed using the industry-standard **BCRYPT** algorithm.
- **Ownership Verification**: Strict checks on event deletion, editing, and ticket access to prevent horizontal privilege escalation.

## 3. Dynamic & AJAX Features
- **Live Notification Polling**: A background JavaScript engine that polls the server every 30 seconds for unread alerts.
- **Zero-Refresh Filtering**: Category switching on the home page is handled via AJAX, shuffling events without a page reload.
- **Animated Counters**: Scroll-triggered intersection observers that animate statistics from zero to target values.

## 4. API & Utility Endpoints
- `api/get_featured_events.php`: Serves filtered event data for the home page shuffler.
- `api/get_notifications.php`: Real-time endpoint for the navbar alert system.
- `api/submit_review.php`: Secure endpoint for the star-rating system with verified-booking checks.
- `pages/export_attendees.php`: Business utility for generating dynamic **CSV reports** for organizers.
- `api/live_search.php`: Powers the global instant-search interface.

## 5. Database Schema
- **Referential Integrity**: Managed through InnoDB foreign keys with `ON DELETE CASCADE` where appropriate.
- **Normalization**: Achieved 3rd Normal Form (3NF) for optimal data storage and reduced redundancy.
- **Re-import Stability**: The schema script (`db.sql`) includes `FOREIGN_KEY_CHECKS` toggles to allow safe re-initialization without constraint errors.

---
*© 2026 EthioEvent Hub Development Team*
