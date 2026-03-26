# [cite_start]MiniLedger POS-Cashier v2.0 [cite: 2, 3]

[cite_start]MiniLedger POS-Cashier is an offline-first point-of-sale system designed specifically for car spare parts shops[cite: 40]. [cite_start]Built entirely in **Native PHP** with an **MVC architecture**, it runs locally on the cashier's machine and communicates with a central **Laravel 12 Admin API** over a local network (LAN)[cite: 41]. 

[cite_start]All invoices are saved locally to SQLite first, ensuring the POS never goes down even if the network connection drops, and then synced to the server when available[cite: 42].

## 🚀 Key Features

* [cite_start]**True Offline-First Architecture:** Invoices are stored in a local SQLite database before syncing to the server[cite: 46].
* [cite_start]**Per-Cashier Database Isolation:** Every cashier gets their own `.sqlite` file named by their email prefix[cite: 46].
* [cite_start]**Idempotent Sync Engine:** Every invoice gets a UUID (v4) to prevent duplicate records during server sync[cite: 46, 124].
* [cite_start]**Automated FIFO Sync:** Pending invoices are synced oldest-first in batches[cite: 46]. [cite_start]Auto-sync runs every 30 seconds via JS polling[cite: 98].
* [cite_start]**Smart Multi-Word Search:** Search independently using AND logic across products, SKUs, and compatible car models (Brand, Model, Year, Engine)[cite: 128, 129].
* [cite_start]**Image Caching:** Product and category images are downloaded via cURL and stored locally for offline viewing[cite: 44, 46].
* [cite_start]**Data Protection:** Logout is strictly blocked when unsynced invoices exist to prevent data loss[cite: 86].

## 🛠️ Technology Stack

* [cite_start]**Cashier Client:** Native PHP (No Frameworks) + SQLite [cite: 44]
* [cite_start]**Admin API:** Laravel 12.x + MySQL [cite: 44]
* [cite_start]**Web Server:** Apache (via XAMPP) [cite: 44]
* [cite_start]**Authentication:** JSON-based session file (`session.json`) storing Bearer token and email [cite: 44]

## 📋 Requirements

* [cite_start]**PHP:** 8.1 or higher [cite: 52]
* [cite_start]**Web Server:** Apache with `mod_rewrite` enabled [cite: 52]
* [cite_start]**Extensions:** PDO SQLite and CURL must be enabled in `php.ini` [cite: 52]
* [cite_start]**Server:** A running instance of the MiniLedger Laravel 12 Admin API on the same LAN [cite: 52]

## ⚙️ Installation & Setup

1. **Clone the repository:**
   [cite_start]Place the project inside your web server directory (e.g., `C:/xampp/htdocs/MiniLedger/CASHIER/`)[cite: 54].

2. **Configure Apache:**
   [cite_start]Ensure `AllowOverride All` is set in Apache's `httpd.conf` so the `.htaccess` routing works correctly[cite: 62].

3. **Application Configuration:**
   Copy `config/app.example.php` to `config/app.php` and update the local paths and server URL:
   ```php
   'server_url' => '[http://127.0.0.1:8000/api](http://127.0.0.1:8000/api)'
   'base_url' => '/MiniLedger/CASHIER/public'