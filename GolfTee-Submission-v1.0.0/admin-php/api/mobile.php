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

// IP Rate Limiting Middleware (max 100 requests/minute per IP)
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (!RateLimiter::check($clientIp, 100, 60)) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'Rate limit exceeded: Maximum 100 requests per minute allowed.'
    ]);
    exit;
}

// Disallow password parameter via GET query string
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

// Token authentication required before routing to any controller method
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

// Derive member ID strictly from authenticated token for all personal/mutating actions;
// client-supplied query/body member_id is ignored to prevent IDOR / privilege escalation
if (!in_array($action, $publicActions, true)) {
    if ($authMemberId === null || $authMemberId <= 0) {
        Response::unauthorized('Authentication required');
        exit;
    }
    $memberId = $authMemberId;
} else {
    // Strictly public endpoints (e.g., weather, slots) do not require a personal member ID
    $memberId = $authMemberId ?? 0;
}

try {
    $controller->dispatch($action, $jsonBody, $memberId);
} catch (\App\Core\UnauthorizedException $e) {
    Response::unauthorized($e->getMessage());
} catch (\Throwable $e) {
    Response::serverError($e->getMessage());
}

