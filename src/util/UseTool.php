<?php
/**
 * UseTool.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/10/1 0:02
 */
declare(strict_types=1);

namespace Cdyun\WebmanCrontab\util;

use GuzzleHttp\Client;

class UseTool
{
    /**
     * 成功码
     * @var int
     */
    private static int $successCode = 0;
    /**
     * 失败码
     * @var int
     */
    private static int $failCode = -1;

    /**
     * 输出日志
     * @param $msg
     * @param bool $ok
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public static function writeln($msg, bool $ok = true): void
    {
        echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . ($ok ? " [Ok] " : " [Fail] ") . PHP_EOL;
    }

    /**
     * 请求URL内容
     * @param $crontab
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public static function urlTask($crontab): array
    {
        $url = trim($crontab['target'] ?? '');
        $code = self::$successCode;
        try {
            $client = new Client();
            $response = $client->get($url);
            $log = $response->getBody()->getContents();
        } catch (\Throwable $throwable) {
            $code = self::$failCode;
            $log = $throwable->getMessage();
        }
        return ['code' => $code, 'log' => $log];
    }

    /**
     * 执行Shell任务
     * @param $crontab
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public static function shellTask($crontab): array
    {
        $code = self::$successCode;
        try {
            $log = shell_exec($crontab['target']);
        } catch (\Throwable $e) {
            $code = self::$failCode;
            $log = $e->getMessage();
        }
        return ['code' => $code, 'log' => $log];
    }

    /**
     * 执行PHP任务
     * @param $crontab
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public static function evalTask($crontab): array
    {
        $code = self::$successCode;
        try {
            $log = eval($crontab['target']);
        } catch (\Throwable $e) {
            $code = self::$failCode;
            $log = $e->getMessage();
        }
        return ['code' => $code, 'log' => $log];
    }
    /**
     * @param string|null $name - 名称
     * @param $default - 默认值
     * @return mixed
     * @author cdyun(121625706@qq.com)
     * @desc 获取配置config
     */
    public static function getConfig(?string $name = null, $default = null): mixed
    {
        if (!is_null($name)) {
            return config('plugin.cdyun.webman-crontab.app.crontab.' . $name, $default);
        }
        return config('plugin.cdyun.webman-crontab.app.crontab');
    }
}