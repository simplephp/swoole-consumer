<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/6/28
 * @since          1.0
 */

define('ROOT_PATH', __DIR__);
require ROOT_PATH . '/vendor/autoload.php';
$config =  require ROOT_PATH . '/config.php';

$console = new simplephp\consumer\Console($config);
$console->run();