# 🎭 EthioEvent Hub - Technical Project Documentation

## 1. Executive Summary
**EthioEvent Hub** is a centralized web-based platform designed to revolutionize the way events are discovered and managed in Ethiopia. The platform bridges the gap between event organizers (musical, cultural, professional) and attendees by providing a secure, real-time, and aesthetically premium environment for ticket booking and event promotion.

---

## 2. Technical Architecture
The application follows a modular, monolithic architecture built on the **LAMP** stack (Windows, Apache, MySQL, PHP).


### 📁 Directory Structure


- `api/`: Asynchronous JSON endpoints for live search.
- `assets/`: Global styling (CSS), interactive logic (JS), and user-uploaded media.
- `includes/`: Core configuration, database connection, and utility functions.
- `pages/`: Individual application routes and role-based views.
- `logs/`: Simulation sandbox for email logs.

---

## 3. Database Design & Schema
The system uses a relational database model with **5 interconnected tables**, ensuring data integrity through foreign key constraints.

| Table | Purpose | Key Relationships |
| :--- | :--- | :--- |
| **Users** | Stores identity and role data. | Primary actor for events/bookings. |
| **Categories** | Organizes events (Music, Tech, etc.). | Linked to Events. |
| **Events** | Core event data (Venue, Date, Poster). | Linked to Categories and Organizers. |
| **Bookings** | Records ticket reservations. | Many-to-Many link between Users & Events. |
| **Reviews** | Stores community feedback & ratings. | Linked to Users and Events. |

---

## 4. Key Technical Implementations

### 🔍 Real-Time Asynchronous Search
The platform utilizes the **Fetch API** to provide an instant search experience. As users type in the search bar, a request is sent to `api/live_search.php`, which returns a JSON payload. The UI is then dynamically updated using DOM manipulation without a page reload.

### 📊 Data Visualization & Analytics
The Organizer Dashboard integrates **Chart.js** to visualize event performance. It dynamically calculates booking counts per event and renders them as high-quality bar charts, providing immediate business intelligence.

### 🛡️ Security Implementation
- **Prepared Statements (PDO)**: All database interactions use prepared statements to eliminate the risk of SQL Injection.
- **Password Security**: Passwords are never stored in plain text; the system uses `password_hash()` with the BCRYPT algorithm.
- **Session Protection**: Role-based access control (RBAC) ensures users can only access pages relevant to their specific role (Attendee/Organizer/Admin).
- **Email Sandbox**: A simulated mail environment was built to log password reset tokens and notifications to a local log file, avoiding SMTP dependencies during development.

---

## 5. Modern Design System
The UI is built on a custom **"Unified Glassmorphism"** design system:
- **Floating Navigation**: A modern, blur-effect "island" navbar that adapts as the user scrolls.
- **Glassmorphism Layers**: Semi-transparent, frosted glass containers that balance background aesthetics with content readability.
- **Responsive Grid**: Built on Bootstrap 5.3, ensuring a seamless experience across mobile, tablet, and ultra-wide displays.

---

## 6. Functional Guide


### For Attendees


1. **Discover**: Browse the homepage for featured events or use the live search.
2. **Book**: Secure a ticket with a single click after logging in.
3. **Manage**: View all upcoming reservations in the "My Bookings" tab.
4. **Feedback**: Leave star ratings and comments on attended events.


### For Organizers


1. **Promote**: Create events with custom titles, categories, and poster uploads.
2. **Analyze**: Use the dashboard charts to see which events are trending.
3. **Export**: Download CSV reports of event data for offline management.
4. **Edit**: Update event details or venue information in real-time.

---


## 7. Group Members & Contributions

- **Mesfin Alemayehu**: Database Design & Technical Documentation.
- **Biruktawit Geresu**: Event CRUD & Organizer Dashboard.
- **Yonas Tadese**: Frontend UI & Glassmorphism Design System.
- **Edget Adissu**: Booking Logic & CSV Export Integration.
- **Ebsitu**: AJAX/Fetch Implementation & Reviews System.

---
*© 2026 EthioEvent Hub Project Team - All Rights Reserved.*
