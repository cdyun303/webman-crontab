<?php
/**
 * Context.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/10/1 0:41
 */
declare(strict_types=1);

namespace Cdyun\WebmanCrontab;

use Workerman\Crontab\Crontab;
use Workerman\Worker;

class Context
{
    /**
     * worker 实例
     * @var Worker
     * @author cdyun(121625706@qq.com)
     */
    private Worker $worker;

    /**
     * 定时任务进程池
     * @var Crontab[]
     * @author cdyun(121625706@qq.com)
     */
    private array $crontabPool = [];

    /**
     * Context 构造函数
     * @param Worker $worker - Workerman工作进程实例
     * @author cdyun(121625706@qq.com)
     */
    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
    }

    /**
     * 获取 worker 实例
     * @return Worker
     * @author cdyun(121625706@qq.com)
     */
    public function getWorker(): Worker
    {
        return $this->worker;
    }

    /**
     * 获取定时任务进程池
     * @param int|null $id
     * @return Crontab|Crontab[]
     * @author cdyun(121625706@qq.com)
     */
    public function getCrontabPool(int $id = null): Crontab|array
    {
        return $id ? $this->crontabPool[$id] : $this->crontabPool;
    }

    /**
     * 设置定时任务进程池
     * @param int $id
     * @param Crontab $crontab
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public function setCrontabPool(int $id, Crontab $crontab): void
    {
        $this->crontabPool[$id] = $crontab;
    }

    /**
     * 删除指定定时任务进程池
     * @param int $id
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public function removeCrontabPool(int $id): void
    {
        unset($this->crontabPool[$id]);
    }

    /**
     * 清空定时任务进程池
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public function clearCrontabPool(): void
    {
        $pool = $this->crontabPool;
        foreach ($pool as $crontab) {
            $crontab->destroy();
        }
        $this->crontabPool = [];
    }
}