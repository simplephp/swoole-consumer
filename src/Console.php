<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/6/29
 * @since          1.0
 */

namespace simplephp\consumer;

use simplephp\consumer\Base;
use simplephp\consumer\Process;

class Console
{
    public $logger = null;
    private $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 获取用户输入
     */
    public function run()
    {
        global $argv;
        if (empty($argv[1])) {
            $this->help();
            exit(1);
        }
        $opt = $argv[1];
        switch ($opt) {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->sendSignal();
                break;
            case 'status':
                $this->sendSignal(SIGUSR2);
                break;
            case 'exit':
                $this->kill();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'help':
                $this->help();
                break;
            default:
                $this->help();
                break;
        }
    }

    /**
     *  启动进程
     */
    public function start()
    {
        $process = new Process();
        $process->start();
    }

    /**
     *  给主进程发送信号：
     *  SIGUSR1 用户自定义信号1，让子进程平滑退出
     *  SIGUSR2 用户自定义信号2，显示进程状态
     *  SIGTERM 软件终止（software  termination），让子进程强制退出.
     * @param int $signal
     */
    public function sendSignal($signal = SIGUSR1)
    {
        if (isset($this->config['pidPath']) && !empty($this->config['pidPath'])) {
            $masterPidFile = $this->config['pidPath'] . '/master.pid';
            $pidStatusFile = $this->config['pidPath'] . '/status.info';
        } else {
            exit('config pidPath must be set!' . PHP_EOL);
        }

        if (file_exists($masterPidFile)) {
            $pid = file_get_contents($masterPidFile);
            // $signo = 0，可以检测进程是否存在，不会发送信号
            if ($pid && !@\Swoole\Process::kill($pid, 0)) {
                exit('The service is not running' . PHP_EOL);
            }
            if (@\Swoole\Process::kill($pid, $signal)) {
                sleep(1);
                if (SIGUSR2 == $signal) {
                    $statusStr = file_get_contents($pidStatusFile);
                    echo $statusStr ? $statusStr : 'sorry,show status fail.';
                    exit;
                }
            }
        } else {
            exit('The service is not running' . PHP_EOL);
        }
    }

    /**
     * 重启
     */
    public function restart()
    {
        $this->kill();
        sleep(3);
        $this->start();
    }

    /**
     * SIGTERM 软件终止（software  termination），让子进程强制退出.
     */
    public function kill()
    {
        $this->sendSignal(SIGTERM);
    }

    /**
     * 帮助信息
     */
    public function help()
    {
        $m = <<<'EOF'
NAME
      php swoole-consumer - manage swoole-consumer

SYNOPSIS
      php swoole-consumer command [options]
          Manage swoole-consumer daemons.

WORKFLOWS

      help [command]
      Show this help, or workflow help for command.

      restart
      Stop, then start swoole-consumer master and workers.

      start
      Start swoole-consumer master and workers.

      stop
      Wait all running workers smooth exit, please check swoole-consumer status for a while.

      exit
      Kill all running workers and master PIDs.
EOF;
        echo $m;
    }

}