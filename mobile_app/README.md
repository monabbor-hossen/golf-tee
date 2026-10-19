# Pine Valley Golf Club — Mobile App (Flutter + Riverpod)

A high-fidelity mobile application for the Pine Valley Golf Club, matching the Figma design specification (`src/mobile/MobileApp.tsx`), powered by **Flutter** with **Riverpod 3** state management and connected to the `admin-php` MySQL database (`golf_admin`).

---

## 📱 Features & Screens (Figma Parity)

1. **Shell & Navigation (`lib/screens/shell_screen.dart`):**
   - Bottom navigation bar with animated pill selector (Home, Book, Bookings, Score, Profile).
   - Global Toast overlay with Pine Valley dark styling.

2. **Home Screen (`lib/screens/home_screen.dart`):**
   - Pine Valley Gold Crest & live weather conditions pill (`68° Sunny · 6 mph`).
   - **Next Round Card:** Displays upcoming confirmed tee time with 1-tap navigation to digital pass or quick booking CTA if empty.
   - **Quick Action Pills:** "Book tee time", "Active waitlist", "Digital scorecard", "Member perks".
   - **Live Course Conditions:** Stimp reading (`10.5`), NW wind speed, Cart rule (`90° Rule`), and refresh status.
   - **Live Pace of Play Tracker:** Status meter (`4h 12m avg round · 6m ahead of pace`).
   - **Upcoming Club Events:** Carousel with badges (Member-Guest, Twilight Scramble, Ladies Invitational).
   - **Club Announcements:** Live bulletin board loaded from MySQL database.

3. **Tee Time Booking (`lib/screens/book_screen.dart`):**
   - 14-day horizontal calendar with auto-scroll and weekend indicators.
   - 9-hole vs 18-hole segment toggle.
   - 1 to 4 player counter.
   - Time of Day filters: `All`, `Morning`, `Midday`, `Twilight (−35%)`.
   - Dynamic tee slot chips displaying state: **Available**, **Selected**, **Waitlist**, **Booked**, **Locked**, along with pricing ($85, $65, $55).
   - Sticky bottom bar calculating total price and direct navigation to Checkout or Join Waitlist.

4. **Checkout & Payment (`lib/screens/checkout_screen.dart`):**
   - Booking summary card with date, time, and player count.
   - **Playing Partners Selector:** Modal to add fellow club members (Marcus Vance, David Sterling, Julian Hayes, Robert Chen).
   - **Course Amenities:** Cart rental ($20/player) and Caddie reservation ($50/group) toggles.
   - **Promo Code:** Coupon system with `BIRDIE10` ($10 discount) validation.
   - **Saved Cards Selector:** Visa, Mastercard with "Add new card" modal.
   - **Cancellation Policy:** Full refund warning (> 24 hours).
   - Full price itemization & "Confirm & Pay" button with loading feedback.

5. **Booking Confirmation (`lib/screens/success_screen.dart`):**
   - Animated checkmark & confetti banner.
   - Digital ticket reference badge (`#PV-2026-XXXX`).
   - Real-time QR Code rendering powered by `qr_flutter`.
   - "View digital pass", "Add to calendar", and "Back to home" quick actions.

6. **My Bookings (`lib/screens/bookings_screen.dart`):**
   - 3-segment switcher: **Upcoming**, **Waitlist**, and **Past Rounds**.
   - Upcoming booking cards with date badge and 1-tap pass access.
   - Live waitlist cards with position (#1 in line, #3 in line) and "Leave waitlist" functionality.
   - Past rounds history with final scores.

7. **Digital Pass & Tee Time Detail (`lib/screens/detail_screen.dart`):**
   - Digital membership ticket with Pine Valley watermark.
   - QR Code for bag drop check-in and starter station scanning.
   - Game day weather forecast.
   - Course location & Clubhouse contact phone/email links.
   - Self-service booking cancellation with modal and automatic refund calculation.

8. **Digital Scorecard (`lib/screens/scorecard_screen.dart`):**
   - 18-hole interactive scorecard with par tracking (Holes 1–18, Pars 4/5/3).
   - Dynamic stroke increment/decrement (+ / -).
   - Real-time score-to-par calculation (`+1`, `E`, `-2`) with color-coded chips (Birdie/Eagle green, Par dark, Bogey amber, Double+ red).
   - Front 9 & Back 9 score breakdown summary table.
   - "Finish round" button that saves scores to MySQL database and calculates revised handicap index.

9. **Member Profile (`lib/screens/profile_screen.dart`):**
   - Member card with James Carter (`#PV-84291`, Full Golf Member).
   - Handicap Index card (`12.4`) with **CustomPainter Handicap Trend Line Chart**.
   - Rounds played, average score, member since statistics.
   - Saved payment methods manager (set default, delete card, add new card).
   - Notification preference switches (Tee time reminders, Waitlist openings, Tournament alerts, Weather updates).
   - Member directory and Clubhouse contact shortcuts.

---

## 🛠 Tech Stack

- **Framework:** Flutter 3.47+ / Dart 3.13+
- **State Management:** Riverpod 3 (`flutter_riverpod: ^3.4.3`) using `Notifier` and `NotifierProvider`
- **Typography:** Google Fonts Inter
- **Icons:** Material Icons & Cupertino Icons
- **QR Codes:** `qr_flutter: ^4.1.0`
- **HTTP Client:** `http: ^1.6.0`
- **Backend API:** PHP REST API at `admin-php/api/mobile.php` connecting to MySQL `golf_admin`

---

## 🚀 How to Run

### 1. Ensure Local PHP Server is Running
XAMPP Apache / MySQL should be started. The API is accessible at:
```
http://localhost/golf-tee/admin-php/api/mobile.php?endpoint=home
```

### 2. Run the Mobile App

Navigate to `mobile_app`:
```bash
cd mobile_app
```

**Run on Windows Desktop:**
```bash
flutter run -d windows
```

**Run in Chrome / Web Browser:**
```bash
flutter run -d chrome
```

**Run on Android Device / Emulator:**
```bash
flutter run -d android
```
*(On Android emulators, network requests automatically map to `10.0.2.2` to communicate with the host XAMPP server).*
