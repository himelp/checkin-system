# CheckTrack

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php) ![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql) ![License](https://img.shields.io/badge/License-MIT-green?style=flat)

A powerful multi-user employee check-in/check-out attendance management system.

## Features

- ✅ Multi-user check-in/checkout system
- 🔐 Secure login with bcrypt + CSRF protection
- 📊 Admin dashboard with live stats
- 📅 Calendar view with attendance visualization
- 📈 Analytics with Chart.js charts
- 📤 PDF & CSV export
- 🌍 Bilingual support (English + Italian)
- 📱 Mobile-first responsive design (PWA)
- 🔗 Google Sheets auto-sync via Apps Script
- ⚙️ WordPress-style web installer
- 🚀 Rate limiting & security headers

## Tech Stack

- **Backend:** PHP 8.2, MySQL
- **Frontend:** Tailwind CSS, Chart.js, FullCalendar.js
- **Integration:** Google Apps Script
- **Tools:** PDO, bcrypt, cURL

## Requirements

- PHP >= 8.2
- MySQL >= 5.7
- Apache with mod_rewrite enabled
- cURL extension enabled

## Installation

1. Upload all files to your web hosting (public_html)
2. Create a MySQL database in your hosting panel
3. Visit `yourdomain.com/install.php`
4. Follow the installation wizard
5. Login at `yourdomain.com`

## Google Sheets Setup

1. Create a new Google Spreadsheet
2. Go to Extensions → Apps Script
3. Copy code from `apps_script/Code.gs` and paste it
4. Deploy as Web App (Execute as: Me, Access: Anyone)
5. Copy the Web App URL
6. Go to Admin → Settings → Google Sheets → Paste URL → Save & Test

## Security

- bcrypt password hashing
- CSRF token protection
- Rate limiting (5 attempts/10 min)
- PDO prepared statements
- XSS protection headers
- robots.txt & noindex meta tags

---

**Created with ❤️ by [Minhaz Bin Santo](https://www.linkedin.com/in/minhaz-bin-santo/)**

> 💼 Available for freelance projects | Web Development & System Design

[![LinkedIn](https://img.shields.io/badge/LinkedIn-Minhaz%20Bin%20Santo-blue?style=flat&logo=linkedin)](https://www.linkedin.com/in/minhaz-bin-santo/)

---

## License

MIT License
