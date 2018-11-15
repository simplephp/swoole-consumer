<?php

define('ROOT_PATH', __DIR__);
require ROOT_PATH . '/vendor/autoload.php';
$config =  require ROOT_PATH . '/config.php';

$server = new \swoole_http_server("0.0.0.0", 9501);
$server->set([
    'worker_num' => 1,
]);
$server->on("WorkerStart", function ($server, $wid) {
    \simplephp\consumer\db\DbPool::getInstance()->init();
});
$server->on("request", function ($request, $response) {
    $pool =  \simplephp\consumer\db\DbPool::getInstance()->query("select * from users", function ($res) use ($response) {
        $response->end(json_encode($res));
    });
});
$server->start();