# 📡 GolfTee — REST API Documentation

This document provides a comprehensive technical reference for the GolfTee Mobile & Integration REST API (`admin-php/api/mobile.php`).

---

## 🌐 General Information

- **Base URL (Local):** `http://localhost/golf-tee/admin-php/api/mobile.php`
- **Base URL (Docker):** `http://localhost:8080/admin-php/api/mobile.php`
- **Data Format:** JSON (`Content-Type: application/json; charset=utf-8`)
- **CORS:** Enabled (`Access-Control-Allow-Origin: *`)
- **Authentication:** Session / Token / `member_id` query/body parameter with role-based access.

---

## 📋 Endpoints Overview

| Action | Method | Description |
| :--- | :---: | :--- |
| `home` | `GET` | Dashboard payload (profile, next round, conditions, events, announcements) |
| `slots` | `GET` | 14-day tee sheet slot grid with dynamic pricing and availability |
| `book` | `POST` | Reserve tee time, add players, caddies, carts, promo codes, card checkout |
| `bookings` | `GET` | Member rounds history (Upcoming, Waitlist, Past rounds) |
| `booking_detail` | `GET` | Complete digital pass data, QR payload, cancellation rules |
| `cancel_booking` | `POST` | Cancel a confirmed reservation with refund calculation |
| `join_waitlist` | `POST` | Add player to waitlist for a booked tee time slot |
| `leave_waitlist` | `POST` | Remove entry from waitlist |
| `weather` | `GET` | Real-time weather, temperature, wind, stimpmeter, cart rule |
| `profile` | `GET` | Member details, handicap index trend, stats, cards, notifications |
| `save_score` | `POST` | Submit 18-hole score card and recalculate member handicap index |
| `cards` | `GET` | Retrieve saved payment methods for a member |
| `add_card` | `POST` | Store a new masked credit card token |
| `set_default_card` | `POST` | Update primary payment card |
| `delete_card` | `POST` | Remove a saved payment method |
| `login` | `POST` | Member login authentication with password hash verification |
| `members` | `GET` | Member directory for adding playing partners during checkout |

---

## 🔍 Detailed Endpoint Reference

### 1. Home Dashboard
- **Method:** `GET`
- **Action:** `home`
- **Parameters:**
  - `member_id` *(int, optional)*: ID of the member (default: `1` or `15`)
- **Sample Request:**
  ```http
  GET /admin-php/api/mobile.php?action=home&member_id=15 HTTP/1.1
  ```
- **Sample Response:**
  ```json
  {
    "status": "ok",
    "member": {
      "id": 15,
      "name": "James Carter",
      "email": "carter@pinevalley.com",
      "tier": "Full Golf Member",
      "hcp": "12.4",
      "member_number": "#PV-00015"
    },
    "next_round": {
      "id": 1,
      "slot_time": "08:30:00",
      "date": "2026-10-23",
      "players": 4,
      "qr_token": "PV-2026-84291-0830"
    },
    "course_conditions": {
      "temp": 68,
      "condition": "Sunny",
      "stimp": "10.5",
      "cart_rule": "90° Rule",
      "wind": "6 mph NW"
    },
    "announcements": [...],
    "events": [...]
  }
  ```

---

### 2. Available Tee Slots
- **Method:** `GET`
- **Action:** `slots`
- **Parameters:**
  - `date` *(string, required)*: Target round date in `YYYY-MM-DD` format.
  - `holes` *(int, optional)*: `9` or `18` (default: `18`).
- **Sample Request:**
  ```http
  GET /admin-php/api/mobile.php?action=slots&date=2026-10-23&holes=18 HTTP/1.1
  ```
- **Response Fields:**
  - `slots`: Array of tee slots containing `time`, `price`, `status` (`available`, `locked`, `booked`, `waitlist`), `max_players`, and `booked_players`.

---

### 3. Reserve Tee Time (Checkout)
- **Method:** `POST`
- **Action:** `book`
- **Request Body (JSON):**
  ```json
  {
    "action": "book",
    "member_id": 15,
    "date": "2026-10-24",
    "time": "09:00:00",
    "holes": 18,
    "players": 2,
    "cart_rental": true,
    "caddie_service": true,
    "promo_code": "BIRDIE10",
    "card_id": 1
  }
  ```
- **Sample Response:**
  ```json
  {
    "status": "ok",
    "booking_id": 18,
    "reference": "PV-2026-1024-0900-18",
    "qr_token": "PV-QR-20261024-0900",
    "total_paid": 160.00,
    "message": "Booking confirmed successfully"
  }
  ```

---

### 4. Member Bookings History
- **Method:** `GET`
- **Action:** `bookings`
- **Parameters:**
  - `member_id` *(int, required)*
- **Response Segments:**
  - `upcoming`: Confirmed upcoming tee times.
  - `waitlist`: Active waitlist entries with queue position (`#1 in line`).
  - `past`: Completed rounds with recorded scores and dates.

---

### 5. Cancel Booking
- **Method:** `POST`
- **Action:** `cancel_booking`
- **Request Body (JSON):**
  ```json
  {
    "action": "cancel_booking",
    "id": 18,
    "member_id": 15,
    "reason": "Schedule conflict"
  }
  ```
- **Refund Policy:**
  - Cancellations > 24 hours prior to tee time receive a 100% automated refund.
  - Cancellations within 24 hours receive a 50% club credit.

---

### 6. Weather & Course Conditions
- **Method:** `GET`
- **Action:** `weather`
- **Sample Request:**
  ```http
  GET /admin-php/api/mobile.php?action=weather HTTP/1.1
  ```
- **Response:**
  ```json
  {
    "status": "ok",
    "source": "wttr.in/PineValley",
    "temp": 68,
    "condition": "Sunny",
    "stimp": "10.5",
    "cart_rule": "90° Rule",
    "wind": "6 mph NW",
    "last_updated": "2026-10-22 10:00:00"
  }
  ```

---

### 7. Member Authentication
- **Method:** `POST`
- **Action:** `login`
- **Request Body (JSON):**
  ```json
  {
    "action": "login",
    "email": "carter@pinevalley.com",
    "password": "Password123!"
  }
  ```
- **Sample Response:**
  ```json
  {
    "status": "ok",
    "member": {
      "id": 15,
      "name": "James Carter",
      "email": "carter@pinevalley.com",
      "tier": "Full Golf Member",
      "hcp": "12.4"
    }
  }
  ```

---

## 🔒 Error Handling & Status Codes

All errors return standard HTTP status codes along with a JSON body describing the issue:

```json
{
  "status": "error",
  "message": "Detailed description of the validation or authorization failure."
}
```

| HTTP Status | Meaning | Common Cause |
| :---: | :--- | :--- |
| `200` | OK | Request processed successfully |
| `400` | Bad Request | Missing required fields, invalid date/time |
| `401` | Unauthorized | Invalid password or credentials |
| `403` | Forbidden | Action disallowed (e.g. self-registration blocked) |
| `404` | Not Found | Booking ID or Member ID does not exist |
| `500` | Server Error | Database failure or external service timeout |
