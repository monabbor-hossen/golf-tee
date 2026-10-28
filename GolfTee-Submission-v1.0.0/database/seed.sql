-- WARNING: Demo credentials below. DELETE these rows and create real staff accounts before production deployment.
-- ========================================================
-- GolfTee - Golf Club Booking & Course Management System
-- DEMO SEED DATA (DML)
-- Pre-loaded sample data for testing and demonstrations
-- ========================================================

USE `golf_admin`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- 1. Initial Settings Configuration
-- --------------------------------------------------------
INSERT INTO `settings` (`key`, `value`) VALUES
('course_name', 'Pine Valley Golf Club'),
('phone', '+1 (856) 783-3000'),
('address', '1 E Atlantic Ave, Pine Valley, NJ 08021'),
('timezone', 'America/New_York'),
('currency', 'USD'),
('course_location', 'Pine Valley, NJ'),
('course_lat', '39.7898'),
('course_lng', '-74.9735'),
('green_speed', '10.5'),
('speed_desc', 'Championship Pace'),
('carts', 'Open'),
('carts_desc', 'Carts on fairways (90° rule)'),
('weather_unit', 'F'),
('first_tee', '420'),
('last_tee', '1080'),
('interval', '10'),
('max_party', '4'),
('advance_days', '14'),
('cancel_hours', '24'),
('price_weekday_18', '85.00'),
('price_weekday_9', '48.00'),
('price_weekend_18', '110.00'),
('price_weekend_9', '62.00'),
('price_twilight_18', '55.00'),
('price_twilight_9', '35.00'),
('twilight_start', '900'),
('flag_twilight', '1'),
('flag_waitlist', '1'),
('flag_nine', '1'),
('flag_guest', '1'),
('flag_deposit', '0'),
('flag_dynamic', '1'),
('flag_auto_offer', '1'),
('flag_n_book', '1'),
('flag_n_cancel', '1'),
('flag_n_wait', '1'),
('flag_n_daily', '0'),
('flag_n_sms', '0'),
('open_days', '["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- --------------------------------------------------------
-- 2. Staff Administrator
-- Default Admin Account (password hashed with bcrypt)
-- IMPORTANT: After importing this schema, immediately set admin password via
-- admin-php/set-password.php or the admin panel. Default credentials are disabled for security.
-- --------------------------------------------------------
INSERT INTO `staff` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `password_must_change`) VALUES
(1, 'Marcus Vance', 'admin@pinevalley.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', 'Owner', 1, 1),
(2, 'Sarah Jenkins', 'sjenkins@pinevalley.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', 'Pro', 1, 1),
(3, 'David Torres', 'dtorres@pinevalley.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', 'Admin', 1, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- --------------------------------------------------------
-- 3. Club Members
-- Pre-configured member profiles with initial activation requirements
-- --------------------------------------------------------
INSERT INTO `members` (`id`, `name`, `email`, `password_hash`, `phone`, `tier`, `hcp`, `rounds`, `last_play`, `joined`, `status`, `spend`, `next_tee`) VALUES
(1, 'James Carter', 'james.carter@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 892-1204', 'Gold', 7.4, 48, 'Tue, Oct 20', 'Member since 2022', 'Active', 5420.00, 'Sat, Oct 24 · 9:10 AM · 4 players'),
(2, 'Mina Lee', 'mina.lee@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 301-4491', 'Gold', 5.2, 62, 'Mon, Oct 19', 'Member since 2021', 'Active', 6980.00, 'Tomorrow · 8:20 AM · 2 players'),
(3, 'Raj Patel', 'r.patel@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 781-9023', 'Silver', 14.2, 23, 'Sun, Oct 11', 'Member since 2023', 'Active', 2140.00, ''),
(4, 'Sofia Kim', 'sofia.k@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 440-1928', 'Standard', 21.0, 6, 'Sat, Sep 28', 'Member since 2024', 'Active', 410.00, ''),
(5, 'Alejandro Gomez', 'agomez@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 672-0034', 'Silver', 11.7, 31, 'Fri, Oct 16', 'Member since 2023', 'Active', 2980.00, ''),
(6, 'Thanh Nguyen', 'thanh.n@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 881-2231', 'Standard', 18.3, 12, 'Sun, Oct 04', 'Member since 2024', 'Active', 960.00, ''),
(7, 'Daniel Brown', 'd.brown@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 219-5502', 'Gold', 3.9, 88, 'Thu, Oct 15', 'Member since 2020', 'Active', 11250.00, 'Thu, Oct 31 · 11:30 AM · 4 players'),
(8, 'Lucia Rossi', 'lucia.rossi@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 399-4412', 'Silver', 16.5, 19, 'Sun, Oct 18', 'Member since 2023', 'Active', 1720.00, ''),
(9, 'Kenji Ito', 'kenji.ito@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 702-8819', 'Standard', 24.8, 4, 'Sat, Sep 14', 'Member since 2024', 'Active', 220.00, ''),
(10, 'Priya Shah', 'priya.s@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 914-6630', 'Gold', 9.6, 54, 'Mon, Oct 12', 'Member since 2022', 'Active', 6040.00, ''),
(15, 'Alex Morgan', 'alex.morgan@mail.com', '$2y$10$UNUSABLE_ACCOUNT_SETUP_REQUIRED_ON_FIRST_LOGIN_abcdefghijklm', '+1 (555) 234-8901', 'Gold', 8.8, 16, 'Sun, Oct 18', 'Member since 2024', 'Active', 1890.00, 'Tomorrow · 8:20 AM · 2 players')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- --------------------------------------------------------
-- 4. Member Payment Cards
-- --------------------------------------------------------
INSERT INTO `member_cards` (`id`, `member_id`, `brand`, `card_holder`, `last4`, `exp_month`, `exp_year`, `is_default`) VALUES
(1, 15, 'Visa', 'Alex Morgan', '4242', '08', '2028', 1),
(2, 15, 'Mastercard', 'Alex Morgan', '5555', '11', '2029', 0),
(3, 15, 'Amex', 'Alex Morgan', '3782', '05', '2027', 0),
(4, 1, 'Visa', 'James Carter', '9012', '04', '2028', 1),
(5, 1, 'Mastercard', 'James Carter', '4410', '10', '2029', 0)
ON DUPLICATE KEY UPDATE `brand` = VALUES(`brand`);

-- --------------------------------------------------------
-- 5. Bookings (Upcoming + Past with Scores)
-- --------------------------------------------------------
INSERT INTO `bookings` (`id`, `tee_date`, `tee_slot`, `tee`, `name`, `party`, `holes`, `status`, `notes`, `member_id`) VALUES
(1, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), 500, 0, 'Alex Morgan', 2, 18, 'Booked', '{"partners":["Mina Lee"],"addons":["cart","range_balls"],"card":"v","total":193.00,"score":null}', 15),
(2, DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 570, 0, 'Alex Morgan', 4, 18, 'Booked', '{"partners":["James Carter","Raj Patel","Daniel Brown"],"addons":["cart"],"card":"v","total":420.00,"score":null}', 15),
(3, DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 460, 0, 'Alex Morgan', 2, 18, 'Checked in', '{"partners":["Lucia Rossi"],"addons":["cart"],"card":"v","total":170.00,"score":78}', 15),
(4, DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), 530, 0, 'Alex Morgan', 4, 18, 'Checked in', '{"partners":["Priya Shah","Kenji Ito","Alejandro Gomez"],"addons":["cart"],"card":"m","total":340.00,"score":82}', 15),
(5, DATE_SUB(CURRENT_DATE, INTERVAL 18 DAY), 610, 0, 'Alex Morgan', 2, 18, 'Checked in', '{"partners":["Daniel Brown"],"addons":["cart"],"card":"v","total":170.00,"score":85}', 15),
(6, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 550, 0, 'James Carter', 4, 18, 'Booked', '{"partners":["Daniel Brown","Sofia Kim","Alejandro Gomez"],"addons":["cart"],"card":"v","total":420.00}', 1),
(7, CURRENT_DATE, 480, 0, 'Daniel Brown', 2, 18, 'Booked', '{"partners":["Thanh Nguyen"],"addons":["cart"],"card":"v","total":170.00}', 7)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- --------------------------------------------------------
-- 6. Waitlist Queue
-- --------------------------------------------------------
INSERT INTO `waitlist` (`id`, `name`, `tee_date`, `tee_slot`, `party`, `holes`, `status`, `offered_slot`, `member_id`) VALUES
(1, 'Alex Morgan', DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 480, 2, 18, 'Offered', 480, 15),
(2, 'Alex Morgan', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), 540, 2, 18, 'Waiting', NULL, 15),
(3, 'Raj Patel', DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 520, 4, 18, 'Waiting', NULL, 3)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- --------------------------------------------------------
-- 7. Caddies Roster
-- --------------------------------------------------------
INSERT INTO `caddies` (`id`, `name`, `phone`, `experience_years`, `specialty`, `rate_per_round`, `rating`, `status`, `notes`) VALUES
(1, 'Thomas Mitchell', '+1 (555) 712-4091', 8, 'Green reading & wind calculation', 65.00, 4.9, 'Available', 'Master Caddie with tournament loop experience.'),
(2, 'Sean O\'Connor', '+1 (555) 834-1190', 5, 'Club selection & yardage accuracy', 55.00, 4.8, 'Available', 'Former college player, excellent local knowledge.'),
(3, 'Liam Brooks', '+1 (555) 902-3341', 3, 'Course navigation & putting lines', 50.00, 4.7, 'Available', 'Punctual, friendly, highly recommended by members.'),
(4, 'Marcus Chen', '+1 (555) 621-8890', 4, 'Bunker play strategy & pace of play', 50.00, 4.6, 'Available', 'Great communicator and tireless bag carrier.'),
(5, 'Ryan Gallagher', '+1 (555) 441-2900', 6, 'Short game advice & mental focus', 60.00, 4.9, 'Available', 'Top requested caddie for Saturday morning loops.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- --------------------------------------------------------
-- 8. Club News Feed
-- --------------------------------------------------------
INSERT INTO `club_news` (`id`, `title`, `category`, `badge_text`, `summary`, `content`, `image_url`, `is_published`, `published_at`) VALUES
(1, 'Autumn Member Classic — registration is open', 'Event', 'Event · Nov 9', 'Shotgun start 8 AM · 72 spots left', 'The annual Autumn Member Classic is open for registration. 18 holes of championship stroke play followed by a harvest dinner and awards at the clubhouse. Register your team early before capacity is reached.', 'https://images.unsplash.com/photo-1761141987961-ed96a841fe21?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 1, CURRENT_DATE),
(2, 'Aerification Schedule for Front 9 Fairways', 'Course Update', 'Course Update', 'Scheduled for next Tuesday 6 AM - 12 PM', 'Our greenskeeping crew will be conducting seasonal aerification on Holes 1 through 9 next Tuesday morning. Carts will be restricted to the cart path during this maintenance window. The back 9 remains fully operational.', 'https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 1, CURRENT_DATE),
(3, 'New Pro Shop Arrivals: Titleist & Callaway Gear', 'Pro Shop', 'Pro Shop · 15% Off', 'Exclusive discount for Gold & Silver tier members', 'Check out the newly stocked premium drivers, bags, and golf apparel in the Pro Shop. Gold and Silver members enjoy an automatic 15% discount at checkout through the end of the month.', 'https://images.unsplash.com/photo-1535131749006-b7f58c99034b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 1, CURRENT_DATE),
(4, 'PGA Head Pro Clinic: Short Game Mastery', 'Clinic', 'Clinic · Sat 10 AM', 'Master bunker play and green reading with Head Professional Mark Williams.', 'Join PGA Head Professional Mark Williams this Saturday at 10 AM on the practice short-game facility. Learn high-spin wedge shots, flop techniques, and consistency drills.', 'https://images.unsplash.com/photo-1592919505780-303950717480?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 1, CURRENT_DATE)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- --------------------------------------------------------
-- 9. Events & Tournaments
-- --------------------------------------------------------
INSERT INTO `events` (`id`, `name`, `event_date`, `start_slot`, `hours`, `format`, `capacity`, `fee`, `is_open`, `is_blocked`) VALUES
(1, 'Junior Golf Clinic', DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), 600, 2, 'Clinic', 16, 40.00, 1, 0),
(2, 'Autumn Member Classic', DATE_ADD(CURRENT_DATE, INTERVAL 12 DAY), 480, 5, 'Scramble', 72, 95.00, 1, 0),
(3, 'Couples Twilight Scramble', DATE_ADD(CURRENT_DATE, INTERVAL 16 DAY), 960, 2, 'Best ball', 32, 60.00, 1, 0),
(4, 'Club Championship 2026', DATE_ADD(CURRENT_DATE, INTERVAL 25 DAY), 450, 6, 'Stroke play', 64, 120.00, 1, 0)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- --------------------------------------------------------
-- 10. Financial Transactions Ledger
-- --------------------------------------------------------
INSERT INTO `transactions` (`id`, `txn_date`, `txn_time`, `name`, `description`, `type`, `method`, `amount`, `status`, `ref`, `member_id`) VALUES
('TXN-829140', CURRENT_DATE, 500, 'Alex Morgan', 'Green fee: 18 Holes (2 Players)', 'Green fee', 'Visa •••• 4242', 193.00, 'Paid', 'BK-1', 15),
('TXN-719320', DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 460, 'Alex Morgan', 'Green fee: 18 Holes Weekend (2 Players)', 'Green fee', 'Visa •••• 4242', 170.00, 'Paid', 'BK-3', 15),
('TXN-654810', DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), 720, 'Alex Morgan', 'Pro Shop: Titleist Pro V1x Balls (1 Dozen)', 'Pro shop', 'Visa •••• 4242', 58.00, 'Paid', 'PS-912', 15),
('TXN-590120', DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), 530, 'Alex Morgan', 'Green fee: 18 Holes (4 Players)', 'Green fee', 'Mastercard •••• 5555', 340.00, 'Paid', 'BK-4', 15)
ON DUPLICATE KEY UPDATE `amount` = VALUES(`amount`);

-- --------------------------------------------------------
-- 11. System Notifications
-- --------------------------------------------------------
INSERT INTO `notifications` (`id`, `title`, `description`, `tone`, `page`, `focus_data`, `is_read`) VALUES
(1, 'Weather cycle auto-refreshed', 'Live course conditions updated from satellite feed (78° F · 2 mph wind).', 'forest', 'Settings', '{"tab":"Course"}', 0),
(2, 'New Booking Confirmed', 'Alex Morgan booked 8:20 AM for tomorrow (Hole 1, 2 players).', 'forest', 'Master Grid', '{"date":"2026-10-23"}', 0),
(3, 'Waitlist Opportunity Offered', 'Member Alex Morgan was offered priority slot for Saturday 8:00 AM.', 'wait', 'Waitlist', '{}', 0),
(4, 'Monthly Maintenance Scheduled', 'Front 9 aerification scheduled for next Tuesday 6 AM - 12 PM.', 'gray', 'Settings', '{"tab":"Course"}', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

SET FOREIGN_KEY_CHECKS = 1;
