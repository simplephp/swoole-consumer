<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/6/29
 * @since          1.0
 */

namespace simplephp\consumer;
use Pheanstalk\Pheanstalk;

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
        $pheanstalk = new Pheanstalk('127.0.0.1');
        $condition = $pheanstalk->getConnection()->isServiceListening();
        $empty_message = 0;

        if($condition) {
            do {
                // make sure master prccess running
                $job_data = $pheanstalk->watch($this->tube_name)->ignore('default')->reserve();
                if($job_data == false) {
                    usleep(200);
                } else {
                    var_dump($job_data);
                }
                $pheanstalk->delete($job_data);
                $x_condition = true;
            } while($x_condition);
        } else {
            echo 'The Server not running...'.PHP_EOL;
            sleep(1);
        }
    }
}