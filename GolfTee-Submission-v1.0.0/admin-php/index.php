<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\DashboardController;
use App\Core\Request;

(new DashboardController())->index(Request::createFromGlobals());
