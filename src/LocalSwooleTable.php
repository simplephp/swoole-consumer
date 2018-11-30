<?php
/**
 * description
 * @author         kevin <askyiwang@gmail.com>
 * @date           2018/11/16
 * @since          1.0
 */

namespace simplephp\consumer;


class LocalSwooleTable
{
    private static $_swooleTable;// 静态变量，单个进程内共享

    const SWOOLE_TABLE_SET_FAILED = -1001;
    const SWOOLE_TABLE_GET_FAILED = -1002;

    // swoole table初始化
    private function __construct() {
        //预估数据量 100个服务,每个长度30 需要3000个字节,这里申请64k
        self::$_swooleTable = new \swoole_table(65536);
        self::$_swooleTable->column('ip',\swoole_table::TYPE_STRING, 64);
        self::$_swooleTable->column('port',\swoole_table::TYPE_INT, 4);
        self::$_swooleTable->column('timestamp',\swoole_table::TYPE_INT, 4);
        self::$_swooleTable->column('bTcp',\swoole_table::TYPE_INT, 4);
        self::$_swooleTable->create();
    }

    // 获取单个swoole的实例
    public static function getInstance() {
        if(self::$_swooleTable) {
            return self::$_swooleTable;
        }
        else {
            new LocalSwooleTable();
            return self::$_swooleTable;
        }
    }
}