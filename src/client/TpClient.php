<?php
/**
 * TpClient.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/10/1 0:31
 */
declare(strict_types=1);

namespace Cdyun\WebmanCrontab\client;

use Cdyun\WebmanCrontab\Context;
use Cdyun\WebmanCrontab\orm\TpOrm;
use Cdyun\WebmanCrontab\util\UseTool;
use Workerman\Crontab\Crontab;
use Workerman\Protocols\Http\Request;

/**
 * @mixin TpOrm
 * @method array getCrontabIds() 获取所有任务ID
 * @method array getCrontab(int $id) 获取一个任务详情, 返回的是数组
 * @method int|string updateCrontab(array $data) 更新任务,
 * @method int|string setCrontabLog(array $data) 添加任务日志
 */
class TpClient
{

    /**
     * 数据库进程池
     * @var TpOrm[] array
     * @author cdyun(121625706@qq.com)
     */
    private array $dbPool = [];
    /**
     * 调试模式
     * @var bool
     * @author cdyun(121625706@qq.com)
     */
    private bool $debug = false;
    /**
     * 上下文
     * @var Context
     * @author cdyun(121625706@qq.com)
     */
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $config = UseTool::getConfig();
        $worker = $this->context->getWorker();
        $this->dbPool[$worker->id] = new TpOrm($config);
    }

    /**
     * 定时任务数据表初始化
     * @return true
     * @author cdyun(121625706@qq.com)
     */
    public function DbInit(): bool
    {
        $worker = $this->context->getWorker();
        $this->dbPool[$worker->id]->checkTable();
        return true;
    }

    /**
     * 定时任务进程初始化
     * @return true
     * @author cdyun(121625706@qq.com)
     */
    public function CrontabInit(): bool
    {
        $this->context->clearCrontabPool();
        $worker = $this->context->getWorker();
        $ids = $this->dbPool[$worker->id]->getCrontabIds();
        if (!empty($ids)) {
            foreach ($ids as $vo) {
                $this->crontabRun($vo);
            }
        }
        return true;

    }

    /**
     * 运行定时任务
     * @param $id
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    private function crontabRun($id): void
    {
        $worker = $this->context->getWorker();
        $rs = $this->dbPool[$worker->id]->getCrontab($id);
        if (empty($rs)) {
            return;
        }

        $crontab = new Crontab($rs['rule'], function () use (&$rs, $worker) {
            $rs['running_times'] += 1;
            $this->debug && UseTool::writeln('执行定时器任务#' . $rs['id'] . ' ' . $rs['rule'] . ' ' . $rs['target']);
            $startTime = microtime(true);
            if ($rs['type'] == 1) { // url
                $result = UseTool::urlTask($rs);
            } else if ($rs['type'] == 2) { // eval
                $result = UseTool::evalTask($rs);
            } else if ($rs['type'] == 3) { // shell
                $result = UseTool::shellTask($rs);
            } else {
                $result = ['code' => -1, 'log' => '任务ID' . $rs['id'] . '执行失败, 任务类型错误.'];
            }
            $endTime = microtime(true);
            //更新任务
            $this->dbPool[$worker->id]->savaCrontab([
                'id' => $rs['id'],
                'running_times' => $rs['running_times'],
                'last_time' => date('Y-m-d H:i:s'),
            ]);
            //添加任务日志
            $this->dbPool[$worker->id]->setCrontabLog([
                'cid' => $rs['id'],
                'command' => $rs['target'],
                'output' => json_encode($result, JSON_UNESCAPED_UNICODE),
                'return_var' => $result['code'] ?? -1,
                'running_time' => round($endTime - $startTime, 6),
                'create_time' => date('Y-m-d H:i:s', time()),
                'update_time' => date('Y-m-d H:i:s', time()),
            ]);
        }, $rs['id']);

        //添加定时器
        $this->context->setCrontabPool($rs['id'], $crontab);
    }

    /**
     * （公开接口）
     * 定时器初重载
     * @return true
     * @author cdyun(121625706@qq.com)
     */
    public function reloadCrontab(): bool
    {
        $this->CrontabInit();
        return true;
    }

    /**
     * （公开接口）
     * 获取定时任务日志
     * @param Request $request
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public function logsCrontab(Request $request): array
    {
        $params = $request->get();
        if (empty($params['cid'])) {
            return [];
        }
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 100;
        // 限制最大查询条数，防止性能问题
        $limit = min($limit, 1000);
        $worker = $this->context->getWorker();
        return $this->dbPool[$worker->id]->listCrontabLogs($params['cid'], $page, $limit);
    }

    /**
     * （公开接口）
     * 测试定时任务
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    public function pingCrontab(): string
    {
        return '连接成功！';
    }
}