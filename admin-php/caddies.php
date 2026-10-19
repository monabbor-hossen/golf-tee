<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\CaddieController;
use App\Core\Request;

$controller = new CaddieController();
$controller->index(new Request());
