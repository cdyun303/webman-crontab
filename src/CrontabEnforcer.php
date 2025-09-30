<?php
/**
 * CrontabEnforcer.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/10/1 0:48
 */
declare(strict_types=1);

namespace Cdyun\WebmanCrontab;

use Cdyun\WebmanCrontab\client\TpClient;
use Cdyun\WebmanCrontab\util\UseTool;
use InvalidArgumentException;

/**
 * CrontabEnforcer
 * @mixin TpClient
 */
class CrontabEnforcer
{
    /**
     * 上下文
     * @author cdyun(121625706@qq.com)
     */
    private Context $context;
    /**
     * 驱动
     * @var array 驱动缓存
     * @author cdyun(121625706@qq.com)
     */
    private array $drivers = [];

    /**
     * 构造函数
     * @param Context $context
     * @author cdyun(121625706@qq.com)
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * 动态调用
     *
     * @param string $method 方法名
     * @param array $arguments 参数列表
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    public function __call(string $method, array $arguments)
    {
        $driverKey = UseTool::getConfig('orm');

        // 使用缓存避免重复创建驱动实例
        if (!isset($this->drivers[$driverKey])) {
            $this->drivers[$driverKey] = $this->createDriver();
        }

        $driver = $this->drivers[$driverKey];

        // 检查方法是否存在
        if (!method_exists($driver, $method)) {
            throw new \RuntimeException("方法 {$method} 在驱动 {$driverKey} 中不存在");
        }

        return $driver->{$method}(...$arguments);
    }

    /**
     * 创建ORM驱动实例
     * @return object 驱动实例
     * @author cdyun(121625706@qq.com)
     */
    protected function createDriver(): object
    {
        $orm =UseTool::getConfig('orm');
        if ($orm === 'tp') {
            return new TpClient($this->context);
        }
        // ... 其他ORM驱动处理逻辑
        throw new InvalidArgumentException("不支持的ORM驱动类型: {$orm}");
    }
}