<?php
/**
 * TpOrm.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/10/1 0:12
 */
declare(strict_types=1);

namespace Cdyun\WebmanCrontab\orm;

use InvalidArgumentException;
use support\think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class TpOrm
{
    /**
     * 定时任务日志表后缀 按月分表
     * @var string|null
     * @author cdyun(121625706@qq.com)
     */
    private ?string $recordSuffix = null;
    /**
     * 定时任务日志表
     * @var string
     * @author cdyun(121625706@qq.com)
     */
    private string $cronRecord;
    /**
     * 定时任务表
     * @var string
     * @author cdyun(121625706@qq.com)
     */
    private string $cronTable;

    /**
     * 表前缀
     * @var string
     * @author cdyun(121625706@qq.com)
     */
    private string $prefix;

    public function __construct($config = [])
    {
        $this->prefix = $config['prefix'] ?? $this->getTablePrefix();
        $this->cronRecord = $config['log_table'] ?? 'crontab_log';
        $this->cronTable = $config['crontab_table'] ?? 'crontab';
    }


    /**
     * 获取表前缀
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    public function getTablePrefix(): string
    {
        $config = Db::getConfig();
        $name = !empty($config['default']) ? $config['default'] : 'mysql';
        $connections = $config['connections'];
        if (!isset($connections[$name])) {
            throw new InvalidArgumentException('Undefined db config:' . $name);
        }
        $mysqlConfig = $connections[$name];
        return !empty($mysqlConfig['prefix']) ? $mysqlConfig['prefix'] : 'web_';
    }

    /**
     * 检查表是否存在
     * @return true
     * @author cdyun(121625706@qq.com)
     */
    public function checkTable(): bool
    {
        $date = $this->generateRecordSuffix();
        if ($date !== $this->recordSuffix) {
            $this->recordSuffix = $date;
            $allTables = $this->getAllTable();

            $cronTable = $this->prefix . $this->cronTable;
            !in_array($cronTable, $allTables) && $this->createCronTable();

            $this->cronRecord = $this->getCronRecordNow(); //初始化赋值日志表名称

            $cronRecord = $this->prefix . $this->cronRecord;
            !in_array($cronRecord, $allTables) && $this->createCronLogs();
        }
        return true;
    }

    /**
     * 获取所有表名
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public function getAllTable(): array
    {
        $tables = Db::query("SHOW TABLES");
        $tableNames = [];
        foreach ($tables as $table) {
            $tableNames[] = current($table);
        }
        return $tableNames;
    }

    /**
     * 生成当前记录表后缀，可以自定义规则
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    public function generateRecordSuffix(): string
    {
        // 每天生成一张表
        return date('Ymd');

//        // 每月生成一张表
//        return date('Ym');
//
//        // 每月5号、15号、25号生成一张表
//        $day = (int)date('d');
//        $baseDate = date('Ym');
//
//        if ($day < 5) {
//            // 1-4日调整为上个月的25日
//            $targetDate = date('Ym', strtotime('last month')) . '25';
//        } elseif ($day < 15) {
//            // 5-14日调整为5日
//            $targetDate = $baseDate . '05';
//        } elseif ($day < 25) {
//            // 15-24日调整为15日
//            $targetDate = $baseDate . '15';
//        } else {
//            // 25-31日调整为25日
//            $targetDate = $baseDate . '25';
//        }
//        return $targetDate;
    }

    /**
     * 获取当前记录表名称
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    public function getCronRecordNow(): string
    {
        return $this->cronRecord . "_" . $this->generateRecordSuffix();
    }

    /**
     * 创建定时器任务表
     * @author cdyun(121625706@qq.com)
     */
    private function createCronTable()
    {
        $tableName = $this->prefix . $this->cronTable;
        $sql = <<<SQL
 CREATE TABLE IF NOT EXISTS `{$tableName}`  (
  `id` int(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '任务标题',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '任务类型[1:url,2:eval,3:shell]',
  `cycle` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '执行周期',
  `rule` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''  COMMENT '任务表达式',
  `target` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''  COMMENT '执行脚本',
  `running_times` int(0) NOT NULL DEFAULT 0 COMMENT '已运行次数',
  `last_time` datetime(0) NOT NULL COMMENT '最近运行时间',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '任务状态状态[0:禁用;1启用]',
  `op_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作人',
  `create_time` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) COMMENT '新增时间',
  `update_time` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '更新时间',
  `delete_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `title`(`title`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '定时器任务表' ROW_FORMAT = Dynamic
SQL;

        return Db::query($sql);
    }

    /**
     * 创建定时器任务记录表
     * @author cdyun(121625706@qq.com)
     */
    private function createCronLogs()
    {
        $tableName = $this->prefix . $this->cronRecord;
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$tableName}`  (
  `id` int(0) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `cid` int(0) NOT NULL DEFAULT 0 COMMENT '任务id',
  `command` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '执行命令',
  `output` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '执行输出',
  `return_var` tinyint(4) NOT NULL DEFAULT 0  COMMENT '执行返回状态[0：成功; 非0：失败]',
  `running_time` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''  COMMENT '执行所用时间',
  `create_time` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) COMMENT '新增时间',
  `update_time` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '更新时间',
  `delete_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '定时器任务流水表{$this->recordSuffix}' ROW_FORMAT = Dynamic
SQL;

        return Db::query($sql);
    }

    /**
     * 获取所有定时任务id
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public function getCrontabIds(): array
    {
        return Db::name($this->cronTable)
            ->where('status', 1)
            ->whereNull('delete_time')
            ->column('id');
    }

    /**
     * 获取定时任务详情
     * @param $id
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public function getCrontab($id): array
    {
        return Db::name($this->cronTable)
            ->where('status', 1)
            ->where('id', $id)
            ->whereNull('delete_time')
            ->findOrEmpty();
    }

    /**
     * 新增/更新定时任务
     * @param array $data
     * @return int
     * @author cdyun(121625706@qq.com)
     */
    public function savaCrontab(array $data): int
    {
        return Db::name($this->cronTable)->save($data);
    }

    /**
     * 添加任务日志
     * @param array $data
     * @return int
     * @author cdyun(121625706@qq.com)
     */
    public function setCrontabLog(array $data): int
    {
        return Db::name($this->cronRecord)->save($data);

    }

    /**
     * 获取任务日志
     * @param mixed $cid
     * @param int $limit
     * @param int $page
     * @return array
     * @author cdyun(121625706@qq.com)
     * 
     */
    public function listCrontabLogs(mixed $cid, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        try {
            return Db::name($this->getCronRecordNow())
                ->where('cid', $cid)
                ->whereNull('delete_time')
                ->limit($offset, $limit)
                ->order('id', 'desc')
                ->select()
                ->toArray();
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return [];
        }
    }
}