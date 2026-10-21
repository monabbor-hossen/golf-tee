<?php
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/Config.php';
App\Core\Config::load(__DIR__ . '/../.env');
$cols = App\Core\Database::fetchAll('DESCRIBE members');
print_r(array_column($cols, 'Field'));
