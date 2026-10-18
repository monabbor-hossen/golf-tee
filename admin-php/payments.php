<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\PaymentController;
use App\Core\Request;

(new PaymentController())->index(Request::createFromGlobals());
