<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\NewsController;
use App\Core\Request;

(new NewsController())->index(Request::createFromGlobals());
