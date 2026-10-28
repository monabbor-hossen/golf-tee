<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\ApiController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

Session::requireAuth();
if (!Session::hasRole('manager') && !Session::hasRole('owner') && !Session::hasRole('admin')) {
    Response::json(['error' => 'Forbidden'], 403);
}

(new ApiController())->handleReports(Request::createFromGlobals());
