<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/6/29
 * @since          1.0
 */

namespace simplephp\consumer\log;

class Logger
{

    const LEVEL_ERROR = 0x01;

    const LEVEL_WARNING = 0x02;

    const LEVEL_INFO = 0x04;

    const LEVEL_TRACE = 0x08;

    const LEVEL_PROFILE = 0x40;

    const LEVEL_PROFILE_BEGIN = 0x50;

    const LEVEL_PROFILE_END = 0x60;

    public $messages = [];

    public $flushInterval = 1000;

    public $traceLevel = 0;

    public $dispatcher;


    public function __construct()
    {
        register_shutdown_function(function () {
            $this->flush();
            register_shutdown_function([$this, 'flush'], true);
        });
    }

    /**
     * Initializes the logger by registering [[flush()]] as a shutdown function.
     */
    public function init()
    {

    }

    /**
     * @param $message
     * @param $level
     * @param string $category
     */
    public function log($message, $level, $category = 'application')
    {
        $time = microtime(true);
        $traces = [];
        $this->messages[] = [$message, $level, $category, $time, $traces, memory_get_usage()];
        if ($this->flushInterval > 0 && count($this->messages) >= $this->flushInterval) {
            $this->flush();
        }
    }

    /**
     * Flushes log messages from memory to targets.
     * @param bool $final whether this is a final call during a request.
     */
    public function flush($final = false)
    {
        $messages = $this->messages;
        $this->messages = [];
        if ($this->dispatcher instanceof Dispatcher) {
            $this->dispatcher->dispatch($messages, $final);
        }
    }

    /**
     * Returns the total elapsed time since the start of the current request.
     * This method calculates the difference between now and the timestamp
     * defined by constant `YII_BEGIN_TIME` which is evaluated at the beginning
     * of [[\yii\BaseYii]] class file.
     * @return float the total elapsed time in seconds for current request.
     */
    public function getElapsedTime()
    {
        return microtime(true) - YII_BEGIN_TIME;
    }

    /**
     * Returns the profiling results.
     *
     * By default, all profiling results will be returned. You may provide
     * `$categories` and `$excludeCategories` as parameters to retrieve the
     * results that you are interested in.
     *
     * @param array $categories list of categories that you are interested in.
     * You can use an asterisk at the end of a category to do a prefix match.
     * For example, 'yii\db\*' will match categories starting with 'yii\db\',
     * such as 'yii\db\Connection'.
     * @param array $excludeCategories list of categories that you want to exclude
     * @return array the profiling results. Each element is an array consisting of these elements:
     * `info`, `category`, `timestamp`, `trace`, `level`, `duration`, `memory`, `memoryDiff`.
     * The `memory` and `memoryDiff` values are available since version 2.0.11.
     */
    public function getProfiling($categories = [], $excludeCategories = [])
    {
        $timings = $this->calculateTimings($this->messages);
        if (empty($categories) && empty($excludeCategories)) {
            return $timings;
        }

        foreach ($timings as $i => $timing) {
            $matched = empty($categories);
            foreach ($categories as $category) {
                $prefix = rtrim($category, '*');
                if (($timing['category'] === $category || $prefix !== $category) && strpos($timing['category'], $prefix) === 0) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                foreach ($excludeCategories as $category) {
                    $prefix = rtrim($category, '*');
                    foreach ($timings as $i => $timing) {
                        if (($timing['category'] === $category || $prefix !== $category) && strpos($timing['category'], $prefix) === 0) {
                            $matched = false;
                            break;
                        }
                    }
                }
            }

            if (!$matched) {
                unset($timings[$i]);
            }
        }

        return array_values($timings);
    }

    /**
     * Returns the statistical results of DB queries.
     * The results returned include the number of SQL statements executed and
     * the total time spent.
     * @return array the first element indicates the number of SQL statements executed,
     * and the second element the total time spent in SQL execution.
     */
    public function getDbProfiling()
    {
        $timings = $this->getProfiling(['yii\db\Command::query', 'yii\db\Command::execute']);
        $count = count($timings);
        $time = 0;
        foreach ($timings as $timing) {
            $time += $timing['duration'];
        }

        return [$count, $time];
    }

    /**
     * Calculates the elapsed time for the given log messages.
     * @param array $messages the log messages obtained from profiling
     * @return array timings. Each element is an array consisting of these elements:
     * `info`, `category`, `timestamp`, `trace`, `level`, `duration`, `memory`, `memoryDiff`.
     * The `memory` and `memoryDiff` values are available since version 2.0.11.
     */
    public function calculateTimings($messages)
    {
        $timings = [];
        $stack = [];

        foreach ($messages as $i => $log) {
            list($token, $level, $category, $timestamp, $traces) = $log;
            $memory = isset($log[5]) ? $log[5] : 0;
            $log[6] = $i;
            $hash = md5(json_encode($token));
            if ($level == self::LEVEL_PROFILE_BEGIN) {
                $stack[$hash] = $log;
            } elseif ($level == self::LEVEL_PROFILE_END) {
                if (isset($stack[$hash])) {
                    $timings[$stack[$hash][6]] = [
                        'info' => $stack[$hash][0],
                        'category' => $stack[$hash][2],
                        'timestamp' => $stack[$hash][3],
                        'trace' => $stack[$hash][4],
                        'level' => count($stack) - 1,
                        'duration' => $timestamp - $stack[$hash][3],
                        'memory' => $memory,
                        'memoryDiff' => $memory - (isset($stack[$hash][5]) ? $stack[$hash][5] : 0),
                    ];
                    unset($stack[$hash]);
                }
            }
        }

        ksort($timings);

        return array_values($timings);
    }


    /**
     * Returns the text display of the specified level.
     * @param int $level the message level, e.g. [[LEVEL_ERROR]], [[LEVEL_WARNING]].
     * @return string the text display of the level
     */
    public static function getLevelName($level)
    {
        static $levels = [
            self::LEVEL_ERROR => 'error',
            self::LEVEL_WARNING => 'warning',
            self::LEVEL_INFO => 'info',
            self::LEVEL_TRACE => 'trace'
        ];

        return isset($levels[$level]) ? $levels[$level] : 'unknown';
    }
}
