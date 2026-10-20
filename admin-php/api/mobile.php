<?php
declare(strict_types=1);

/**
 * Golf Tee Mobile App — Dedicated REST JSON API
 * Connects the Flutter Mobile Application directly to the golf_admin MySQL database.
 */

// Enable CORS for mobile devices, emulators, and web
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json; charset=utf-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Domain\PricingCalculator;
use App\Domain\SlotFormatter;

try {
    $pdo = Database::pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$rawInput = file_get_contents('php://input');
$jsonBody = !empty($rawInput) ? json_decode($rawInput, true) : [];
if (is_array($jsonBody) && !empty($jsonBody['action']) && empty($action)) {
    $action = $jsonBody['action'];
}

// Default authenticated member in demo is James Carter (ID: 1)
$currentMemberId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : (isset($jsonBody['member_id']) ? (int)$jsonBody['member_id'] : 1);

switch ($action) {
    case 'home':
        handleHome($pdo, $currentMemberId);
        break;

    case 'slots':
        handleSlots($pdo);
        break;

    case 'book':
        handleBook($pdo, $jsonBody, $currentMemberId);
        break;

    case 'bookings':
        handleBookings($pdo, $currentMemberId);
        break;

    case 'booking_detail':
        handleBookingDetail($pdo, (int)($_GET['id'] ?? $jsonBody['id'] ?? 0));
        break;

    case 'cancel_booking':
        handleCancelBooking($pdo, (int)($_GET['id'] ?? $jsonBody['id'] ?? 0), $currentMemberId);
        break;

    case 'join_waitlist':
        handleJoinWaitlist($pdo, $jsonBody, $currentMemberId);
        break;

    case 'leave_waitlist':
        handleLeaveWaitlist($pdo, (int)($_GET['id'] ?? $jsonBody['id'] ?? 0), $currentMemberId);
        break;

    case 'login':
        handleLogin($pdo, $jsonBody);
        break;

    case 'members':
        handleMembers($pdo);
        break;

    case 'weather':
    case 'conditions':
        handleWeather($pdo);
        break;

    case 'profile':
        handleProfile($pdo, $currentMemberId);
        break;

    case 'save_score':
        handleSaveScore($pdo, $jsonBody, $currentMemberId);
        break;

    default:
        echo json_encode([
            'status' => 'ok',
            'app' => 'Golf Tee Mobile API',
            'version' => '1.0.0',
            'endpoints' => [
                'GET ?action=home',
                'GET ?action=slots&date=YYYY-MM-DD&holes=18',
                'POST ?action=book',
                'GET ?action=bookings',
                'GET ?action=booking_detail&id=X',
                'POST ?action=cancel_booking',
                'POST ?action=join_waitlist',
                'POST ?action=leave_waitlist',
                'GET ?action=profile',
                'POST ?action=save_score'
            ]
        ]);
        break;
}

// ─────────────────────────────────────────────────────────────
// Action Handlers
// ─────────────────────────────────────────────────────────────

function handleHome(PDO $pdo, int $memberId): void
{
    // 1. Member Profile
    $stmt = $pdo->prepare("SELECT id, name, email, phone, tier, hcp, rounds, last_play, joined, status, spend, next_tee FROM members WHERE id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();
    if (!$member) {
        $member = [
            'id' => 1,
            'name' => 'James Carter',
            'email' => 'james.carter@mail.com',
            'tier' => 'Gold',
            'hcp' => 8.4,
            'rounds' => 42,
            'spend' => 4820.00,
            'status' => 'Active',
            'joined' => 'Mar 2021'
        ];
    }

    // 2. Next Upcoming Booking
    $stmt = $pdo->prepare("
        SELECT id, tee_date, tee_slot, tee, name, party, holes, status, notes
        FROM bookings
        WHERE member_id = ? AND tee_date >= CURDATE()
        ORDER BY tee_date ASC, tee_slot ASC
        LIMIT 1
    ");
    $stmt->execute([$memberId]);
    $nextBooking = $stmt->fetch();

    $formattedNext = null;
    if ($nextBooking) {
        $formattedNext = [
            'id' => (int)$nextBooking['id'],
            'date' => $nextBooking['tee_date'],
            'min' => (int)$nextBooking['tee_slot'],
            'time' => SlotFormatter::toDisplayTime((int)$nextBooking['tee_slot']),
            'holes' => (int)$nextBooking['holes'],
            'players' => (int)$nextBooking['party'],
            'tee' => (int)$nextBooking['tee'] === 0 ? 'Hole 1' : 'Hole 10',
            'status' => 'upcoming'
        ];
    }

    // 3. Top Waitlist Alert
    $stmt = $pdo->prepare("
        SELECT id, tee_date, tee_slot, party, holes, status, offered_slot
        FROM waitlist
        WHERE member_id = ? AND tee_date >= CURDATE()
        ORDER BY added_at DESC
        LIMIT 1
    ");
    $stmt->execute([$memberId]);
    $waitItem = $stmt->fetch();

    $readyWaitlist = null;
    if ($waitItem) {
        $readyWaitlist = [
            'id' => (int)$waitItem['id'],
            'date' => $waitItem['tee_date'],
            'min' => (int)$waitItem['tee_slot'],
            'time' => SlotFormatter::toDisplayTime((int)$waitItem['tee_slot']),
            'holes' => (int)$waitItem['holes'],
            'players' => (int)$waitItem['party'],
            'position' => $waitItem['status'] === 'Offered' ? 1 : 2
        ];
    }

    // 4. Latest News Bulletin
    $news = null;
    try {
        $stmt = $pdo->query("SELECT id, title, category, badge_text, summary, image_url, published_at FROM club_news WHERE is_published = 1 ORDER BY published_at DESC LIMIT 1");
        $news = $stmt ? $stmt->fetch() : null;
    } catch (Throwable $e) {
        // Fallback default announcement matching Figma design
        $news = [
            'id' => 1,
            'title' => 'Autumn Member Classic — registration is open',
            'category' => 'Event',
            'badge_text' => 'Event · Nov 9',
            'summary' => 'Shotgun start 8 AM · 72 spots left',
            'image_url' => 'https://images.unsplash.com/photo-1761141987961-ed96a841fe21?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600'
        ];
    }
    if (!$news) {
        $news = [
            'id' => 1,
            'title' => 'Autumn Member Classic — registration is open',
            'category' => 'Event',
            'badge_text' => 'Event · Nov 9',
            'summary' => 'Shotgun start 8 AM · 72 spots left',
            'image_url' => 'https://images.unsplash.com/photo-1761141987961-ed96a841fe21?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600'
        ];
    }

    // 5. Course conditions / metadata
    try {
        $weatherService = new \App\Services\WeatherService();
        $conditions = $weatherService->getConditions();
    } catch (\Throwable $e) {
        $conditions = [
            'weather' => '68°',
            'weather_desc' => 'Sunny',
            'wind' => '6 mph',
            'wind_desc' => 'NW wind',
            'green_speed' => '10.5',
            'speed_desc' => 'Green speed',
            'carts' => 'Open',
            'carts_desc' => 'Carts on fairways',
            'updated_at' => 'Updated ' . date('g:i A')
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'member' => $member,
        'next_booking' => $formattedNext,
        'ready_waitlist' => $readyWaitlist,
        'news' => $news,
        'conditions' => $conditions,
        'course' => 'Pine Valley GC'
    ]);
}

function handleWeather(PDO $pdo): void
{
    $location = $_GET['location'] ?? null;
    $lat = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float)$_GET['lat'] : null;
    $lng = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float)$_GET['lng'] : null;
    $unit = $_GET['unit'] ?? 'F';
    $force = isset($_GET['refresh']) && $_GET['refresh'] === '1';
    try {
        $weatherService = new \App\Services\WeatherService();
        $conditions = $weatherService->getConditions($location, $unit, $force, $lat, $lng);
        echo json_encode([
            'status' => 'ok',
            'conditions' => $conditions
        ]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

function handleSlots(PDO $pdo): void
{
    $date = $_GET['date'] ?? date('Y-m-d');
    $holes = isset($_GET['holes']) ? (int)$_GET['holes'] : 18;
    if ($holes !== 9) $holes = 18;

    // Fetch blocked slots for this date
    $stmt = $pdo->prepare("SELECT tee_slot, tee FROM blocked_slots WHERE tee_date = ?");
    $stmt->execute([$date]);
    $blocked = [];
    while ($row = $stmt->fetch()) {
        $blocked[(int)$row['tee_slot']] = true;
    }

    // Fetch booked slots for this date
    $stmt = $pdo->prepare("SELECT tee_slot, tee, name, party, status FROM bookings WHERE tee_date = ?");
    $stmt->execute([$date]);
    $booked = [];
    while ($row = $stmt->fetch()) {
        $booked[(int)$row['tee_slot']] = $row;
    }

    // Fetch waitlist entries for this date
    $stmt = $pdo->prepare("SELECT tee_slot FROM waitlist WHERE tee_date = ?");
    $stmt->execute([$date]);
    $waitlist = [];
    while ($row = $stmt->fetch()) {
        $waitlist[(int)$row['tee_slot']] = true;
    }

    // Generate 10-minute slots from 07:00 (420) to 17:50 (1070) = 66 slots
    $isToday = ($date === date('Y-m-d'));
    $nowMinutes = (int)date('H') * 60 + (int)date('i');

    $isWeekend = in_array(date('N', strtotime($date)), ['6', '7'], true);

    $slots = [];
    for ($m = 420; $m <= 1070; $m += 10) {
        $price = calculateSlotPrice($holes, $isWeekend, $m);

        $state = 'available';
        if ($isToday && $m < $nowMinutes) {
            $state = 'locked';
        } elseif (isset($blocked[$m])) {
            $state = 'locked';
        } elseif (isset($booked[$m])) {
            $state = 'booked';
        } elseif (isset($waitlist[$m])) {
            $state = 'waitlist';
        } else {
            // Simulated realistic bookings pattern matching Figma design
            $hash = ((strtotime($date) + 20) * 131 + $m * 7 + ($holes === 9 ? 3 : 0)) % 17;
            if ($hash < 4) {
                $state = 'booked';
            } elseif ($hash < 6) {
                $state = 'waitlist';
            } elseif ($hash === 6) {
                $state = 'locked';
            }
        }

        $slots[] = [
            'min' => $m,
            'time' => SlotFormatter::toDisplayTime($m),
            'price' => $price,
            'state' => $state,
            'tee' => $m % 20 === 0 ? 'Hole 1' : 'Hole 10'
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'date' => $date,
        'holes' => $holes,
        'is_weekend' => $isWeekend,
        'slots' => $slots
    ]);
}

function handleBook(PDO $pdo, array $body, int $memberId): void
{
    $date = $body['date'] ?? date('Y-m-d');
    $slot = (int)($body['min'] ?? 480);
    $holes = (int)($body['holes'] ?? 18);
    $players = (int)($body['players'] ?? 2);
    $tee = (int)($body['tee'] ?? ($slot % 20 === 0 ? 0 : 1));
    $name = $body['name'] ?? 'James Carter';
    $partners = $body['partners'] ?? [];
    $addons = $body['addons'] ?? ['cart'];
    $total = (float)($body['total'] ?? 170.00);
    $card = $body['card'] ?? 'v';

    $notes = json_encode([
        'partners' => $partners,
        'addons' => $addons,
        'card' => $card,
        'total' => $total
    ]);

    $pdo->beginTransaction();
    try {
        // Check if slot already booked
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE tee_date = ? AND tee_slot = ? AND tee = ?");
        $stmt->execute([$date, $slot, $tee]);
        if ($stmt->fetch()) {
            // Already booked, update existing or generate alternate
            $slot += 10;
        }

        $stmt = $pdo->prepare("
            INSERT INTO bookings (tee_date, tee_slot, tee, name, party, holes, status, notes, member_id)
            VALUES (?, ?, ?, ?, ?, ?, 'Booked', ?, ?)
        ");
        $stmt->execute([$date, $slot, $tee, $name, $players, $holes, $notes, $memberId]);
        $bookingId = (int)$pdo->lastInsertId();

        // Record in transactions ledger
        $txnId = 'TXN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $nowMin = (int)date('H') * 60 + (int)date('i');
        $stmt = $pdo->prepare("
            INSERT INTO transactions (id, txn_date, txn_time, name, description, type, method, amount, status, ref, member_id)
            VALUES (?, ?, ?, ?, ?, 'Green fee', 'Credit Card', ?, 'Paid', ?, ?)
        ");
        $stmt->execute([
            $txnId,
            $date,
            $nowMin,
            $name,
            "Tee time reservation ($holes holes, $players players)",
            $total,
            (string)$bookingId,
            $memberId
        ]);

        // Remove from waitlist if this member was on waitlist for this slot
        $stmt = $pdo->prepare("DELETE FROM waitlist WHERE member_id = ? AND tee_date = ? AND tee_slot = ?");
        $stmt->execute([$memberId, $date, $slot]);

        // Update member's spend and rounds count
        $stmt = $pdo->prepare("UPDATE members SET rounds = rounds + 1, spend = spend + ? WHERE id = ?");
        $stmt->execute([$total, $memberId]);

        $pdo->commit();

        echo json_encode([
            'status' => 'ok',
            'message' => 'Booking confirmed successfully',
            'booking' => [
                'id' => $bookingId,
                'date' => $date,
                'min' => $slot,
                'time' => SlotFormatter::toDisplayTime($slot),
                'holes' => $holes,
                'players' => $players,
                'partners' => $partners,
                'addons' => $addons,
                'total' => $total,
                'card' => $card,
                'tee' => $tee === 0 ? 'Hole 1' : 'Hole 10',
                'status' => 'upcoming'
            ]
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Booking failed: ' . $e->getMessage()]);
    }
}

function handleBookings(PDO $pdo, int $memberId): void
{
    // Upcoming
    $stmt = $pdo->prepare("
        SELECT id, tee_date, tee_slot, tee, name, party, holes, status, notes
        FROM bookings
        WHERE member_id = ? AND tee_date >= CURDATE()
        ORDER BY tee_date ASC, tee_slot ASC
    ");
    $stmt->execute([$memberId]);
    $upcoming = [];
    while ($r = $stmt->fetch()) {
        $extra = !empty($r['notes']) ? json_decode($r['notes'], true) : [];
        $upcoming[] = [
            'id' => (int)$r['id'],
            'date' => $r['tee_date'],
            'min' => (int)$r['tee_slot'],
            'time' => SlotFormatter::toDisplayTime((int)$r['tee_slot']),
            'holes' => (int)$r['holes'],
            'players' => (int)$r['party'],
            'tee' => (int)$r['tee'] === 0 ? 'Hole 1' : 'Hole 10',
            'partners' => $extra['partners'] ?? [],
            'addons' => $extra['addons'] ?? ['cart'],
            'total' => (float)($extra['total'] ?? 170.00),
            'card' => $extra['card'] ?? 'v',
            'status' => 'upcoming'
        ];
    }

    // Waitlist
    $stmt = $pdo->prepare("
        SELECT id, tee_date, tee_slot, party, holes, status, offered_slot
        FROM waitlist
        WHERE member_id = ? AND tee_date >= CURDATE()
        ORDER BY tee_date ASC, tee_slot ASC
    ");
    $stmt->execute([$memberId]);
    $waitlist = [];
    while ($r = $stmt->fetch()) {
        $waitlist[] = [
            'id' => (int)$r['id'],
            'date' => $r['tee_date'],
            'min' => (int)$r['tee_slot'],
            'time' => SlotFormatter::toDisplayTime((int)$r['tee_slot']),
            'holes' => (int)$r['holes'],
            'players' => (int)$r['party'],
            'position' => $r['status'] === 'Offered' ? 1 : 2
        ];
    }

    // Past
    $stmt = $pdo->prepare("
        SELECT id, tee_date, tee_slot, tee, name, party, holes, status, notes
        FROM bookings
        WHERE member_id = ? AND tee_date < CURDATE()
        ORDER BY tee_date DESC, tee_slot DESC
        LIMIT 10
    ");
    $stmt->execute([$memberId]);
    $past = [];
    while ($r = $stmt->fetch()) {
        $extra = !empty($r['notes']) ? json_decode($r['notes'], true) : [];
        $past[] = [
            'id' => (int)$r['id'],
            'date' => $r['tee_date'],
            'min' => (int)$r['tee_slot'],
            'time' => SlotFormatter::toDisplayTime((int)$r['tee_slot']),
            'holes' => (int)$r['holes'],
            'players' => (int)$r['party'],
            'tee' => (int)$r['tee'] === 0 ? 'Hole 1' : 'Hole 10',
            'total' => (float)($extra['total'] ?? 170.00),
            'status' => 'past',
            'score' => $extra['score'] ?? (80 + ((int)$r['id'] % 6))
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'upcoming' => $upcoming,
        'waitlist' => $waitlist,
        'past' => $past
    ]);
}

function handleBookingDetail(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $b = $stmt->fetch();
    if (!$b) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
        return;
    }

    $extra = !empty($b['notes']) ? json_decode($b['notes'], true) : [];
    $isUpcoming = (strtotime($b['tee_date']) >= strtotime(date('Y-m-d')));

    $detail = [
        'id' => (int)$b['id'],
        'date' => $b['tee_date'],
        'min' => (int)$b['tee_slot'],
        'time' => SlotFormatter::toDisplayTime((int)$b['tee_slot']),
        'holes' => (int)$b['holes'],
        'players' => (int)$b['party'],
        'tee' => (int)$b['tee'] === 0 ? 'Hole 1' : 'Hole 10',
        'partners' => $extra['partners'] ?? ['Mina Lee', 'Daniel Brown'],
        'addons' => $extra['addons'] ?? ['cart'],
        'total' => (float)($extra['total'] ?? 386.00),
        'card' => $extra['card'] ?? 'v',
        'status' => $isUpcoming ? 'upcoming' : 'past',
        'course' => 'Pine Valley GC',
        'qr_code' => 'PINEVALLEY-BOOKING-' . $b['id'] . '-' . $b['tee_date']
    ];

    echo json_encode(['status' => 'ok', 'booking' => $detail]);
}

function handleCancelBooking(PDO $pdo, int $id, int $memberId): void
{
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $b = $stmt->fetch();
    if (!$b) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
        return;
    }

    $extra = !empty($b['notes']) ? json_decode($b['notes'], true) : [];
    $total = (float)($extra['total'] ?? 170.00);

    // If canceled > 24 hours ahead, full refund; else 50%
    $daysDiff = (strtotime($b['tee_date']) - strtotime(date('Y-m-d'))) / 86400;
    $refundAmount = ($daysDiff >= 1) ? $total : round($total / 2, 2);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$id]);

        // Record refund in transactions
        $txnId = 'REF-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $nowMin = (int)date('H') * 60 + (int)date('i');
        $stmt = $pdo->prepare("
            INSERT INTO transactions (id, txn_date, txn_time, name, description, type, method, amount, status, ref, member_id)
            VALUES (?, CURDATE(), ?, ?, 'Refund: Cancelled Tee Time', 'Green fee', 'Credit Card', ?, 'Refunded', ?, ?)
        ");
        $stmt->execute([
            $txnId,
            $nowMin,
            $b['name'] ?? 'Member',
            -$refundAmount,
            (string)$id,
            $memberId
        ]);

        $pdo->commit();

        echo json_encode([
            'status' => 'ok',
            'message' => 'Booking cancelled and refund processed',
            'refund_amount' => $refundAmount
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Cancel failed: ' . $e->getMessage()]);
    }
}

function handleJoinWaitlist(PDO $pdo, array $body, int $memberId): void
{
    $date = $body['date'] ?? date('Y-m-d');
    $slot = (int)($body['min'] ?? 480);
    $holes = (int)($body['holes'] ?? 18);
    $players = (int)($body['players'] ?? 2);
    $name = $body['name'] ?? 'James Carter';

    $stmt = $pdo->prepare("
        INSERT INTO waitlist (name, tee_date, tee_slot, party, holes, status, member_id)
        VALUES (?, ?, ?, ?, ?, 'Waiting', ?)
    ");
    $stmt->execute([$name, $date, $slot, $players, $holes, $memberId]);
    $waitId = (int)$pdo->lastInsertId();

    echo json_encode([
        'status' => 'ok',
        'message' => "You've been added to the waitlist",
        'entry' => [
            'id' => $waitId,
            'date' => $date,
            'min' => $slot,
            'holes' => $holes,
            'players' => $players,
            'position' => 2
        ]
    ]);
}

function handleLeaveWaitlist(PDO $pdo, int $id, int $memberId): void
{
    $stmt = $pdo->prepare("DELETE FROM waitlist WHERE id = ? AND member_id = ?");
    $stmt->execute([$id, $memberId]);

    echo json_encode(['status' => 'ok', 'message' => 'Removed from waitlist']);
}

function handleProfile(PDO $pdo, int $memberId): void
{
    $stmt = $pdo->prepare("SELECT id, name, email, phone, tier, hcp, rounds, last_play, joined, status, spend FROM members WHERE id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();
    if (!$member) {
        $member = [
            'id' => 1,
            'name' => 'James Carter',
            'email' => 'james.carter@mail.com',
            'tier' => 'Gold',
            'hcp' => 8.4,
            'rounds' => 42,
            'spend' => 4820.00
        ];
    }

    $cards = [
        ['id' => 'v', 'brand' => 'Visa', 'last4' => '4242', 'exp' => '09/27', 'isDefault' => true],
        ['id' => 'm', 'brand' => 'Mastercard', 'last4' => '8810', 'exp' => '03/26', 'isDefault' => false]
    ];

    $hcpHistory = [10.2, 9.9, 9.7, 9.2, 9.0, 8.7, 8.4];

    echo json_encode([
        'status' => 'ok',
        'member' => $member,
        'cards' => $cards,
        'hcp_history' => $hcpHistory,
        'best_score' => 79
    ]);
}

function handleSaveScore(PDO $pdo, array $body, int $memberId): void
{
    $strokes = (int)($body['strokes'] ?? 82);
    $diff = (int)($body['diff'] ?? 10);

    // Calculate updated handicap
    $stmt = $pdo->prepare("SELECT hcp, rounds FROM members WHERE id = ?");
    $stmt->execute([$memberId]);
    $m = $stmt->fetch();

    $currentHcp = $m ? (float)$m['hcp'] : 8.4;
    $newHcp = max(0.0, round($currentHcp + ($diff - 10) * 0.05, 1));

    $stmt = $pdo->prepare("UPDATE members SET hcp = ?, rounds = rounds + 1 WHERE id = ?");
    $stmt->execute([$newHcp, $memberId]);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Round recorded successfully',
        'strokes' => $strokes,
        'new_hcp' => $newHcp
    ]);
}

function calculateSlotPrice(int $holes, bool $isWeekend, int $min): int
{
    if ($min >= 900) {
        return ($holes === 18) ? 55 : 35; // Twilight rate (-35%)
    }
    if ($isWeekend) {
        return ($holes === 18) ? 110 : 62; // Weekend rate
    }
    return ($holes === 18) ? 85 : 48; // Weekday rate
}

function handleLogin(PDO $pdo, array $body): void
{
    $email = trim($_GET['email'] ?? $body['email'] ?? '');
    $memberId = (int)($_GET['member_id'] ?? $body['member_id'] ?? 0);

    $member = null;
    if ($memberId > 0) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, tier, hcp, rounds, last_play, joined, status, spend FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();
    } elseif (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, tier, hcp, rounds, last_play, joined, status, spend FROM members WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        $member = $stmt->fetch();
    }

    if (!$member) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Member account not found for ' . ($email ?: "Member #$memberId") . '. Please verify your email address or contact the club office.'
        ]);
        return;
    }

    if (($member['status'] ?? '') === 'Suspended') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Your membership account is currently suspended. Please contact club administration at (555) 301-2200.'
        ]);
        return;
    }

    if (($member['status'] ?? '') === 'Pending') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Your membership account is currently pending committee review. Please check back soon.'
        ]);
        return;
    }

    echo json_encode([
        'status' => 'ok',
        'message' => 'Signed in successfully',
        'token' => 'member_token_' . $member['id'] . '_' . time(),
        'member' => [
            'id' => (int)$member['id'],
            'name' => $member['name'],
            'email' => $member['email'],
            'phone' => $member['phone'] ?? '',
            'tier' => $member['tier'] ?? 'Standard',
            'hcp' => (float)$member['hcp'],
            'rounds' => (int)$member['rounds'],
            'last_play' => $member['last_play'] ?? '',
            'joined' => $member['joined'] ?? '',
            'status' => $member['status'] ?? 'Active',
            'spend' => (float)$member['spend'],
        ]
    ]);
}

function handleMembers(PDO $pdo): void
{
    $stmt = $pdo->query("SELECT id, name, email, phone, tier, hcp, rounds, status, joined, spend FROM members ORDER BY id ASC");
    $members = [];
    while ($m = $stmt->fetch()) {
        $members[] = [
            'id' => (int)$m['id'],
            'name' => $m['name'],
            'email' => $m['email'],
            'phone' => $m['phone'] ?? '',
            'tier' => $m['tier'] ?? 'Standard',
            'hcp' => (float)$m['hcp'],
            'rounds' => (int)$m['rounds'],
            'status' => $m['status'] ?? 'Active',
            'joined' => $m['joined'] ?? '',
            'spend' => (float)($m['spend'] ?? 0.0)
        ];
    }
    echo json_encode([
        'status' => 'ok',
        'members' => $members
    ]);
}

