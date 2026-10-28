-- DEPRECATED: Use /database/schema.sql instead
-- Golf Tee Admin Database Schema
-- Run this in phpMyAdmin or via: mysql -u root < database/schema.sql

CREATE DATABASE IF NOT EXISTS golf_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE golf_admin;

-- --------------------------------------------------------
-- Members / Users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS members (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed, never plain text',
  password_hash VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed, never plain text',
  password_reset_token VARCHAR(100) DEFAULT NULL,
  password_reset_expires DATETIME DEFAULT NULL,
  phone       VARCHAR(30)  DEFAULT '',
  tier        ENUM('Gold','Silver','Standard') NOT NULL DEFAULT 'Standard',
  hcp         DECIMAL(4,1) DEFAULT 36.0,
  rounds      INT          DEFAULT 0,
  last_play   VARCHAR(40)  DEFAULT '',
  joined      VARCHAR(40)  DEFAULT '',
  status      ENUM('Active','Suspended','Pending') NOT NULL DEFAULT 'Pending',
  spend       DECIMAL(10,2) DEFAULT 0,
  next_tee    VARCHAR(120)  DEFAULT '',
  setup_token VARCHAR(100) DEFAULT NULL,
  setup_token_expires DATETIME DEFAULT NULL,
  auth_provider VARCHAR(50) DEFAULT 'email',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE UNIQUE INDEX idx_members_email ON members(email);

-- --------------------------------------------------------
-- Member Payment Cards
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS member_cards (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  member_id   INT NOT NULL,
  brand       VARCHAR(50) NOT NULL DEFAULT 'Visa',
  card_holder VARCHAR(100) DEFAULT '',
  last4       VARCHAR(4) NOT NULL,
  exp_month   VARCHAR(2) NOT NULL,
  exp_year    VARCHAR(4) NOT NULL,
  is_default  TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_member_cards_member_id ON member_cards(member_id);

CREATE TABLE IF NOT EXISTS payment_cards (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  member_id   INT NOT NULL,
  brand       VARCHAR(50) NOT NULL DEFAULT 'Visa',
  card_holder VARCHAR(100) DEFAULT '',
  last4       VARCHAR(4) NOT NULL,
  exp_month   VARCHAR(2) NOT NULL,
  exp_year    VARCHAR(4) NOT NULL,
  is_default  TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_payment_cards_member_id ON payment_cards(member_id);

-- --------------------------------------------------------
-- Bookings / Tee Grid
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tee_date    DATE         NOT NULL,
  tee_slot    INT          NOT NULL,  -- minutes from midnight (e.g. 420 = 7:00 AM)
  tee         TINYINT      NOT NULL DEFAULT 0,  -- 0=Hole1, 1=Hole10
  name        VARCHAR(100) NOT NULL,
  party       TINYINT      NOT NULL DEFAULT 2,
  holes       TINYINT      NOT NULL DEFAULT 18,
  status      ENUM('Booked','Checked in','No-show') NOT NULL DEFAULT 'Booked',
  notes       TEXT,
  member_id   INT          DEFAULT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slot (tee_date, tee_slot, tee),
  KEY idx_date (tee_date),
  KEY idx_member (member_id),
  KEY idx_booking_member_id (member_id),
  KEY idx_bookings_member_date (member_id, tee_date),
  KEY idx_bookings_date_status (tee_date, status),
  KEY idx_bookings_member_status (member_id, status),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE INDEX idx_bookings_member_id ON bookings(member_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_tee_date ON bookings(tee_date);

-- --------------------------------------------------------
-- Blocked slots
-- --------------------------------------------------------
DROP TABLE IF EXISTS blocked_slots;
CREATE TABLE IF NOT EXISTS blocked_slots (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tee_date    DATE         NOT NULL,
  tee_slot    INT          NOT NULL,
  tee         TINYINT      NOT NULL DEFAULT 0,
  reason      VARCHAR(200) DEFAULT 'Maintenance',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_block (tee_date, tee_slot, tee)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Waitlist
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS waitlist (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  tee_date    DATE         NOT NULL,
  tee_slot    INT          NOT NULL,
  party       TINYINT      NOT NULL DEFAULT 2,
  holes       TINYINT      NOT NULL DEFAULT 18,
  added_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  status      ENUM('Waiting','Offered') NOT NULL DEFAULT 'Waiting',
  offered_slot INT          DEFAULT NULL,
  member_id   INT          DEFAULT NULL,
  KEY idx_waitlist_member_id (member_id),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE INDEX idx_waitlist_member_id ON waitlist(member_id);

-- --------------------------------------------------------
-- Events / Tournaments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  event_date  DATE         NOT NULL,
  start_slot  INT          NOT NULL DEFAULT 480,
  hours       TINYINT      NOT NULL DEFAULT 4,
  format      VARCHAR(50)  DEFAULT 'Scramble',
  capacity    INT          NOT NULL DEFAULT 48,
  fee         DECIMAL(8,2) DEFAULT 75.00,
  is_open     TINYINT(1)   DEFAULT 1,
  is_blocked  TINYINT(1)   DEFAULT 0,
  created_by  INT          DEFAULT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_events_created_by (created_by),
  FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_registrations (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  event_id    INT          NOT NULL,
  name        VARCHAR(100) NOT NULL,
  registered_at TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Transactions / Payments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
  id          VARCHAR(20)  NOT NULL PRIMARY KEY,
  txn_date    DATE         NOT NULL DEFAULT (CURDATE()),
  txn_time    INT          NOT NULL DEFAULT 660,  -- minutes from midnight
  name        VARCHAR(100) NOT NULL,
  description TEXT,
  type        ENUM('Green fee','Pro shop','Food & bev','Event','Membership') NOT NULL DEFAULT 'Green fee',
  method      VARCHAR(50)  DEFAULT 'Visa',
  amount      DECIMAL(10,2) NOT NULL DEFAULT 0,
  status      ENUM('Paid','Pending','Refunded') NOT NULL DEFAULT 'Paid',
  ref         VARCHAR(100) DEFAULT NULL,
  member_id   INT          DEFAULT NULL,
  booking_id  INT          DEFAULT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_transactions_member_id (member_id),
  KEY idx_transaction_booking_id (booking_id),
  KEY idx_transactions_booking_id (booking_id),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE INDEX idx_transactions_member_id ON transactions(member_id);

-- --------------------------------------------------------
-- Activity log
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(150) NOT NULL,
  description TEXT,
  tone        ENUM('forest','wait','danger','sand','gray') DEFAULT 'gray',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  member_id   INT          DEFAULT NULL,
  title       VARCHAR(150) NOT NULL,
  description TEXT,
  page        VARCHAR(50)  DEFAULT 'Dashboard',
  focus_data  VARCHAR(200) DEFAULT NULL,
  is_read     TINYINT(1)   DEFAULT 0,
  tone        ENUM('forest','wait','danger','sand','gray') DEFAULT 'gray',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_member_id (member_id),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_notifications_member_id ON notifications(member_id);

-- --------------------------------------------------------
-- Settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  `key`       VARCHAR(100) NOT NULL PRIMARY KEY,
  `value`     TEXT,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Staff
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  role        ENUM('Owner','Manager','Front Desk','Read only') NOT NULL DEFAULT 'Front Desk',
  password    VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed, never plain text',
  password_hash VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed, never plain text',
  password_must_change TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: login_attempts (IP-based brute-force protection)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  attempt_time INT NOT NULL,
  INDEX idx_login_attempts_ip_time (ip, attempt_time)
) ENGINE=InnoDB;
CREATE INDEX idx_login_attempts_ip_time ON login_attempts(ip, attempt_time);

-- --------------------------------------------------------
-- Member booking history (view / denormalized for profile)
-- --------------------------------------------------------
CREATE VIEW member_history AS
  SELECT b.member_id, b.tee_date, b.tee_slot, b.party, b.holes,
         t.amount, b.status
  FROM   bookings b
  LEFT JOIN transactions t ON t.ref = CONCAT(b.tee_date,'|',b.tee,'-',b.tee_slot)
  WHERE  b.member_id IS NOT NULL;

-- ====================================================
-- SEED DATA
-- ====================================================

-- Default settings
INSERT IGNORE INTO settings (`key`,`value`) VALUES
('course_name', 'Pine Valley Golf Club'),
('phone', '(555) 214-8890'),
('address', '1200 Fairway Drive, Pine Valley, NJ 08021'),
('timezone', 'America/New_York'),
('currency', 'USD'),
('open_days', '["Mon","Tue","Wed","Thu","Fri","Sat","Sun"]'),
('first_tee', '07:00'),
('last_tee', '17:50'),
('interval', '10'),
('max_party', '4'),
('advance_days', '14'),
('cancel_hours', '24'),
('twilight_start', '900'),
('price_weekday_18', '85'),
('price_weekday_9', '48'),
('price_weekend_18', '110'),
('price_weekend_9', '62'),
('price_twilight_18', '55'),
('price_twilight_9', '35'),
('flag_waitlist', '1'),
('flag_nine', '1'),
('flag_guest', '1'),
('flag_deposit', '0'),
('flag_twilight', '1'),
('flag_dynamic', '0'),
('flag_auto_offer', '1'),
('flag_n_book', '1'),
('flag_n_cancel', '1'),
('flag_n_wait', '1'),
('flag_n_daily', '0'),
('flag_n_sms', '0');

-- --------------------------------------------------------
-- Admin staff account (password hashed with bcrypt; password_must_change enforced)
-- IMPORTANT: After importing this schema, immediately set admin password via
-- admin-php/set-password.php or the admin panel. Default credentials are disabled for security.
-- --------------------------------------------------------
INSERT IGNORE INTO staff (name, email, role, password, password_must_change) VALUES
('Admin', 'admin@pinevalley.com', 'Owner', '$2y$10$yZWnT0/CxTd3dz6XRZ8kPO35tkfk8q6s2BljeuS6BoAAKdMmGBbsK', 1),
('Grace Walsh', 'grace@pinevalley.com', 'Manager', NULL, 1),
('Tom Becker', 'tom@pinevalley.com', 'Front Desk', NULL, 1),
('Ava Morales', 'ava@pinevalley.com', 'Front Desk', NULL, 1);

-- Seed members
INSERT IGNORE INTO members (id,name,email,phone,tier,hcp,rounds,last_play,joined,status,spend,next_tee) VALUES
(1,'James Carter','james.carter@mail.com','(555) 301-2201','Gold',8.4,42,'Oct 12','Mar 2021','Active',4820.00,'Sat, Nov 2 · 7:30 AM · 4 players'),
(2,'Mina Lee','mina.lee@mail.com','(555) 301-4410','Gold',5.1,67,'Today','Jun 2019','Active',7310.00,'Tue, Oct 29 · 7:00 AM · 4 players'),
(3,'Raj Patel','r.patel@mail.com','(555) 301-8812','Silver',14.2,23,'Today','Jan 2023','Active',2140.00,''),
(4,'Sofia Kim','sofia.k@mail.com','(555) 301-0937','Standard',21.0,6,'Oct 20','Oct 2024','Pending',410.00,''),
(5,'Alejandro Gomez','agomez@mail.com','(555) 301-5521','Silver',11.7,31,'Today','Aug 2022','Active',2980.00,'Sat, Nov 2 · 8:30 AM · 4 players'),
(6,'Thanh Nguyen','thanh.n@mail.com','(555) 301-7746','Standard',18.3,12,'Sep 30','Feb 2024','Suspended',960.00,''),
(7,'Daniel Brown','d.brown@mail.com','(555) 301-3390','Gold',3.9,88,'Today','Apr 2017','Active',11250.00,'Thu, Oct 31 · 11:30 AM · 4 players'),
(8,'Lucia Rossi','lucia.rossi@mail.com','(555) 301-6604','Silver',16.5,19,'Oct 18','May 2023','Active',1720.00,'Today · 11:50 AM · 3 players'),
(9,'Kenji Ito','kenji.ito@mail.com','(555) 301-2258','Standard',24.8,4,'Oct 02','Sep 2024','Pending',220.00,''),
(10,'Priya Shah','priya.s@mail.com','(555) 301-9183','Gold',9.6,54,'Oct 21','Nov 2020','Active',6040.00,'Today · 11:40 AM · 3 players');

-- Seed today's bookings (tee_date = CURDATE())
INSERT IGNORE INTO bookings (tee_date,tee_slot,tee,name,party,holes,status,member_id) VALUES
(CURDATE(),420,0,'Mina Lee',4,18,'Checked in',2),
(CURDATE(),420,1,'Raj Patel',3,18,'Checked in',3),
(CURDATE(),430,0,'James Carter',2,18,'Checked in',1),
(CURDATE(),440,0,'Daniel Brown',4,18,'Checked in',7),
(CURDATE(),480,0,'Priya Shah',3,18,'Checked in',10),
(CURDATE(),490,1,'Alejandro Gomez',4,18,'Checked in',5),
(CURDATE(),500,0,'Ethan Novak',2,9,'Checked in',NULL),
(CURDATE(),510,1,'Carmen Diaz',4,18,'Booked',NULL),
(CURDATE(),520,0,'Ben Hall',2,18,'Booked',NULL),
(CURDATE(),530,0,'Lucia Rossi',3,18,'Booked',8),
(CURDATE(),600,0,'Marcus Reed',3,18,'Booked',NULL),
(CURDATE(),610,1,'Hannah Fox',2,9,'Booked',NULL),
(CURDATE(),660,0,'Olivia Chen',4,18,'Booked',NULL),
(CURDATE(),700,1,'Priya Shah',3,18,'Booked',10),
(CURDATE(),710,0,'Sofia Kim',2,9,'Booked',4);

-- Seed blocked slots today
INSERT IGNORE INTO blocked_slots (tee_date,tee_slot,tee,reason) VALUES
(CURDATE(),570,0,'Maintenance'),
(CURDATE(),580,0,'Maintenance'),
(CURDATE(),590,0,'Maintenance'),
(CURDATE(),600,1,'Maintenance');

-- Yesterday bookings
INSERT IGNORE INTO bookings (tee_date,tee_slot,tee,name,party,holes,status,member_id) VALUES
(DATE_SUB(CURDATE(),INTERVAL 1 DAY),420,0,'James Carter',4,18,'Checked in',1),
(DATE_SUB(CURDATE(),INTERVAL 1 DAY),430,1,'Mina Lee',4,18,'Checked in',2),
(DATE_SUB(CURDATE(),INTERVAL 1 DAY),440,0,'Daniel Brown',4,18,'Checked in',7),
(DATE_SUB(CURDATE(),INTERVAL 1 DAY),480,0,'Thanh Nguyen',2,18,'No-show',6),
(DATE_SUB(CURDATE(),INTERVAL 1 DAY),490,1,'Priya Shah',4,18,'Checked in',10);

-- Seed waitlist
INSERT IGNORE INTO waitlist (id,name,tee_date,tee_slot,party,holes,status,member_id) VALUES
(1,'Kenji Ito',CURDATE(),880,2,18,'Waiting',9),
(2,'Olivia Chen',CURDATE(),760,4,18,'Waiting',NULL),
(3,'Raj Patel',DATE_ADD(CURDATE(),INTERVAL 1 DAY),480,2,18,'Waiting',3),
(4,'Marcus Reed',DATE_ADD(CURDATE(),INTERVAL 1 DAY),450,3,9,'Waiting',NULL),
(5,'James Carter',DATE_ADD(CURDATE(),INTERVAL 4 DAY),430,4,18,'Waiting',1),
(6,'Hannah Fox',DATE_ADD(CURDATE(),INTERVAL 5 DAY),920,2,9,'Waiting',NULL);

-- Seed events
INSERT IGNORE INTO events (id,name,event_date,start_slot,hours,format,capacity,fee,is_open,is_blocked) VALUES
(1,'Junior Golf Clinic',DATE_ADD(CURDATE(),INTERVAL 3 DAY),900,2,'Clinic',16,40.00,1,0),
(2,'Autumn Member Classic',DATE_ADD(CURDATE(),INTERVAL 9 DAY),480,5,'Scramble',72,95.00,1,0),
(3,'Couples Twilight Scramble',DATE_ADD(CURDATE(),INTERVAL 12 DAY),960,2,'Best ball',32,60.00,1,0),
(4,'Club Championship',DATE_SUB(CURDATE(),INTERVAL 5 DAY),450,6,'Stroke play',64,120.00,0,0);

INSERT IGNORE INTO event_registrations (event_id,name) VALUES
(1,'Ava Walsh'),(1,'Liam Becker'),(1,'Noah Morales'),(1,'Emma Price'),(1,'Oliver Hughes'),
(1,'Mia Ford'),(1,'Lucas Ward'),(1,'Zoe Ellis'),(1,'Owen Grant'),(1,'Ivy Shaw'),(1,'Leo Lane'),
(2,'James Carter'),(2,'Mina Lee'),(2,'Raj Patel'),(2,'Sofia Kim'),(2,'Alejandro Gomez'),
(2,'Thanh Nguyen'),(2,'Ava Walsh'),(2,'Liam Becker'),(2,'Noah Morales'),
(3,'Ava Walsh'),(3,'Liam Becker'),(3,'Noah Morales'),(3,'Emma Price'),(3,'Oliver Hughes'),
(3,'Mia Ford'),(3,'Lucas Ward'),(3,'Zoe Ellis'),(3,'Owen Grant'),(3,'Ivy Shaw'),
(4,'James Carter'),(4,'Mina Lee'),(4,'Daniel Brown'),(4,'Priya Shah'),(4,'Alejandro Gomez');

-- Seed transactions
INSERT IGNORE INTO transactions (id,txn_date,txn_time,name,description,type,method,amount,status) VALUES
('TX-48211',CURDATE(),360,'Mina Lee','18 holes · 4 players · Hole 1 Tee 7:00 AM','Green fee','Member account',340.00,'Paid'),
('TX-48212',CURDATE(),360,'Raj Patel','18 holes · 3 players · Hole 10 Tee 7:00 AM','Green fee','Member account',255.00,'Paid'),
('TX-48213',CURDATE(),370,'James Carter','18 holes · 2 players · Hole 1 Tee 7:10 AM','Green fee','Member account',170.00,'Paid'),
('TX-48214',CURDATE(),380,'Daniel Brown','18 holes · 4 players · Hole 1 Tee 7:20 AM','Green fee','Member account',340.00,'Paid'),
('TX-48215',CURDATE(),420,'Priya Shah','18 holes · 3 players · Hole 1 Tee 8:00 AM','Green fee','Member account',255.00,'Paid'),
('TX-48216',CURDATE(),430,'Alejandro Gomez','18 holes · 4 players · Hole 10 Tee 8:10 AM','Green fee','Visa',340.00,'Paid'),
('TX-48217',CURDATE(),540,'Sofia Kim','Standard membership · annual','Membership','Visa',1200.00,'Pending'),
('TX-48218',DATE_SUB(CURDATE(),INTERVAL 1 DAY),600,'Thanh Nguyen','Cancelled · Yesterday 10:00 AM','Green fee','Visa',170.00,'Refunded'),
('TX-48219',DATE_SUB(CURDATE(),INTERVAL 2 DAY),720,'Priya Shah','Autumn Member Classic entry','Event','Member account',95.00,'Paid'),
('TX-48220',CURDATE(),450,'Ethan Novak','Clubhouse lunch','Food & bev','Visa',45.00,'Paid'),
('TX-48221',CURDATE(),500,'Carmen Diaz','Titleist Pro V1 · dozen','Pro shop','Mastercard',52.00,'Paid');

-- Seed notifications
INSERT IGNORE INTO notifications (id,title,description,page,focus_data,is_read,tone) VALUES
(1,'Members awaiting approval','Sofia Kim and Kenji Ito requested membership','Users','{"memberId":4}',0,'wait'),
(2,'Waitlist demand','2 players waiting for today after 12 PM','Waitlist',NULL,0,'wait'),
(3,'Event almost full','Couples Twilight Scramble · 30/32 registered','Events','{"eventId":3}',0,'forest'),
(4,'Payment pending','Membership fee for Sofia Kim — $1,200','Payments',NULL,1,'gray');

-- Seed activity log
INSERT IGNORE INTO activity_log (id,title,description,tone) VALUES
(1,'New booking','Alejandro Gomez · Sat 8:30 AM · 4 players','forest'),
(2,'Waitlist joined','Kenji Ito · Today 2:40 PM','wait'),
(3,'Cancellation','Thanh Nguyen · Yesterday 10:00 AM · refunded $170','danger'),
(4,'Member pending','Sofia Kim requested Standard membership','sand'),
(5,'Payment received','$340 · James Carter','forest');

-- --------------------------------------------------------
-- Club News & Announcements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS club_news (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  category VARCHAR(50) NOT NULL DEFAULT 'General',
  badge_text VARCHAR(50) NULL,
  summary VARCHAR(255) NOT NULL,
  content TEXT NULL,
  image_url VARCHAR(500) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  published_at DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_published (is_published, published_at),
  INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO club_news (id, title, category, badge_text, summary, content, image_url, is_published, published_at) VALUES
(1, 'Autumn Member Classic — registration is open', 'Event', 'Event · Nov 9', 'Shotgun start 8 AM · 72 spots left', 'The annual Autumn Member Classic is open for registration. 18 holes of championship stroke play followed by a harvest dinner and awards at the clubhouse. Register your team early before capacity is reached.', 'https://images.unsplash.com/photo-1761141987961-ed96a841fe21?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 1, CURDATE()),
(2, 'Aerification Schedule for Front 9 Fairways', 'Course Update', 'Course Update', 'Scheduled for next Tuesday 6 AM - 12 PM', 'Our greenskeeping crew will be conducting seasonal aerification on Holes 1 through 9 next Tuesday morning. Carts will be restricted to the cart path during this maintenance window. The back 9 remains fully operational.', 'https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 1, CURDATE()),
(3, 'New Pro Shop Arrivals: Titleist & Callaway Gear', 'Pro Shop', 'Pro Shop · 15% Off', 'Exclusive discount for Gold & Silver tier members', 'Check out the newly stocked premium drivers, bags, and golf apparel in the Pro Shop. Gold and Silver members enjoy an automatic 15% discount at checkout through the end of the month.', 'https://images.unsplash.com/photo-1535131749006-b7f58c99034b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 1, CURDATE());

-- --------------------------------------------------------
-- Member Payment Cards
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS member_cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  brand VARCHAR(30) NOT NULL DEFAULT 'Visa',
  card_holder VARCHAR(100) NOT NULL DEFAULT '',
  last4 VARCHAR(4) NOT NULL,
  exp_month VARCHAR(2) NOT NULL DEFAULT '12',
  exp_year VARCHAR(4) NOT NULL DEFAULT '2028',
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_member (member_id),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Payment Cards
INSERT IGNORE INTO member_cards (id, member_id, brand, card_holder, last4, exp_month, exp_year, is_default) VALUES
(1, 15, 'Visa', 'Monabbor Hossen', '4242', '08', '2028', 1),
(2, 15, 'Mastercard', 'Monabbor Hossen', '5555', '11', '2029', 0),
(3, 15, 'Amex', 'Monabbor Hossen', '3782', '05', '2027', 0),
(4, 1, 'Visa', 'James Carter', '9012', '04', '2028', 1),
(5, 1, 'Mastercard', 'James Carter', '4410', '10', '2029', 0);

-- Sample Waitlist Entries
INSERT IGNORE INTO waitlist (id, name, tee_date, tee_slot, party, holes, status, offered_slot, member_id) VALUES
(1, 'Monabbor Hossen', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 480, 2, 18, 'Offered', 480, 15),
(2, 'Monabbor Hossen', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 540, 2, 18, 'Waiting', NULL, 15),
(3, 'Raj Patel', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 520, 4, 18, 'Waiting', NULL, 3);


