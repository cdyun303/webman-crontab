<?php
/**
 * Task.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/30 23:09
 */

namespace Cdyun\WebmanCrontab;

use Cdyun\WebmanCrontab\util\UseRouter;
use Cdyun\WebmanCrontab\util\UseTool;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

class Task
{
    /**
     * 上下文
     * @var Context
     */
    protected Context $context;
    public function __construct()
    {
        $this->registerRouter();
    }

    /**
     * 注册路由
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public function registerRouter(): void
    {
        // 定时任务日志列表
        UseRouter::get(UseTool::getConfig('logs'), [CrontabEnforcer::class, 'logsCrontab']);
        // 重新加载定时器
        UseRouter::get(UseTool::getConfig('reload'), [CrontabEnforcer::class, 'reloadCrontab']);
        // 定时任务状态
        UseRouter::get(UseTool::getConfig('ping'), [CrontabEnforcer::class, 'pingCrontab']);

    }

    /**
     * @param Worker $worker
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public function onWorkerStart(Worker $worker)
    {
        // 初始化上下文
        $this->context = new Context($worker);

        $enforcer = new CrontabEnforcer($this->context);
        // 数据库初始化
        $enforcer->DbInit();
        // 定时任务初始化
        $enforcer->CrontabInit();
    }

    /**
     * @param TcpConnection $connection
     * @param $request
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public function onMessage(TcpConnection $connection, $request){

        // 处理 OPTIONS 预检请求
        if ($request->method() === 'OPTIONS') {
            $connection->send($this->response('', 'OK', 200));
            return;
        }

        // 确保是 HTTP 请求
        if (!$request instanceof Request) {
            $connection->send($this->response('', 'Bad Request', 400));
            return;
        }

        // 安全密钥验证
        if (isset($this->config['safe_key'])) {
            $safeKey = $request->header('safe_key');
            if ($safeKey !== $this->config['safe_key']) {
                $connection->send($this->response('', 'Error SafeKey,Connection Not Allowed!', 401));
                return;
            }
        }

        // 路由分发
        $routeHandler = UseRouter::dispatch($request->method(), $request->path());

        if (empty($routeHandler)) {
            $connection->send($this->response('', '404 Not Found', 404));
            return;
        }

        try {
            // 调用路由处理方法
            list($controller, $method) = $routeHandler;
            $controllerInstance = new $controller($this->context);
            $result = call_user_func([$controllerInstance, $method], $request);
            $connection->send($this->response($result, '信息调用成功！', 200));
        } catch (\Exception $e) {
            // 记录错误日志（可选）
            $connection->send($this->response('', 'Internal Server Error: ' . $e->getMessage(), 500));
        }
    }


    /**
     * 响应结果
     * @param mixed $data - 响应数据
     * @param string $msg - 响应消息
     * @param int $code - 响应状态码
     * @param array $headers - 额外的响应头
     * @return Response
     */
    public function response(mixed $data = '', string $msg = '信息调用成功！', int $code = 200, array $headers = []): Response
    {
        // 默认响应头
        $defaultHeaders = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0'
        ];

        // 合并自定义头
        $responseHeaders = array_merge($defaultHeaders, $headers);

        // 构建响应数据
        $responseData = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];

        // 返回 JSON 响应
        return new Response(
            $code,
            $responseHeaders,
            json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}