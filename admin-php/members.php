<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\MemberController;
use App\Core\Request;

(new MemberController())->index(Request::createFromGlobals());
