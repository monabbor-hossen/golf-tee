-- ============================================================
-- Caddie Booking System Migration
-- Run this against your golf_admin database:
--   mysql -u root golf_admin < caddie_migration.sql
-- ============================================================

USE golf_admin;

-- ── Caddies Roster ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS caddies (
  id                INT          AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(100) NOT NULL,
  phone             VARCHAR(30)  DEFAULT '',
  experience_years  TINYINT      NOT NULL DEFAULT 1,
  specialty         VARCHAR(200) DEFAULT '',
  rate_per_round    DECIMAL(8,2) NOT NULL DEFAULT 50.00,
  rating            DECIMAL(2,1) NOT NULL DEFAULT 4.0,
  status            ENUM('Available','Unavailable','On Leave') NOT NULL DEFAULT 'Available',
  notes             TEXT,
  created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Caddie Bookings ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS caddie_bookings (
  id            INT          AUTO_INCREMENT PRIMARY KEY,
  caddie_id     INT          NOT NULL,
  golfer_name   VARCHAR(100) NOT NULL,
  member_id     INT          DEFAULT NULL,
  booking_date  DATE         NOT NULL,
  tee_slot      INT          NOT NULL,   -- minutes from midnight (e.g. 420 = 7:00 AM)
  tee           TINYINT      NOT NULL DEFAULT 0,  -- 0=Hole1, 1=Hole10
  holes         TINYINT      NOT NULL DEFAULT 18,
  party         TINYINT      NOT NULL DEFAULT 1,
  fee           DECIMAL(8,2) NOT NULL DEFAULT 0,
  status        ENUM('Confirmed','Completed','Cancelled','No-show') NOT NULL DEFAULT 'Confirmed',
  notes         TEXT,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_caddie_date (caddie_id, booking_date),
  KEY idx_booking_date (booking_date),
  FOREIGN KEY (caddie_id)  REFERENCES caddies(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id)  REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seed Caddies ──────────────────────────────────────────────
INSERT IGNORE INTO caddies (id, name, phone, experience_years, specialty, rate_per_round, rating, status, notes) VALUES
(1, 'Carlos Mendez',   '(555) 401-1010', 12, 'Course knowledge, Club selection', 65.00, 4.9, 'Available', 'Knows every break on greens 1–9. Preferred by Gold members.'),
(2, 'Tamika Williams', '(555) 401-2020',  8, 'Reading greens, Distance judging', 55.00, 4.7, 'Available', 'Excellent with beginners. Very patient communicator.'),
(3, 'Diego Santos',    '(555) 401-3030',  5, 'Physical fitness, Long carries',   50.00, 4.5, 'Available', ''),
(4, 'Sarah Kim',       '(555) 401-4040', 15, 'Tournament caddying, Strategy',    80.00, 5.0, 'Available', 'Former Tour caddie. Reserved for club events by default.'),
(5, 'Marcus Reed',     '(555) 401-5050',  3, 'Friendly demeanor, Cart handling', 45.00, 4.2, 'On Leave',  'On leave until November.'),
(6, 'Elena Petrov',    '(555) 401-6060',  9, 'Wind reading, Course mapping',     60.00, 4.6, 'Available', ''),
(7, 'James Park',      '(555) 401-7070',  2, 'Club cleaning, Bag carrying',      40.00, 4.0, 'Unavailable','Temporarily unavailable – scheduling conflict.');

-- ── Seed Caddie Bookings ─────────────────────────────────────
INSERT IGNORE INTO caddie_bookings (caddie_id, golfer_name, member_id, booking_date, tee_slot, tee, holes, party, fee, status, notes) VALUES
(1, 'James Carter',    1, CURDATE(), 420, 0, 18, 4, 65.00, 'Confirmed', ''),
(2, 'Mina Lee',        2, CURDATE(), 420, 1, 18, 4, 55.00, 'Confirmed', ''),
(4, 'Daniel Brown',    7, CURDATE(), 440, 0, 18, 4, 80.00, 'Confirmed', 'Requests strategy tips on back 9'),
(6, 'Priya Shah',     10, CURDATE(), 480, 0, 18, 3, 60.00, 'Confirmed', ''),
(3, 'Alejandro Gomez', 5, CURDATE(), 490, 1, 18, 4, 50.00, 'Completed', ''),
(1, 'Lucia Rossi',     8, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 530, 0, 18, 3, 65.00, 'Confirmed', ''),
(2, 'Raj Patel',       3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 480, 1, 18, 2, 55.00, 'Confirmed', ''),
(4, 'James Carter',    1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 480, 0, 18, 4, 80.00, 'Confirmed', 'Championship prep round'),
(6, 'Sofia Kim',       4, DATE_ADD(CURDATE(), INTERVAL 4 DAY), 600, 0,  9, 2, 60.00, 'Confirmed', ''),
(1, 'Priya Shah',     10, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 420, 0, 18, 3, 65.00, 'Completed', ''),
(2, 'Daniel Brown',    7, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 440, 0, 18, 4, 55.00, 'Completed', ''),
(5, 'Ethan Novak',  NULL, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 500, 0,  9, 2, 45.00, 'No-show',   ''),
(3, 'Carmen Diaz',  NULL, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 510, 1, 18, 4, 50.00, 'Completed', '');
