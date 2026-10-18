# Pine Valley Golf Club — Course Admin Application (PHP + MySQL)

A modern, fully functional web application converted from Figma designs into a PHP + MySQL architecture.

## 🚀 Quick Access

- **URL:** [http://localhost/golf-tee/admin-php/](http://localhost/golf-tee/admin-php/)
- **Default Sign In Credentials:**
  - **Email:** `admin@pinevalley.com`
  - **Password:** `password`

Additional staff accounts (all passwords default to `password`):
- `grace@pinevalley.com` (Manager)
- `tom@pinevalley.com` (Front Desk)
- `ava@pinevalley.com` (Front Desk)

---

## 📁 Clean Architecture Project Structure

The application is structured following **Clean Architecture (Layered / Hexagonal)** principles with clear separation of concerns, zero SQL injection, type-safety, and testability:

```text
admin-php/
├── app/                           # Core Clean Architecture namespace (App\...)
│   ├── Core/                      # Framework & Infrastructure Primitives
│   │   ├── Autoloader.php         # PSR-4 compliant zero-dependency autoloader
│   │   ├── Config.php             # Type-safe configuration & .env loader
│   │   ├── Database.php           # PDO Singleton with strict prepared statements
│   │   ├── Request.php            # HTTP Request wrapper (GET/POST/JSON/types)
│   │   ├── Response.php           # HTTP Response (redirect, json, csv stream)
│   │   ├── Session.php            # Session, auth state & flash messaging
│   │   └── View.php               # Template engine with layout & escaping
│   ├── Domain/                    # Domain Layer (Pure Business Rules)
│   │   ├── PricingCalculator.php  # Green fee calculations (twilight/weekend)
│   │   └── SlotFormatter.php      # Time slot conversions & display utilities
│   ├── Repositories/              # Data Access Layer (100% Prepared Statements)
│   │   ├── ActivityRepository.php
│   │   ├── BlockedSlotRepository.php
│   │   ├── BookingRepository.php
│   │   ├── EventRegistrationRepository.php
│   │   ├── EventRepository.php
│   │   ├── MemberRepository.php
│   │   ├── NotificationRepository.php
│   │   ├── SettingRepository.php
│   │   ├── StaffRepository.php
│   │   ├── TransactionRepository.php
│   │   └── WaitlistRepository.php
│   ├── Services/                  # Application Layer (Use Cases & Workflows)
│   │   ├── AuthService.php        # Staff credentials verification & session
│   │   ├── BookingService.php     # Conflict checks, booking & automatic txns
│   │   ├── DashboardService.php   # Metrics aggregation & operational alerts
│   │   ├── EventService.php       # Tournament scheduling & registrations
│   │   ├── LayoutService.php      # Layout badges & notifications provider
│   │   ├── MemberService.php      # Directory queries, life-cycle & CSV export
│   │   ├── PaymentService.php     # Ledger processing, refunds & CSV export
│   │   ├── ReportService.php      # Pacing, revenue charts & member spend
│   │   └── WaitlistService.php    # Queue prioritization & tee sheet conversion
│   └── Controllers/               # Presentation Layer (Adapters)
│       ├── ApiController.php      # REST / AJAX handler for client calls
│       ├── AuthController.php     # Sign-in & sign-out flows
│       ├── DashboardController.php# Main operations dashboard
│       ├── EventController.php    # Events management
│       ├── MasterGridController.php# Tee sheet matrix & slot blocking
│       ├── MemberController.php   # Member hub & profile inspector
│       ├── PaymentController.php  # Transactions ledger & refunds
│       ├── ReportController.php   # Analytics & trends
│       ├── SettingController.php  # Club preferences & staff roles
│       └── WaitlistController.php # Waitlist manager
├── views/                         # Clean View Templates (No raw SQL queries)
│   ├── layouts/
│   │   ├── header.php             # Modern responsive sidebar, topbar & search
│   │   └── footer.php             # Bottom mobile navigation, toast & scripts
│   ├── dashboard.php
│   ├── events.php
│   ├── login.php
│   ├── mastergrid.php
│   ├── members.php
│   ├── payments.php
│   ├── reports.php
│   ├── settings.php
│   └── waitlist.php
├── api/                           # Thin REST / AJAX API Endpoints
│   ├── bookings.php               # Check-in, quick-booking API
│   ├── notifications.php          # Mark read / all read API
│   ├── payments.php               # Record transactions, refunds
│   ├── reports.php                # Date-range metrics export
│   └── search.php                 # Real-time search across pages & models
├── assets/                        # Design System Assets
│   ├── css/
│   │   └── main.css               # Styling tokens, grid, pills & modals
│   └── js/
│       └── app.js                 # AJAX handlers, notifications, drawer & modals
├── bootstrap.php                  # Application initialization & PSR-4 autoloader
├── index.php                      # Thin entrypoint -> DashboardController
├── mastergrid.php                 # Thin entrypoint -> MasterGridController
├── members.php                    # Thin entrypoint -> MemberController
├── events.php                     # Thin entrypoint -> EventController
├── waitlist.php                   # Thin entrypoint -> WaitlistController
├── payments.php                   # Thin entrypoint -> PaymentController
├── reports.php                    # Thin entrypoint -> ReportController
├── settings.php                   # Thin entrypoint -> SettingController
├── login.php                      # Thin entrypoint -> AuthController@login
├── logout.php                     # Thin entrypoint -> AuthController@logout
├── database.sql                   # MySQL schema & initial seed data
├── .env                           # Database credentials & app configuration
└── .env.example                   # Template for configuration
```

---

## 🛠️ Requirements & Database

- **Web Server:** Apache (via XAMPP)
- **PHP:** 8.0+ (PDO, MySQLnd)
- **Database:** MySQL / MariaDB (Database name: `golf_admin`)

If you ever need to re-import or reset the database:
```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "source C:/xampp/htdocs/golf-tee/admin-php/database.sql"
```

---

## ✨ Features Implemented

1. **Operations Dashboard (`index.php`)**:
   - Live revenue, rounds, average pace, and utilization metrics
   - Next tee time countdown & quick check-in
   - Hourly capacity & weather preview
   - Pending member approvals & waitlist alerts
   - Real-time activity log

2. **Master Tee Sheet (`mastergrid.php`)**:
   - Full 10-minute slot matrix from 7:00 AM to 5:50 PM
   - Dual tee support (Hole 1 and Hole 10)
   - Status indicators (`Booked`, `Checked in`, `Blocked/Maintenance`)
   - Modal to quickly book slots or block for maintenance

3. **Members Hub (`members.php`)**:
   - Member search, tier filtering (Gold, Silver, Standard), and status tags
   - Detailed slide-out member profile showing handicap, rounds, total spend, and play history
   - Add new member modal

4. **Events & Tournaments (`events.php`)**:
   - Tournaments, scrambles, and club championships
   - Registration roster and capacity counters
   - Modal to create and schedule new events

5. **Waitlist Manager (`waitlist.php`)**:
   - Preferred time slots, party sizes, and notes
   - Status progression (`Waiting`, `Offered`, `Confirmed`, `Expired`)
   - Direct slot offering actions

6. **Payments & Ledger (`payments.php`)**:
   - Financial breakdown: Green fees, cart rentals, pro shop, and guest fees
   - Transaction list with payment methods (Visa, Member account, Cash, Master)
   - Record payment modal

7. **Reports & Analytics (`reports.php`)**:
   - Date range selector
   - Course utilization rate and pace of play tracking
   - Export reports action

8. **Settings & Course Configuration (`settings.php`)**:
   - Course name, timezone, contact info
   - Green fees configuration (Weekday, Weekend, 9-hole / 18-hole, Cart fee)
   - Operating hours and slot intervals
   - Staff roles & permissions

---

## Security Documentation

For detailed information on the application's security architecture, SQL injection defenses, CSRF protection, session hardening, and developer security rules, see [SECURITY.md](file:///c:/xampp/htdocs/golf-tee/admin-php/SECURITY.md).
