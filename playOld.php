<?php

require_once 'vendor/autoload.php';

$param = ($argc > 1) ? $argv[1] : '';

$snakes    = strstr($param, 'm') ? 2 : 1;
$keepAlive = strstr($param, 'k') ? true : false;

$snake = new \PHPSnake\SnakeOld($snakes, $keepAlive);
$snake->run();