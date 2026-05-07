# 🚀 Installation & Setup Guide

Follow these steps to get EthioEvent Hub running on your local machine.

## 1. Prerequisites
- **XAMPP** (PHP 8.x and MySQL/MariaDB)
- A modern web browser (Chrome, Firefox, Edge)

## 2. Setting Up the Files
1. Copy the `EthioEventHub` folder into your `C:\xampp\htdocs\Wep_Programming_Pro\` directory.
2. Ensure the file structure looks like this:
   `C:\xampp\htdocs\Wep_Programming_Pro\EthioEventHub\index.php`

## 3. Database Configuration
1. Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Open [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
3. Create a new database named **`ethioeventhub`**.
4. Click on the **Import** tab.
5. Choose the **`db.sql`** file from the project root and click **Go**.

## 4. Environment Configuration
Open `includes/config.php` and verify the settings:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ethioeventhub');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change if you have a MySQL password
```

## 5. Running the App
Navigate to:
👉 **[http://localhost/Wep_Programming_Pro/EthioEventHub/](http://localhost/Wep_Programming_Pro/EthioEventHub/)**

---
*© 2026 EthioEvent Hub Team*
