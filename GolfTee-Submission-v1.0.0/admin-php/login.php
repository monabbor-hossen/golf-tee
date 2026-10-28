<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;
use App\Core\Request;

(new AuthController())->login(Request::createFromGlobals());
