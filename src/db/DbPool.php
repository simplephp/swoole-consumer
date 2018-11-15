<?php

/**
 * Class Pool
 */
namespace simplephp\consumer\db;

class DbPool
{
    // 连接池数组 .
    protected $connections;

    // 最大连接数
    protected $max;

    // 最小连接数
    protected $min;

    // 已连接数
    protected $count = 0;

    protected $inited = false;

    // 单例
    private static $instance;

    //数据库配置
    protected $config = array(
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '123456',
        'database' => 'story',
        'charset' => 'utf8',
        'timeout' => 2,
    );

    /**
     * 初始化
     * Pool constructor.
     */
    private function __construct()
    {
        $this->connections = new \SplQueue();
        $this->max = 30;
        $this->min = 5;
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    /**
     * @return DbPool
     */
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new DbPool();
        }
        return self::$instance;
    }

    // worker启动的时候 建立 min 个连接
    public function init()
    {
        if ($this->inited) {
            return;
        }
        for ($i = 0; $i < $this->min; $i++) {
            $this->generate();
        }
        return $this;
    }

    /**
     * 维持当前的连接数不断线，并且剔除断线的链接 .
     */
    public function keepAlive()
    {
        // 2分钟检测一次连接
        swoole_timer_tick(1000, function () {
            // 维持连接
            while ($this->connections->count() > 0 && $next = $this->connections->shift()) {
                $next->query("select 1", function ($db, $res) {
                    if ($res == false) {
                        return;
                    }
                    echo "当前连接数：" . $this->connections->count() . PHP_EOL;
                    $this->connections->push($db);
                });
            }
        });

        swoole_timer_tick(1000, function () {
            // 维持活跃的链接数在 min-max之间
            if ($this->connections->count() > $this->max) {
                while ($this->max < $this->connections->count()) {
                    $next = $this->connections->shift();
                    $next->close();
                    $this->count--;
                    echo "关闭连接...\n";
                }
            }
        });
    }

    /**
     * 创建连接
     * @param null $callback
     */
    public function generate($callback = null)
    {
        $db = new \swoole_mysql();
        $db->connect($this->config, function ($db, $res) use ($callback) {
            if ($res == false) {
                throw new \Exception("数据库连接错误::" . $db->connect_errno . $db->connect_error);
            }
            $this->count++;
            $this->addConnections($db);
            if (is_callable($callback)) {
                call_user_func($callback);
            }
        });
    }

    /**
     * @param $db
     * @return $this
     */
    public function addConnections($db)
    {
        $this->connections->push($db);
        return $this;
    }

    /**
     * 执行数据库命令 . 会判断连接数够不够，够就直接执行，不够就新建连接执行
     * @param $query
     * @param $callback
     */
    public function query($query, $callback)
    {
        if ($this->connections->count() == 0) {
            $this->generate(function () use ($query, $callback) {
                $this->exec($query, $callback);
            });
        } else {
            $this->exec($query, $callback);
        }
    }

    /**
     * 直接执行数据库命令并且 callback();
     * @param $query
     * @param $callback
     */
    private function exec($query, $callback)
    {
        $db = $this->connections->shift();
        $db->query($query, function ($db, $result) use ($callback) {
            $this->connections->push($db);
            $callback($result);
        });
    }

}