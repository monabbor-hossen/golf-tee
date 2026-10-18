<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\SettingController;
use App\Core\Request;

(new SettingController())->index(Request::createFromGlobals());
