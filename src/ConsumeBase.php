<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/11/30
 * @since          1.0
 */

namespace simplephp\consumer;


interface ConsumeBase
{
    public function excute(Job $job, ...$params);

    public function failed(...$params);
}