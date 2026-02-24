# Cantinho Deolinda

Restaurant web project built with PHP, MySQL, HTML, CSS, and JavaScript.

The system includes:
- public website (menu, events, contact, reservations),
- customer area (dashboard),
- admin area (user/reservation management and audit logs).

## Tech Stack
- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- PHPMailer (email delivery)

## Main Features

### Public Area
- Homepage with menu, carousel, and restaurant content.
- Reservation modal with validations.
- Contact page.
- Site maintenance mode (controlled from admin).

### Account / Authentication
- User signup and login.
- Account verification by token (email).
- Password recovery.
- Dashboard with:
  - password change,
  - reservation cancellation (time-based rules),
  - favorites.

### Admin
- Admin panel with:
  - site status (active/maintenance),
  - users (block/unblock, role changes, no-show reset),
  - blacklist,
  - reservations (attendance and status),
  - latest audit records.
- Dedicated logs page: `admin_logs.php`.
- Filters, sorting, and CSV export:
  - logs (filtered CSV),
  - reservations (currently visible rows in admin).

## Security (Current State)

Implemented:
- Prepared statements in key operations.
- Critical admin actions migrated to `POST`.
- CSRF protection in:
  - `admin.php`,
  - `Bd/confirmar_reservas.php`,
  - `dashboard.php` (write forms).
- `Bd/favoritos.php` restricted to `POST` (no write actions via `GET`).
- Admin audit trail (`admin_audit_log`) for action tracking.

## Project Structure

```text
CantinhoDeolinda/
|-- index.php
|-- login.php
|-- dashboard.php
|-- admin.php
|-- admin_logs.php
|-- manut.php
|-- verificar_conta.php
|-- config.php
|-- Bd/
|   |-- ligar.php
|   |-- processar_reservas.php
|   |-- confirmar_reservas.php
|   |-- favoritos.php
|   |-- popup_helper.php
|   `-- ...
|-- Js/
|-- Css/
|-- recuperacao/
|-- Recursos/
|-- Seguranca/
|   |-- config.env
|   `-- config.env.example
`-- phpmailer/
```

## Setup

### 1) Database
Database connection is currently configured in `Bd/ligar.php`.

Update credentials for your local environment:
- host
- user
- password
- database

### 2) Sensitive Variables
Create `Seguranca/config.env` from `Seguranca/config.env.example` and fill:
- SMTP (`SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, etc.)
- WhatsApp Cloud (`META_TOKEN`, `PHONE_NUMBER_ID`, `DESTINO`) if used
- AI keys (`OPENAI_API_KEY` / `GROQ_API_KEY`) if used

Never commit `config.env` to Git.

### 3) Recommended Requirements
- PHP 8.x
- MySQL 5.7+ / 8.x
- Apache/Nginx

## Local Run
1. Clone/copy the project to your local web server (e.g., XAMPP `htdocs`).
2. Configure `Bd/ligar.php`.
3. Configure `Seguranca/config.env`.
4. Ensure base tables exist (`Cliente`, `reservas`, `estado_site`, etc.).
5. Open `index.php`.

Notes:
- `admin_audit_log` and some helper structures are auto-created when required.

## Quick Test Checklist

### Admin
- Block/unblock user.
- Change user role (admin/client).
- Mark reservation attendance.
- Reset no-shows.
- Toggle site status.
- Confirm critical actions are `POST` requests with `csrf_token`.

### Logs
- Apply filters.
- Export filtered CSV.
- Validate admin/action/target data.

### Dashboard
- Logout.
- Delete account (test account).
- Change password.
- Cancel reservation.
- Remove favorite / remove all favorites.

## Notes
- Some legacy frontend text still exists without proper accents in older sections; core admin/log pages were updated.
- If you want full encoding/text standardization across all pages, run a dedicated text cleanup pass.

## Author
Bruno Carvalho
