<?php
require dirname(__FILE__).'/../../src/ark.php';
$env = $_SERVER['REMOTE_ADDR'] === '127.0.0.1'?'dev':'prod';
$debug = $_SERVER['REMOTE_ADDR'] === '127.0.0.1';
Ark::createWebApp(dirname(__FILE__).'/app', $env, $debug)->run();