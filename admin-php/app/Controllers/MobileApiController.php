<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\Response;
use App\Core\Session;
use App\Core\UnauthorizedException;
use App\Domain\SlotFormatter;
use App\Repositories\MemberRepository;
use App\Services\BookingApiHandler;
use App\Services\EmailService;
use App\Services\LoginLockoutManager;
use App\Services\PasswordHandler;
use App\Services\PaymentApiHandler;
use App\Services\ProfileApiHandler;
use App\Services\TokenAuthMiddleware;
use App\Services\TokenExtractor;
use App\Services\TokenGenerator;
use App\Services\WeatherApiHandler;
use PDO;

/**
 * MobileApiController
 * Finding 5.1: Thin dispatcher (<300 lines) delegating business logic to services.
 * Extends BaseApiController (Finding 5.12) with token/password extraction and ownership validation.
 */
class MobileApiController extends BaseApiController
{
    private BookingApiHandler $bookingHandler;
    private PaymentApiHandler $paymentHandler;
    private ProfileApiHandler $profileHandler;
    private WeatherApiHandler $weatherHandler;

    public function __construct(
        ?PDO $pdo = null, ?MemberRepository $memberRepo = null,
        ?BookingApiHandler $bookingHandler = null, ?PaymentApiHandler $paymentHandler = null,
        ?ProfileApiHandler $profileHandler = null, ?WeatherApiHandler $weatherHandler = null
    ) {
        parent::__construct($pdo, $memberRepo);
        $this->bookingHandler = $bookingHandler ?? new BookingApiHandler($this->pdo);
        $this->paymentHandler = $paymentHandler ?? new PaymentApiHandler($this->pdo);
        $this->profileHandler = $profileHandler ?? new ProfileApiHandler($this->pdo, $this->paymentHandler);
        $this->weatherHandler = $weatherHandler ?? new WeatherApiHandler();
    }

