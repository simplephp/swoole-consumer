<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/6/29
 * @since          1.0
 */

namespace simplephp\consumer;
use simplephp\consumer\di\Container;

class Base
{
    public static $container;

    private static $_logger;

    private function __construct()
    {
        $this->_init();
    }

    /**
     * 初始化
     */
    private function _init() {
        self::$container = new Container();
    }
    /**
     * @return Logger message logger
     */
    public static function getLogger()
    {
        if (self::$_logger !== null) {
            return self::$_logger;
        }

        return self::$_logger = static::$container->get("log");
    }
}