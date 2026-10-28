<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\WaitlistController;
use App\Core\Request;

(new WaitlistController())->index(Request::createFromGlobals());
