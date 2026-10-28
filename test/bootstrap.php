<?php

declare(strict_types=1);

$loader = require getenv('API_TOOLS_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$loader->addPsr4('Jield\\ApiTools\\', dirname(__DIR__) . '/src', true);
$loader->addPsr4('Jield\\ApiToolsTest\\', __DIR__);
