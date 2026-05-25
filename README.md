# Halal Keeps — Halal Certification Portal

**Halal Keeps**  
A full-stack web application that digitizes the complete halal certification lifecycle — from a business owner's initial application through auditing, laboratory testing, committee review, and final certificate issuance.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [User Roles](#user-roles)
- [Certification Workflow](#certification-workflow)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Security Notes](#security-notes)
- [Contributing](#contributing)

---

## Overview

Halal Keeps is a role-based PHP web application built for the Halal Certification. It manages the end-to-end halal certification process for food businesses (restaurants, manufacturers), including:

- Digital application submission and document management
- Multi-stage evaluation and inspection workflows
- Laboratory sample tracking and analysis
- Committee-based certification decisions
- Certificate issuance and a public halal restaurant directory

---

## Features

| Feature | Description |
|---|---|
| **Application Management** | Submit and track halal certification applications with real-time status updates |
| **Document Verification** | 24-point document checklist reviewed per application by evaluators |
| **Inspection Scheduling** | Evaluators assign Technical and Shariah auditors with fee calculation by enterprise size |
| **Audit & NCR Reports** | Auditors complete conformity checklists and generate Non-Conformance Reports (PDF) |
| **Laboratory Workflow** | Lab requests, sample receiving, analysis, and halal status reports |
| **Online Payments** | PayMongo integration (card, GCash, PayMaya) for laboratory fees |
| **Committee Decisions** | Two-tier review: Impartial Committee → Decision Committee |
| **Certificate Issuance** | President awards official halal certificates with logo |
| **Restaurant Directory** | Public listing of certified restaurants with ordering and reviews |
| **Security & DLP** | RBAC, account lockout, session timeout, field-level encryption, full audit trail |
| **Google OAuth** | Sign in with Google alongside traditional email/password login |
| **Admin Panel** | Separate security dashboard for managing users, roles, logs, and DLP settings |

---

## User Roles

| Role | ID | Description |
|---|---|---|
| Customer | 1 | Browse halal restaurants, place orders, write reviews |
| Business Owner | 2 | Submit LOI, manage applications, track certification progress |
| Evaluator | 3 | Verify LOIs and applications, create TOR, schedule inspections |
| Auditor – Technical | 4 | Conduct technical compliance inspections, file NCR reports |
| Auditor – Shariah | 5 | Conduct Shariah compliance inspections, file NCR reports |
| Impartial Committee | 6 | Review audit evidence, make certification recommendations |
| Decision Committee | 7 | Issue final certification decisions |
| President | 8 | Award certificates, manage users, access admin panel |
| Receiving Officer | 9 | Receive and track laboratory sample submissions |
| Lab Analyst | 10 | Analyze samples, generate lab reports with halal status |
| Admin | 11 | Full security dashboard, RBAC matrix, DLP, logs |

---

## Certification Workflow

```
Business Owner                 HID Philippines Staff
──────────────                 ─────────────────────
1. Submit Letter of Intent  →  Evaluator verifies LOI
2. Upload 24 requirements   →  Evaluator reviews each document
3. Accept Terms of Reference ← Evaluator creates TOR + assigns auditors
4. View inspection schedule ← Evaluator schedules inspection
5. Await inspection         →  Technical & Shariah auditors inspect on-site
6. Request lab test + pay   →  Receiving Officer receives samples
                               Lab Analyst analyzes + issues report
                               Impartial Committee reviews evidence
                               Decision Committee makes final decision
7. Receive certificate      ←  President awards halal certificate
```

Enterprise size determines auditor count and fees:

| Type | Technical Auditors | Shariah Auditors | Fee Each |
|---|---|---|---|
| Micro | 1 | 1 | ₱500.00 |
| Small | 1 | 1 | ₱700.00 |
| Medium | 1 | 2 | ₱900.00 |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2 |
| Database | MariaDB 10.4 (via MySQLi) |
| Web Server | Apache (XAMPP) |
| PDF Generation | [mPDF 8.3](https://mpdf.github.io/) via Composer |
| Email | [PHPMailer](https://github.com/PHPMailer/PHPMailer) |
| Payments | [PayMongo API](https://developers.paymongo.com/) |
| Authentication | bcrypt + Google OAuth 2.0 |
| Frontend | Vanilla HTML/CSS/JS, Font Awesome 6.5, Google Fonts |
| Dependency Manager | Composer |

---

## Project Structure

```
halal_final/
├── index.php                   # Public landing page
├── composer.json               # PHP dependencies
├── halal_system.sql            # Full database dump
│
├── config/
│   ├── app.php                 # Bootstrap: session, timeout, upload dirs
│   ├── database.php            # MySQLi connection
│   └── constants.php           # Role IDs, status codes, API keys, limits
│
├── includes/
│   ├── functions.php           # Global helpers (auth, routing, PayMongo, etc.)
│   ├── header.php              # Shared page header template
│   └── footer.php              # Shared page footer template
│
├── auth/
│   ├── login.php               # Email/password + Google OAuth login
│   ├── register.php            # New user registration
│   ├── logout.php              # Session destruction
│   ├── google_callback.php     # Google OAuth callback handler
│   └── apply_role.php          # Role selection after registration
│
├── dashboard/
│   ├── admin/                  # Admin role dashboard
│   ├── auditor/                # Technical & Shariah auditor dashboard
│   ├── business_owner/         # Business owner dashboard
│   ├── customer/               # Customer dashboard
│   ├── decision_committee/     # Decision committee dashboard
│   ├── evaluator/              # Evaluator dashboard
│   ├── impartial_committee/    # Impartial committee dashboard
│   ├── lab_analyst/            # Laboratory analyst dashboard
│   ├── president/              # President dashboard
│   └── receiving_officer/      # Receiving officer dashboard
│
├── admin/
│   ├── index.php               # Security dashboard (login attempts, online users)
│   ├── users.php               # User management
│   ├── roles.php               # Role management
│   ├── activity_logs.php       # Activity audit log viewer
│   ├── login_logs.php          # Login attempt log viewer
│   ├── system_logs.php         # System log viewer
│   ├── dlp_settings.php        # Data Loss Prevention settings
│   ├── data_classification.php # Data classification rules
│   ├── encrypted_fields.php    # Field-level encryption management
│   ├── api_permissions.php     # API access control
│   ├── settings.php            # System settings (fees, timeouts, etc.)
│   ├── admin_functions.php     # Admin-specific helper functions
│   ├── admin_migration.sql     # Admin module migration SQL
│   ├── api/
│   │   ├── check_permission.php  # AJAX: RBAC permission check
│   │   ├── log_dlp.php           # AJAX: DLP event logging
│   │   └── ping_session.php      # AJAX: Session keepalive
│   └── includes/
│       ├── admin_header.php    # Admin panel header template
│       └── admin_footer.php    # Admin panel footer template
│
├── api/
│   ├── notifications.php       # AJAX: Notification fetch/mark-read
│   └── upload_requirement.php  # AJAX: Application document upload
│
├── assets/
│   ├── css/style.css           # Global stylesheet
│   └── js/main.js              # Global JavaScript
│
├── uploads/                    # User-uploaded files (gitignored)
│   ├── avatars/
│   ├── documents/
│   ├── certificates/
│   ├── laboratory/
│   ├── role_applications/
│   ├── restaurant_images/
│   ├── menu_images/
│   └── receipts/
│
├── database_migrations/
│   ├── admin_interface.sql         # Admin panel schema additions
│   └── separate_auditor_findings.sql # Auditor findings schema update
│
├── PHPMailer-master/           # PHPMailer library (bundled)
├── vendor/                     # Composer packages (gitignored)
└── tmp/                        # mPDF temp files (gitignored)
```

---

## Prerequisites

- [XAMPP](https://www.apachefriends.org/) (Apache + MariaDB 10.4+, PHP 8.2+)
- [Composer](https://getcomposer.org/)
- PHP extensions: `mysqli`, `curl`, `mbstring`, `gd`, `zip` (all included in XAMPP)

---

## Installation

1. **Clone or copy** the project into your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\halal_final\
   ```

2. **Install PHP dependencies:**
   ```bash
   cd C:\xampp\htdocs\halal_final
   composer install
   ```

3. **Import the database** (see [Database Setup](#database-setup)).

4. **Configure the application** (see [Configuration](#configuration)).

5. **Start XAMPP** — ensure Apache and MySQL services are running.

6. **Open the app** in your browser:
   ```
   http://localhost/halal_final/
   ```

---

## Configuration

All configuration lives in `config/constants.php`. Update the following before running in any environment:

### Base URL
```php
define('BASE_URL', '/halal_final/');
```
Change this if the project is served from a different path.

### Google OAuth
```php
define('GOOGLE_CLIENT_ID',     'your-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'your-client-secret');
define('GOOGLE_REDIRECT_URI',  'http://localhost/halal_final/auth/google_callback.php');
```
Create credentials at [Google Cloud Console](https://console.cloud.google.com/). Add the redirect URI to your OAuth app's authorized redirect URIs.

### PayMongo
```php
define('PAYMONGO_SECRET_KEY', 'sk_test_...');
define('PAYMONGO_PUBLIC_KEY',  'pk_test_...');
```
Get keys from [PayMongo Dashboard](https://dashboard.paymongo.com/). Use `sk_live_` / `pk_live_` keys in production.

### Database (`config/database.php`)
```php
$db_host     = "127.0.0.1";
$db_username = "root";
$db_password = "";
$db_name     = "halal_system";
```

### Encryption Key
```php
define('FIELD_ENCRYPT_KEY', 'your-strong-random-key-here');
```
**Change this before deploying.** This key is used for field-level AES encryption of sensitive data.

---

## Database Setup

1. Open **phpMyAdmin** at `http://localhost/phpmyadmin/`.

2. Create a new database named `halal_system`.

3. Import the main dump:
   - Select the `halal_system` database
   - Click **Import** → choose `halal_system.sql` → click **Go**

4. Apply supplemental migrations in order:
   ```
   database_migrations/admin_interface.sql
   database_migrations/separate_auditor_findings.sql
   ```
   Also apply the admin module migration:
   ```
   admin/admin_migration.sql
   ```

5. The database will be seeded with sample data including test users and activity logs.

### Default Admin Access

After import, log in with an admin account. Check the `users` table for accounts where `is_admin = 1`. You can set a password via phpMyAdmin using:
```sql
UPDATE users SET password = '$2y$10$...' WHERE is_admin = 1;
```
Generate a bcrypt hash with PHP: `password_hash('yourpassword', PASSWORD_DEFAULT)`.

---

## Security Notes

> **These settings must be changed before any production deployment.**

- **`FIELD_ENCRYPT_KEY`** in `config/constants.php` — replace with a strong, randomly generated key
- **`GOOGLE_CLIENT_SECRET`** — never commit real credentials; use environment variables in production
- **`PAYMONGO_SECRET_KEY`** — switch to live keys and store outside the codebase
- **Database credentials** in `config/database.php` — use a non-root user with least-privilege access
- **`uploads/`** directory — ensure it is not web-accessible for non-image files, or serve files through a PHP proxy
- **Session timeout** is configurable via the admin panel under System Settings (`session_timeout_minutes`)
- **Account lockout** triggers after 5 failed login attempts (configurable via `MAX_LOGIN_ATTEMPTS` in `constants.php`)

---

## Contributing

1. Fork the repository and create a feature branch.
2. Follow the existing code style — vanilla PHP, MySQLi prepared statements, no framework.
3. Test all role workflows before submitting a pull request.
4. Do not commit secrets, uploaded files, or the `vendor/` directory.
