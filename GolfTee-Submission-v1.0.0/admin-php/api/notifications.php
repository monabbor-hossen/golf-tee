<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\ApiController;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuthorizationMiddleware;

// Require authenticated staff session and role check by default
Session::requireStaff();
AuthorizationMiddleware::requireRole(['staff', 'manager', 'admin', 'owner']);

$request = Request::createFromGlobals();
if ($request->isPost()) {
    Session::validateRequestCsrf($request);
}

(new ApiController())->handleNotifications($request);
