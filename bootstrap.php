<?php

use App\CliApplication;

$autoloader = require __DIR__ . '/vendor/autoload.php';


$app = new CliApplication($autoloader);

$app->setEnv(getenv('APPLICATION_ENV') ?: 'production');

$app->run();
