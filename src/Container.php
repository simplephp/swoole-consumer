<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/6/28
 * @since          1.0
 */

namespace simplephp\consumer;


class Container
{
    public static $instance;

    private $build = [];

    private function __construct(){}

    private function __clone(){}

    /**
     * @return Container
     */
    public static function getInstance() {

        if(!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}