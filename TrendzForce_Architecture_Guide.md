# TrendzForce Platform Architecture & API Guide

This document outlines the complete organizational structure of the TrendzForce platform. It provides clear definitions for what each HTML interface and backend API script functionally handles across the software ecosystem.

---

## 💻 The Frontend HTML Interfaces (What users actually see)

### 1. `index.html` (The Front Door)
Main login page and homepage. This file hosts the beautiful  carousel, handles desktop/mobile viewing with a background video, and directs users exactly where they belong based on their role credentials upon signing in.

### 2. `register.html` (The Welcome Mat)
The sign-up page where brand new users fill out their profile, select their group (e.g., "ROR PROGRAMS TRENDFORCE B"), and establish their KingsChat handles and secure passwords.

### 3. `member.html` (The Personal Workspace)
The base-tier dashboard. This is where standard users go to submit their daily KingsChat tracking metrics into the database and view their own personal performance history over time.

### 4. `leader.html` (The Captain’s Deck)
The middle-tier dashboard. This gives Group Leaders a global view of *only their specific group*. They can see leaderboards for their own members and approve/reject their group's reports here.

### 5. `admin.html` (The Global Command Center)
The highest-tier dashboard. Depending on the `admin_level` you assign a user in the database, this page allows your executives to view the entire organization's statistics globally, export massive CSV databases, and permanently block or delete users.

### 6. `forgot-password.html` 
An interface instructing users on what to do or allowing them to request a reset if they lock themselves out of their account.

---

## ⚙️ The Primary Backend APIs (The engine gears)

### 7. `api/config.php` (The Core Engine)
This is the heart of the backend. It handles the raw connection to  MySQL database and contains the vital `require_auth()` security function. Almost every other script calls this file first to make sure hackers cannot bypass the security protocols.

### 8. `api/login.php` (The Doorman)
Receives a user’s KingsChat handle and password, securely verifies it against the hashed password in your database, and creates a secure session. It securely passes their `role` and `admin_level` to the frontend to determine which dashboard they are allowed to see.

### 9. `api/register.php` (The Onboarding Script)
Handles the creation of brand new accounts. It securely encrypts their password, assigns them to their specific dropdown group, and registers them into the database, defaulting them to the lowest level (`member`).

### 10. `api/submit_report.php` (The Collector)
Allows Members and Group Leaders to officially upload their daily analytics (KingsChat views, likes, shares, external app reach) directly into the `reports` database table.

### 11. `api/admin_reports.php` (The Aggregator)
A high-level data script that scours the entire database to pull every single submitted report globally. It feeds the raw data into the Admin dashboard so that higher-ups can visualize performance and export CSV spreadsheets.

### 12. `api/admin_verify.php` (The Admin Stamper)
Allows Level 1 and Level 2 Admins to permanently mark a report as **"Verified"** or **"Rejected"**. Once an Admin stamps a report through this API, the original user cannot legitimately fake or edit those analytics anymore.

### 13. `api/members.php` (The Directory)
Runs a query to pull the complete list of registered users on the entire platform. It feeds this list primarily into the Admin dashboard's "All Members" tab so leadership can see exactly who is participating.

### 14. `api/manage_user.php` (The Executioner)
Built exclusively for **Level 1 Super Admins**. This highly-secured script bypasses normal restrictions and interfaces directly with the database to instantly and permanently **Delete** or **Block** rogue user accounts from the platform.

---

## 🔧 Supplementary APIs & Resources

### 15. `api/get_reports.php` (The Personal Archive)
Unlike the admin version, this specific API is restricted so that an individual member can only request and download their *own* specific past submissions directly to their `member.html` dashboard.

### 16. `api/verify.php` (The Captain's Stamper)
This works identically to the Admin's verification, but it is restricted specifically for **Leaders**. It allows leaders to mark the reports belonging ONLY to their own group members as verified/rejected.

### 17. `api/stats.php` (The Calculator)
This script crunches all the live statistics (like total global reach, view counts, and engagement scores) and formats the numbers mathematically so they can be displayed beautifully on the dashboard screens in real-time.

### 18. `api/update_profile.php` (The Settings Configurator)
Allows an existing user to update their active profile. If someone needs to add a new WhatsApp handle or change an external app link, this API updates their database row securely.

### 19. `api/reset_password.php` (The Keymaker)
The backend logic that safely overrides an old encrypted password with a new one when a user triggers a password recovery.

### 20. `api/logout.php` (The Shredder)
A tiny but critical security script. When a user clicks "Logout", this API permanently shreds their active session token from the server, ensuring nobody else can use their browser to access their dashboard.

### 21. `logo_png.png` / `bg-image.jpg` / `bg-video.mp4`
All of the main visual assets that brand the `index.html` login page and dashboards.

### 22. `BACKEND_PHP.php` (The Blueprint)
The architectural blueprint file! It doesn't actually run anything live on the server, but it contains all the SQL documentation (like the exact Database table layouts) as a safekeeping map.
