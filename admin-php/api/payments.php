<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\ApiController;
use App\Core\Request;

(new ApiController())->handlePayments(Request::createFromGlobals());
