<?php
require __DIR__.'/bootstrap.php';

$configs = require(__DIR__.'/config.php');
$app = new \ddliu\tinyark\WebApp($configs);
$app->run();