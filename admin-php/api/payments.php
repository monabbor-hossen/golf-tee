<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\ApiController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

Session::requireAuth();
if (!Session::isStaff()) {
    Response::json(['error' => 'Unauthorized'], 403);
}

(new ApiController())->handlePayments(Request::createFromGlobals());
