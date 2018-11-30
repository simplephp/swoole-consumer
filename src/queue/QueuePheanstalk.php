<?php

/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/11/16
 * @since          1.0
 */
namespace simplephp\consumer\queue;

use simplephp\consumer\queue\QueueInterface;
use Pheanstalk\Pheanstalk;

class QueuePheanstalk implements QueueInterface
{
    private $queue = null;

    /**
     * 是否支持
     * @return bool
     */
    public function support(): bool
    {
        return true;
    }

    /**
     * @param Closure $callback
     */
    public function run($tube_name, \Closure $callback) {
        do {
            // make sure master prccess running
            $job_data = $this->queue->watch($tube_name)->ignore('default')->reserve();
            $job_datax = json_decode($job_data->getData(), true);
            if($job_data == false) {
                $state = $callback($job_datax);
            } else {
                $state = $callback($job_datax);
            }
            $this->queue->delete($job_data);
            $x_condition = true;
        } while($x_condition);
    }
    /**
     * 获取连接
     * @param array $config
     * @return mixed
     */
    public function getConnection(array $config)
    {

        $this->queue = new Pheanstalk($config['host']);

    }

    /**
     * @return array a array of topics
     */
    public function getTopics()
    {

    }

    /**
     * @param array $topics
     */
    public function setTopics(array $topics)
    {
    }

    /**
     * 推送队列，返回jobid字符串.
     *
     * @param [type]    $topic
     * @param JobObject $job
     *
     * @return string
     */
    public function push($topic): string
    {
        return $topic;
    }

    /**
     * 从队列拿消息
     * @param $topic
     * @return mixed
     */
    public function pop($topic)
    {

    }

    /**
     * 队列长度获取
     * @param $topic
     * @return int
     */
    public function len($topic): int
    {
        return 0;
    }

    /**
     * 关闭
     * @return mixed
     */
    public function close()
    {
        $this->queue->getConnection()->disconnect();
    }

    /**
     * 连接状态
     * @return mixed
     */
    public function isConnected() {
        $this->queue->getConnection()->isServiceListening();
    }
}