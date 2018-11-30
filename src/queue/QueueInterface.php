<?php

/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/11/16
 * @since          1.0
 */
namespace simplephp\consumer\queue;

interface QueueInterface
{

    /**
     * 是否支持
     * @return bool
     */
    public function support(): bool;
    /**
     * 获取连接
     * @param array $config
     * @return mixed
     */
    public function getConnection(array $config);

    /**
     * @return array a array of topics
     */
    public function getTopics();

    /**
     * @param array $topics
     */
    public function setTopics(array $topics);

    /**
     * 推送队列，返回jobid字符串.
     *
     * @param [type]    $topic
     * @param JobObject $job
     *
     * @return string
     */
    public function push($topic): string;

    /**
     * 从队列拿消息
     * @param $topic
     * @return mixed
     */
    public function pop($topic);

    /**
     * 队列长度获取
     * @param $topic
     * @return int
     */
    public function len($topic): int;

    /**
     * 关闭
     * @return mixed
     */
    public function close();

    /**
     * 连接状态
     * @return mixed
     */
    public function isConnected();
}