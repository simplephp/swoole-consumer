<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/6/29
 * @since          1.0
 */

namespace simplephp\consumer;
use simplephp\consumer\di\Container;
use simplephp\consumer\queue\QueuePheanstalk;

class Consumer
{
    public $logger = null;
    private $config = [];
    private $max_exec_count = 100;
    private $sleep_time = 1;

    private $tube_name;

    public function __construct($tube_name)
    {

        $this->tube_name = $tube_name;
    }

    /**
     *  启动进程
     */
    public function start()
    {
        $QueuePheanstalk = new QueuePheanstalk();
        $QueuePheanstalk->getConnection(['host' => '127.0.0.1']);
        $QueuePheanstalk->run('update_weather',function ($data) {
            $this->http_post_data($data['params']['url']);
            return true;
        });
    }

    /**
     * @param $data_string
     * @param $istest
     * @return mixed
     */
    private function http_post_data($url, $data = [], $second = 5)
    {
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, $second);
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_HEADER, 0);

        if(!empty($data)) {
            curl_setopt($curl_handle, CURLOPT_POST, 1);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
        $response_json = curl_exec($curl_handle);
        //返回结果
        if($response_json){
            curl_close($curl_handle);
            return $response_json;
        } else {
            $error = curl_errno($curl_handle);
            curl_close($curl_handle);
            return false;
        }
    }
}