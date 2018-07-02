<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/6/28
 * @since          1.0
 */

namespace simplephp\consumer\di;


class Container
{
    // 用于保存单例Singleton对象，以对象类型为键
    private $_singletons = [];

    // 用于保存依赖的定义，以对象类型为键
    private $_definitions = [];

    // 用于保存构造函数的参数，以对象类型为键
    private $_params = [];

    // 用于缓存ReflectionClass对象，以类名或接口名为键
    private $_reflections = [];

    // 用于缓存依赖信息，以类名或接口名为键
    private $_dependencies = [];

    public function __construct(){}

    private function __clone(){}

    public function get($class) {
        return null;
    }

}