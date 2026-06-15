<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use MateriaisOpme\App\Bootstrap;

$bootstrap = new Bootstrap();
$bootstrap->handle();
