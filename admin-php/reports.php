<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\ReportController;
use App\Core\Request;

(new ReportController())->index(Request::createFromGlobals());
