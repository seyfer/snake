<?php
/**
 * Created by PhpStorm.
 * User: seyfer
 * Date: 11/1/16
 */

use PHPSnake\Snake;
use PHPSnake\SnakeGame;

require_once "vendor/autoload.php";

$param = ($argc > 1) ? $argv[1] : '';

$snakes    = strstr($param, 'm') ? 2 : 1;
$keepAlive = strstr($param, 'k') ? true : false;

var_export($snakes);
var_export($keepAlive);

$game = new SnakeGame();
$game->addSnake(new Snake());
$game->run();