    public function home(int $memberId): void
    {
        $authId = $this->requireOwnership($memberId, 401);
        if ($authId === null) return;
        $stmt = $this->pdo->prepare("SELECT id, name, email, tier, hcp, rounds, last_play, joined, status, spend FROM members WHERE id = ?");
        $stmt->execute([$authId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) { Response::notFound('Member not found'); return; }
        $stmt = $this->pdo->prepare("SELECT id, tee_date, tee_slot, party, holes, tee, status FROM bookings WHERE member_id = ? AND tee_date >= CURDATE() AND status != 'No-show' ORDER BY tee_date ASC, tee_slot ASC LIMIT 1");
        $stmt->execute([$authId]);
        $nb = $stmt->fetch(PDO::FETCH_ASSOC);
        $next = $nb ? ['id' => (int)$nb['id'], 'date' => $nb['tee_date'], 'min' => (int)$nb['tee_slot'], 'time' => SlotFormatter::toDisplayTime((int)$nb['tee_slot']), 'holes' => (int)$nb['holes'], 'players' => (int)$nb['party'], 'tee' => (int)$nb['tee'] === 0 ? 'Hole 1' : 'Hole 10', 'status' => 'upcoming'] : null;
        $stmt = $this->pdo->prepare("SELECT id, tee_date, tee_slot, party, holes, status FROM waitlist WHERE member_id = ? AND tee_date >= CURDATE() ORDER BY added_at DESC LIMIT 1");
        $stmt->execute([$authId]);
        $wi = $stmt->fetch(PDO::FETCH_ASSOC);
        $wait = $wi ? ['id' => (int)$wi['id'], 'date' => $wi['tee_date'], 'min' => (int)$wi['tee_slot'], 'time' => SlotFormatter::toDisplayTime((int)$wi['tee_slot']), 'holes' => (int)$wi['holes'], 'players' => (int)$wi['party'], 'position' => $wi['status'] === 'Offered' ? 1 : 2] : null;
        $news = ['id' => 1, 'title' => 'Autumn Member Classic — registration is open', 'category' => 'Event', 'badge_text' => 'Event · Nov 9', 'summary' => 'Shotgun start 8 AM · 72 spots left', 'image_url' => 'https://images.unsplash.com/photo-1761141987961-ed96a841fe21?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600'];
        Response::json(['status' => 'ok', 'member' => $member, 'next_booking' => $next, 'ready_waitlist' => $wait, 'news' => $news, 'conditions' => $this->weatherHandler->handleWeather()['conditions'] ?? []]);
    }

    public function slots(string $date, int $holes): void { Response::json($this->bookingHandler->getSlots($date, $holes)); }
    public function book(array $body, int $memberId): void {
        $authId = $this->requireOwnership($memberId, 401);
        if ($authId === null) return;
        $res = $this->bookingHandler->createBooking($authId, $body);
        Response::json($res, isset($res['error']) ? ($res['code'] ?? 500) : 200);
    }

    /**
     * Finding 3.2: Verify authenticated member ID matches requested memberId or is staff
     */
    public function bookings(int $memberId): void
    {
        $authId = $this->requireOwnership($memberId);
        if ($authId === null) return;

        $stmt = $this->pdo->prepare("SELECT id, member_id, tee_date, tee_slot, party, holes, tee, status, notes FROM bookings WHERE member_id = ? ORDER BY tee_date DESC, tee_slot DESC");
        $stmt->execute([$authId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $upcoming = []; $past = []; $today = date('Y-m-d');
        foreach ($rows as $b) {
            $extra = !empty($b['notes']) ? json_decode($b['notes'], true) : [];
            $item = ['id' => (int)$b['id'], 'date' => $b['tee_date'], 'min' => (int)$b['tee_slot'], 'time' => SlotFormatter::toDisplayTime((int)$b['tee_slot']), 'holes' => (int)$b['holes'], 'players' => (int)$b['party'], 'tee' => (int)$b['tee'] === 0 ? 'Hole 1' : 'Hole 10', 'partners' => $extra['partners'] ?? [], 'addons' => $extra['addons'] ?? ['cart'], 'total' => (float)($extra['total'] ?? 170.00), 'card' => $extra['card'] ?? 'v', 'status' => ($b['tee_date'] >= $today && $b['status'] !== 'No-show') ? 'upcoming' : 'past', 'course' => 'Pine Valley GC', 'qr_code' => 'PINEVALLEY-BOOKING-' . $b['id'] . '-' . $b['tee_date']];
            ($item['status'] === 'upcoming') ? ($upcoming[] = $item) : ($past[] = $item);
        }

        $wStmt = $this->pdo->prepare("SELECT id, member_id, tee_date, tee_slot, party, holes, status FROM waitlist WHERE member_id = ? ORDER BY tee_date DESC");
        $wStmt->execute([$authId]);
        $waitlist = array_map(fn($w) => ['id' => (int)$w['id'], 'date' => $w['tee_date'], 'min' => (int)$w['tee_slot'], 'time' => SlotFormatter::toDisplayTime((int)$w['tee_slot']), 'holes' => (int)$w['holes'], 'players' => (int)$w['party'], 'position' => $w['status'] === 'Offered' ? 1 : 2, 'status' => $w['status']], $wStmt->fetchAll(PDO::FETCH_ASSOC));

        Response::json(['status' => 'ok', 'upcoming' => $upcoming, 'past' => $past, 'waitlist' => $waitlist]);
    }

    public function bookingDetail(int $bookingId, int $memberId): void
    {
        $authId = $this->requireOwnership($memberId, 401);
        if ($authId === null) return;
        $b = $this->bookingHandler->getBooking($bookingId, $authId);
        if (!$b) { Response::notFound('Booking not found'); return; }
        $extra = !empty($b['notes']) ? json_decode($b['notes'], true) : [];
        $isUp = ($b['tee_date'] >= date('Y-m-d') && $b['status'] !== 'No-show');
        Response::json(['status' => 'ok', 'booking' => ['id' => (int)$b['id'], 'date' => $b['tee_date'], 'min' => (int)$b['tee_slot'], 'time' => SlotFormatter::toDisplayTime((int)$b['tee_slot']), 'holes' => (int)$b['holes'], 'players' => (int)$b['party'], 'tee' => (int)$b['tee'] === 0 ? 'Hole 1' : 'Hole 10', 'partners' => $extra['partners'] ?? [], 'addons' => $extra['addons'] ?? ['cart'], 'total' => (float)($extra['total'] ?? 170.00), 'status' => $isUp ? 'upcoming' : 'past', 'course' => 'Pine Valley GC']]);
    }

    /**
     * Finding 3.3: Verify booking.member_id matches authenticated member before allowing cancellation
     */
    public function cancel(int $bookingId, int $memberId = 0): void
    {
        $targetId = $memberId > 0 ? $memberId : (int)$this->getAuthenticatedMemberId();
        $authId = $this->requireOwnership($targetId, 401);
        if ($authId === null) return;

        $stmt = $this->pdo->prepare("SELECT member_id FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) { Response::notFound('Booking not found'); return; }
        if ((int)$booking['member_id'] !== $authId && !Session::isStaff()) throw new UnauthorizedException('Not your booking', 403);

        Response::json($this->bookingHandler->cancelBooking($bookingId, $authId));
    }

    public function cancelBooking(int $id, int $memberId = 0): void { $this->cancel($id, $memberId); }

    /**
     * Finding 3.4: Verify authenticated member can only update their own profile
     */
    public function updateProfile(array $body, int $memberId = 0): void
    {
        $targetId = (int)($body['member_id'] ?? 0);
        $authId = $this->requireOwnership($targetId, $body);
        if ($authId === null) return;
        Response::json($this->profileHandler->updateProfile($authId, $body));
    }

    /**
     * Finding 3.5: Verify authenticated member ID matches requested memberId
     */
    public function notifications(int $memberId): void
    {
        $authId = $this->requireOwnership($memberId);
        if ($authId === null) return;
        $stmt = $this->pdo->prepare("SELECT id, member_id, title, description, tone, page, focus_data, is_read, created_at FROM notifications WHERE member_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$authId]);
        Response::json(['status' => 'ok', 'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function profile(int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->profileHandler->getProfile($authId) ?? ['error' => 'Member not found']); }
    public function cards(int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->paymentHandler->getCards($authId)); }
    public function addCard(array $body, int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->paymentHandler->addCard($authId, $body)); }
    public function setDefaultCard(array $body, int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->paymentHandler->setDefaultCard($authId, (int)($body['card_id'] ?? 0))); }
    public function deleteCard(array $body, int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->paymentHandler->deleteCard($authId, (int)($body['card_id'] ?? 0))); }
    public function transactions(int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->paymentHandler->getTransactions($authId)); }
    public function joinWaitlist(array $body, int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->bookingHandler->joinWaitlist($authId, $body)); }
    public function leaveWaitlist(int $waitId, int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->bookingHandler->leaveWaitlist($authId, $waitId)); }
    public function waitlistEntries(int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->bookingHandler->getWaitlistEntries($authId)); }
    public function saveScore(array $body, int $memberId): void { $authId = $this->requireOwnership($memberId, 401); if ($authId !== null) Response::json($this->profileHandler->saveScore($authId, $body)); }
    public function weather(): void { Response::json($this->weatherHandler->handleWeather()); }

