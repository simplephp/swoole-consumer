<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/6/29
 * @since          1.0
 */

namespace simplephp\consumer;

class Job
{
    public $logger = null;
    private $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }
}