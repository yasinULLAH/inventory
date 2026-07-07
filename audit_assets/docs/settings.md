# Settings Module

## Purpose
Central application configuration controlling company info, tax, invoice display, session timeouts, password management, database backup/restore, logo upload, and landing page content management.

## Form Fields & Controls
- **COMPANY NAME**: [text] - Business name displayed on invoices and dashboard.
- **BRANCH NAME**: [text] - Branch/location name.
- **CURRENCY SYMBOL**: [text] - Currency symbol (e.g., Rs.).
- **TAX RATE (%)**: [number] - Tax rate as decimal (e.g., 0.1 for 10%).
- **TAX CALCULATED ON**: [select] - Purchase Price or Selling Price.
- **SHOW PURCHASE PRICE ON INVOICE**: [select] - Yes/No toggle.
- **IDLE SESSION TIMEOUT (seconds)**: [number] - Auto-logout after inactivity (default: 2400).
- **ABSOLUTE SESSION TIMEOUT (seconds)**: [number] - Max session duration (default: 28800).
- **NEW PASSWORD**: [password] - Change current user password (min 8 chars, uppercase, lowercase, digit, special).

## Maintenance Functions
- **⬇ Download SQL Backup**: One-click full database export (all 23+ tables).
- **⬆ Restore Database**: Upload .sql file to restore (⚠️ overwrites all data).
- **🖼 Upload App Logo**: Auto-generates favicon, apple-touch-icon, web-app-manifest icons.

## System Info
- App Version, Author, PHP Version, MySQL Version, Database Name, Server Time.

## Landing Page Manager
- Hero Title/Subtitle
- Leadership Team (name, position, image, message, sort order)
- Image Gallery (title, description, image, sort order)
- Bike Requests (pending/contacted/fulfilled/cancelled)
- Quote Requests (pending/sent/accepted/rejected)
- Company Address, WhatsApp, Email
- Social Media: Facebook, Instagram, Twitter
- Google Maps Embed URL
- Vision/Mission Statements

## Visual Evidence
![Settings Full Capture](../screenshots/settings.png)
