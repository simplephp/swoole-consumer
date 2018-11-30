<?php
/**
 * 摘抄 yii2 Container，人家米其林轮子，安装上用就行了。
 * @author         kevin <askyiwang@gmail.com>
 * @date           2017/4/21
 * @since          1.0
 */

namespace simplephp\consumer\di;

class Container implements \ArrayAccess
{
    /**
     * 单例
     * @var Container
     */
    protected static $instance;

    /**
     * 容器所管理的实例
     * @var array
     */
    protected $instances = [];

    /**
     * 初始化
     * Container constructor.
     * @param array $components
     */
    private function __construct(array $components){

        if(is_array($components) && !empty($components)) {

            foreach ($components as $class => $params) {
                $this->instances[$class] = $this->make($params['class'], $params);
            }
        }
    }

    private function __clone(){}

    /**
     * 获取单例的实例
     * @param string $class
     * @param array  ...$params
     * @return object
     */
    public function singleton($class, ...$params)
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        } else {
            $this->instances[$class] = $this->make($class, $params);
        }

        return $this->instances[$class];
    }

    /**
     * 获取实例（每次都会创建一个新的）
     * @param string $class
     * @param array  ...$params
     * @return object
     */
    public function get($class, ...$params)
    {
        return $this->make($class, $params);
    }

    /**
     * 工厂方法，创建实例，并完成依赖注入
     * @param string $class
     * @param array  $params
     * @return object
     */
    protected function make($class, $params = [])
    {
        //如果不是反射类根据类名创建
        $class = is_string($class) ? new \ReflectionClass($class) : $class;

        //如果传的入参不为空，则根据入参创建实例
        if (!empty($params)) {
            return $class->newInstanceArgs([$params]);
        }

        //获取构造方法
        $constructor = $class->getConstructor();

        //获取构造方法参数
        $parameterClasses = $constructor ? $constructor->getParameters() : [];

        if (empty($parameterClasses)) {
            //如果构造方法没有入参，直接创建
            return $class->newInstance();
        } else {
            //如果构造方法有入参，迭代并递归创建依赖类实例
            foreach ($parameterClasses as $parameterClass) {
                $paramClass = $parameterClass->getClass();
                $params[] = $this->make($paramClass);
            }
            //最后根据创建的参数创建实例，完成依赖的注入
            return $class->newInstanceArgs($params);
        }
    }

    /**
     * @return Container
     */
    public static function getInstance(array $components)
    {
        if (null === static::$instance) {
            static::$instance = new static($components);
        }

        return static::$instance;
    }

    public function __get($class)
    {
        if (!isset($this->instances[$class])) {
            $this->instances[$class] = $this->make($class);
        }
        return $this->instances[$class];
    }

    public function offsetExists($offset)
    {
        return isset($this->instances[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->instances[$offset])) {
            $this->instances[$offset] = $this->make($offset);
        }
        return $this->instances[$offset];
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset) {
        unset($this->instances[$offset]);
    }
}