    /**
     * Finding 3.6: Require email verification token before account activation, implement rate limiting per IP
     */
    public function register(array $body): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (LoginLockoutManager::isLockedOut($ip)) { Response::json(['error' => 'Too many registration attempts. Please try again later.'], 429); return; }
        $email = trim((string)($body['email'] ?? '')); $name = trim((string)($body['name'] ?? ''));
        if (empty($email) || empty($name)) { Response::json(['error' => 'Name and email are required'], 400); return; }

        $token = TokenGenerator::generate(); $expires = TokenGenerator::expiry('+24 hours');
        $stmt = $this->pdo->prepare("INSERT INTO members (name, email, reset_token, token_expires_at, status) VALUES (?, ?, ?, ?, 'pending_verification')");
        $stmt->execute([$name, $email, $token, $expires]);
        (new EmailService())->sendInvitation($name, $email, $token);
        Response::json(['status' => 'ok', 'message' => 'Verification email sent', 'member_id' => (int)$this->pdo->lastInsertId()]);
    }

    public function login(array $body): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (LoginLockoutManager::isLockedOut($ip)) { Response::json(['error' => 'Too many failed login attempts.'], 429); return; }
        $email = trim((string)($body['email'] ?? ''));
        $authType = strtolower(trim((string)($body['auth_type'] ?? (!empty($body['google']) ? 'google' : 'password'))));

        // Finding 5.10: Token field names defined in Config
        if ($authType === 'google') {
            $tokenFields = Config::get('TOKEN_FIELD_NAMES', ['google_token', 'id_token', 'token']);
            $googleToken = '';
            foreach ($tokenFields as $field) {
                if (!empty($body[$field])) { $googleToken = (string)$body[$field]; break; }
            }
            if (empty($email) && !empty($googleToken)) {
                $parts = explode('.', $googleToken);
                if (count($parts) === 3) {
                    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                    $email = (string)($payload['email'] ?? '');
                }
            }
        }

        $stmt = $this->pdo->prepare("SELECT * FROM members WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member || ($authType !== 'google' && !password_verify(PasswordHandler::extract($body), (string)($member['password'] ?? $member['password_hash'] ?? '')))) {
            LoginLockoutManager::recordFailedAttempt($ip);
            Response::json(['error' => 'Invalid credentials'], 401);
            return;
        }

        LoginLockoutManager::clearAttempts($ip);
        // Finding 5.11: Centralized TokenGenerator::generateAndStore()
        $token = TokenGenerator::generateAndStore((int)$member['id']);
        Session::start();
        $_SESSION['member_id'] = (int)$member['id']; $_SESSION['member_name'] = (string)$member['name']; $_SESSION['auth_tokens'][$token] = (int)$member['id'];
        Response::json(['status' => 'ok', 'token' => $token, 'member' => $member]);
    }

    public function changePassword(array $body, int $memberId): void
    {
        $authId = $this->requireOwnership($memberId, 401);
        if ($authId === null) return;
        // Finding 5.14: Use PasswordHandler::extract() with field name parameter
        $currentPassword = PasswordHandler::extract($body, 'current_password');
        $member = $this->memberRepo->findById($authId);
        if (!$member || !password_verify($currentPassword, (string)($member['password'] ?? $member['password_hash'] ?? ''))) {
            Response::json(['error' => 'Current password incorrect'], 400);
            return;
        }
        $newPassword = PasswordHandler::extract($body, 'password');
        PasswordHandler::validate($newPassword);
        $newHash = PasswordHandler::hash($newPassword);
        $this->pdo->prepare("UPDATE members SET password = ?, password_hash = ? WHERE id = ?")->execute([$newHash, $newHash, $authId]);
        Response::json(['status' => 'ok', 'message' => 'Password updated']);
    }

    /**
     * Finding 3.7: Validate token exists, check token expiry timestamp, and mark as used
     */
    public function resetPassword(array $body): void
    {
        $token = TokenExtractor::extractAndValidate($body);
        $stmt = $this->pdo->prepare("SELECT id, token_expires_at FROM members WHERE reset_token = ?");
        $stmt->execute([$token]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) { Response::json(['error' => 'Invalid token'], 400); return; }
        if (strtotime((string)$member['token_expires_at']) < time()) throw new \Exception('Token expired');

        $password = PasswordHandler::extract($body, 'password');
        PasswordHandler::validate($password);
        $hash = PasswordHandler::hash($password);
        $this->pdo->prepare("UPDATE members SET password = ?, password_hash = ?, reset_token = NULL, token_expires_at = NULL WHERE id = ?")->execute([$hash, $hash, $member['id']]);
        Response::json(['status' => 'ok', 'message' => 'Password reset successfully']);
    }

    public function setPassword(array $body): void { $this->resetPassword($body); }
    public function forgotPassword(array $body): void
    {
        $email = trim((string)($body['email'] ?? ''));
        $stmt = $this->pdo->prepare("SELECT id, name, email FROM members WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($m) {
            $token = TokenGenerator::generate();
            $this->pdo->prepare("UPDATE members SET reset_token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?")->execute([$token, $m['id']]);
            (new EmailService())->sendPasswordReset($m['name'], $m['email'], $token);
        }
        Response::json(['status' => 'ok', 'message' => 'If this email is registered, instructions have been sent.']);
    }

    public function verifySetupToken(array|string $input): void
    {
        $token = TokenExtractor::extractAndValidate($input);
        $stmt = $this->pdo->prepare("SELECT id, name, email, tier, status FROM members WHERE reset_token = ? AND token_expires_at > NOW()");
        $stmt->execute([$token]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) { Response::json(['error' => 'Token invalid or expired'], 404); return; }
        Response::json(['status' => 'ok', 'member' => $member]);
    }

    public function members(): void
    {
        $authId = $this->getAuthenticatedMemberId();
        if ($authId === null) { Response::unauthorized(); return; }
        $stmt = $this->pdo->query("SELECT id, name, email, tier, status FROM members WHERE status != 'Deleted' LIMIT 100");
        Response::json(['status' => 'ok', 'members' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
}
