<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace simplephp\consumer;

class Process
{
    const CHILD_PROCESS_CAN_RESTART         = 'staticWorker';               //子进程可以重启,进程个数固定
    const CHILD_PROCESS_CAN_NOT_RESTART     = 'dynamicWorker';              //子进程不可以重启，进程个数根据队列堵塞情况动态分配
    const STATUS_RUNNING                    = 'runnning';                   //主进程running状态
    const STATUS_WAIT                       = 'wait';                       //主进程wait状态
    const STATUS_STOP                       = 'stop';                       //主进程stop状态
    const APP_NAME                          = 'swoole-jobs';                //app name
    const STATUS_HSET_KEY_HASH              = 'status';                     //status hash名

    public $mpid                            = 0;
    public $works                           = [];
    public $max_precess                     = 5;
    public $new_index                       = 0;
    public $master_name                     = "swoole-consumer-master";
    public $work_name                       = "swoole-consumer-work:%s";
    private $version                        = '2.5';
    private $excute_time                    = 3600;                         //子进程最长执行时间,单位：秒
    private $status;

    public function __construct()
    {

        $this->setProcessName(sprintf('swoole-consumer:%s', 'master'));
        $this->mpid = posix_getpid();

    }

    /**
     * 启动进程
     */
    public function start()
    {
        //每个topic开启最少个进程消费队列
        for ($i = 0; $i < $this->max_precess; ++$i) {
            $this->createProcess($i, 'Message', 1);
        }
        $this->processingSignal();
    }

    /**
     * 创建进程
     * @param null $index
     * @param $topic_name
     * @param $status
     * @return int
     */
    public function createProcess($index = null, $topic_name, $status)
    {
        $process = new \Swoole\Process(function (\Swoole\Process $worker) use ($index) {
            if (is_null($index)) {
                $index = $this->new_index;
                $this->new_index++;
            }
            $begin_time = microtime(true);
            try {
                //设置进程名字
                $i = 1;
                $this->setProcessName(sprintf($this->work_name, $index));
                do {
                    $this->checkMasterStatus($worker);
                    echo '正在处理任务:'.$i.PHP_EOL;
                    $condition = true;
                    //$condition = ((self::STATUS_RUNNING == $this->status) && (time() < ($begin_time + $this->excute_time)) ? true : false);
                    $i++;
                } while ($condition);
            } catch (\Throwable $e) {
                echo  '1';
            } catch (\Exception $e) {
                echo  '2';
            }
        }, false, false);
        $pid = $process->start();
        // 保存当前对象
        if($pid !== false) {
            $this->works[$pid] = $process;
        }
        return $pid;
    }

    /**
     * 检查 master 是否存活
     * @param $worker
     */
    public function checkMasterStatus(&$worker)
    {
        if (!\Swoole\Process::kill($this->mpid, 0)) {
            $worker->exit();
            // 这句提示,实际是看不到的.需要写到日志中
            echo "Master process exited, I [{$worker['pid']}] also quit\n";
        }
    }

    /**
     *  进程信号处理
     */
    public function processingSignal()
    {
        //强制退出
        \Swoole\Process::signal(SIGTERM, function ($signo) {
            $this->killWorkersAndExitMaster();
        });
        //强制退出
        \Swoole\Process::signal(SIGKILL, function ($signo) {
            $this->killWorkersAndExitMaster();
        });
        //平滑退出
        \Swoole\Process::signal(SIGUSR1, function ($signo) {
            $this->waitWorks();
        });
        //记录进程状态
        \Swoole\Process::signal(SIGUSR2, function ($signo) {

            //echo $result;
        });

        // 信号发生时可能同时有多个子进程退出
        \Swoole\Process::signal(SIGCHLD, function ($sig) {
            //在异步信号回调中执行wait
            while ($ret = \Swoole\Process::wait(false)) {
                $pid = $ret['pid'];
                // 主进程是否存活、存活则可以恢复子进程 todo
                $new_process_id  = $this->works[$pid]->start();
                // 尝试启动新的进程 todo
                if($new_process_id) {
                    echo '正在重启进程ID:'.$pid.',新进程ID:'.$new_process_id.PHP_EOL;
                    $this->works[$new_process_id] = $this->works[$pid];
                    unset($this->works[$pid]);
                }

                // 如果启动成功，处理之前的进程 todo

            }
        });
    }

    /**
     * 平滑等待子进程退出之后，再退出主进程
     */
    private function waitWorks()
    {
        $data['pid'] = $this->mpid;
        $data['status'] = self::STATUS_WAIT;
        //$this->saveMasterData($data);
        $this->status = self::STATUS_WAIT;
    }

    /**
     * 平滑退出，先强制 kill 子进程，再退出 master 进程
     */
    private function killWorkersAndExitMaster()
    {
        $this->status = self::STATUS_STOP;
        if ($this->works) {
            foreach ($this->works as $pid => $worker) {
                //强制杀workers子进程
                @\Swoole\Process::kill($pid);
                unset($this->works[$pid]);
            }
        }
        $this->exitMaster();
    }

    /**
     * 处理 master 进程
     */
    private function exitMaster()
    {
        @unlink($this->pidFile);
        @unlink($this->pidInfoFile);
        sleep(1);
        exit();
    }

    /**
     * 设置进程名 【mac os】不支持进程重命名
     *
     * @param mixed $name
     */
    private function setProcessName($name)
    {
        if (function_exists('swoole_set_process_name') && PHP_OS != 'Darwin') {
            swoole_set_process_name($name);
        }
    }
}
