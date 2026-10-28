<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\MasterGridController;
use App\Core\Request;

(new MasterGridController())->index(Request::createFromGlobals());
