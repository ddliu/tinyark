<?php
require dirname(__FILE__).'/../../src/ark.php';
Ark::createWebApp()
require './app/app.php';
$app = new App();
$app->run();function(){}