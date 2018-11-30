<?php

define('ROOT_PATH', __DIR__);
require ROOT_PATH . '/vendor/autoload.php';
$config =  require ROOT_PATH . '/config.php';

use Pheanstalk\Pheanstalk;

$pheanstalk = new Pheanstalk('127.0.0.1');

$job = json_encode([
    'module' => 't',
    'function' => 's',
    'params' => ['url' => 'http://www.baidu.com']
]);
while(true) {

    $pheanstalk->useTube('update_weather')->put($job);
    usleep(1000);

}
