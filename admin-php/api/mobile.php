<?php
declare(strict_types=1);

/**
 * Golf Tee Mobile App — Dedicated REST JSON API Front Router
 *
 * Dispatches mobile requests to App\Controllers\MobileApiController following
 * PSR-4 clean MVC architecture with strict authorization and rate limiting.
 */

// Enable CORS for mobile devices, emulators, and web
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Member-Id');
    header('Content-Type: application/json; charset=utf-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\MobileApiController;
use App\Core\Response;
use App\Services\RateLimiter;
use App\Services\TokenExtractor;
use App\Services\TokenAuthMiddleware;

// Finding 4.11: IP Rate Limiting Middleware (max 100 requests/minute per IP)
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (!RateLimiter::check($clientIp, 100, 60)) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'Rate limit exceeded: Maximum 100 requests per minute allowed.'
    ]);
    exit;
}

// Finding 3.5 & 5.3: Disallow password parameter via GET query string
if (isset($_GET['password'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Passwords must never be submitted via GET query string. Please use POST request with JSON body.'
    ]);
    exit;
}

$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');
$rawInput = file_get_contents('php://input');
$jsonBody = !empty($rawInput) ? json_decode($rawInput, true) : [];
if (!is_array($jsonBody)) {
    $jsonBody = [];
}
if (!empty($jsonBody['action']) && empty($action)) {
    $action = (string)$jsonBody['action'];
}

// Input validation middleware: sanitize action name
if ($action !== '' && !preg_match('/^[a-zA-Z0-9_\-]+$/', $action)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action parameter']);
    exit;
}

// Public endpoints exempt from mandatory upfront auth check
$publicActions = [
    'login', 'weather', 'conditions', 'slots', 'forgot_password',
    'forgot-password', 'verify_setup_token', 'set_password',
    'reset-password', 'reset_password', 'register', 'signup'
];

// Finding 3.1: Token authentication required before routing to any controller method
$authMemberId = null;
if (!in_array($action, $publicActions, true)) {
    try {
        $authMemberId = TokenAuthMiddleware::authenticate($jsonBody);
        $_REQUEST['authenticated_member_id'] = $authMemberId;
    } catch (\App\Core\UnauthorizedException $e) {
        RateLimiter::logSecurityEvent('Unauthenticated mobile API attempt', $clientIp, ['action' => $action]);
        Response::unauthorized('Authentication required');
        exit;
    }
} else {
    try {
        $authMemberId = TokenAuthMiddleware::authenticate($jsonBody);
        $_REQUEST['authenticated_member_id'] = $authMemberId;
    } catch (\Throwable $e) {
        // Public endpoint, auth is optional
    }
}

$controller = new MobileApiController();

// Extract requested member ID (default to authenticated member if logged in)
$memberId = $authMemberId ?? (isset($_GET['member_id'])
    ? intval($_GET['member_id'])
    : (isset($jsonBody['member_id']) ? intval($jsonBody['member_id']) : 0));

try {
    switch ($action) {
        case 'home':
            $controller->home($memberId);
            break;

        case 'slots':
            $date = (string)($_GET['date'] ?? $jsonBody['date'] ?? date('Y-m-d'));
            $holes = (int)($_GET['holes'] ?? $jsonBody['holes'] ?? 18);
            $controller->slots($date, $holes);
            break;

        case 'book':
            $controller->book($jsonBody, $memberId);
            break;

        case 'bookings':
            $controller->bookings($memberId);
            break;

        case 'booking_detail':
            $bookingId = (int)($_GET['id'] ?? $jsonBody['id'] ?? 0);
            $controller->bookingDetail($bookingId, $memberId);
            break;

        case 'cancel':
        case 'cancel_booking':
            $bookingId = (int)($_GET['id'] ?? $jsonBody['id'] ?? 0);
            $controller->cancel($bookingId, $memberId);
            break;

    case 'update_profile':
    case 'updateProfile':
        $controller->updateProfile($jsonBody, $memberId);
        break;

    case 'notifications':
        $controller->notifications($memberId);
        break;

    case 'join_waitlist':
        $controller->joinWaitlist($jsonBody, $memberId);
        break;

    case 'leave_waitlist':
        $waitId = (int)($_GET['id'] ?? $jsonBody['id'] ?? 0);
        $controller->leaveWaitlist($waitId, $memberId);
        break;

    case 'login':
        $controller->login($jsonBody);
        break;

    case 'forgot_password':
    case 'forgot-password':
        $controller->forgotPassword($jsonBody);
        break;

    case 'verify_setup_token':
        $controller->verifySetupToken($jsonBody);
        break;

    case 'change_password':
        $controller->changePassword($jsonBody, $memberId);
        break;

    case 'set_password':
    case 'reset-password':
    case 'reset_password':
        $controller->setPassword($jsonBody);
        break;

    case 'register':
    case 'signup':
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Direct self-registration is disabled. Pine Valley Golf Club accounts are created exclusively by Club Administration.'
        ]);
        break;

    case 'members':
        $controller->members();
        break;

    case 'weather':
    case 'conditions':
        $controller->weather();
        break;

    case 'profile':
        $controller->profile($memberId);
        break;

    case 'save_score':
        $controller->saveScore($jsonBody, $memberId);
        break;

    case 'cards':
        $controller->cards($memberId);
        break;

    case 'transactions':
        $controller->transactions($memberId);
        break;

    case 'waitlist':
    case 'waitlist_entries':
        $controller->waitlistEntries($memberId);
        break;

    case 'add_card':
        $controller->addCard($jsonBody, $memberId);
        break;

    case 'set_default_card':
        $controller->setDefaultCard($jsonBody, $memberId);
        break;

    case 'delete_card':
        $controller->deleteCard($jsonBody, $memberId);
        break;

    default:
        echo json_encode([
            'status' => 'ok',
            'app' => 'Golf Tee Mobile API',
            'version' => '1.0.0',
            'architecture' => 'PSR-4 MVC (MobileApiController)',
            'endpoints' => [
                'GET ?action=home&member_id=X',
                'GET ?action=slots&date=YYYY-MM-DD&holes=18',
                'POST ?action=book',
                'GET ?action=bookings&member_id=X',
                'GET ?action=booking_detail&id=X',
                'POST ?action=cancel_booking',
                'POST ?action=join_waitlist',
                'POST ?action=leave_waitlist',
                'POST ?action=login',
                'POST ?action=forgot-password',
                'POST ?action=set_password',
                'GET ?action=profile&member_id=X',
                'GET ?action=cards&member_id=X',
                'POST ?action=add_card',
                'POST ?action=delete_card',
                'POST ?action=save_score'
            ]
        ]);
        break;
    }
} catch (\App\Core\UnauthorizedException $e) {
    Response::unauthorized($e->getMessage());
} catch (\Throwable $e) {
    Response::serverError($e->getMessage());
}
