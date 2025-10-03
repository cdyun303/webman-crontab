<?php
/**
 * UseTool.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/10/1 0:02
 */
declare(strict_types=1);

namespace Cdyun\WebmanCrontab\util;

use GuzzleHttp\Client;
use InvalidArgumentException;

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

    /**
     * 生成crontab规则字符串
     * @param array $data 包含5个元素的数组，分别表示：类型、星期、日期、小时、分钟
     *                    [type, week, day, hour, minute]
     * @return string 返回生成的crontab规则字符串，格式为"分钟 小时 日期 月份 星期"，失败时返回空字符串
     * @author cdyun(121625706@qq.com)
     */
    public static function generateCrontabRule(array $data): string
    {
        // 验证输入数组长度
        if (empty($data) || count($data) != 5) {
            throw new InvalidArgumentException('Crontab 数据格式错误');
        }
        // 解构并验证必需的参数存在
        list($type, $week, $day, $hour, $minute) = $data;
        // 验证type参数
        if (!is_numeric($type) || $type < 1 || $type > 7) {
            throw new InvalidArgumentException('Crontab 类型错误');
        }
        $rule = match ((int)$type) {
            //type=1: 每天指定时间执行
            1 => [$minute, $hour, '*', '*', '*'],
            //type=2: 每隔指定天数执行
            2 => [$minute, $hour, '0/' . $day, '*', '*'],
            //type=3: 每小时执行
            3 => [$minute, '*', '*', '*', '*'],
            //type=4: 每隔指定小时执行
            4 => [$minute, '0/' . $hour, '*', '*', '*'],
            //type=5: 每隔指定分钟执行
            5 => ['0/' . $minute, '*', '*', '*', '*'],
            //type=6: 每周指定星期几执行
            6 => [$minute, $hour, '?', '*', $week],
            //type=7: 每月指定日期执行
            7 => [$minute, $hour, $day, '*', '?'],
            default => throw new InvalidArgumentException('Crontab 类型错误'),
        };

        return empty($rule) ? '' : implode(' ', $rule);
    }
    /**
     * 生成crontab提示信息
     * @param array $data
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    public static function generateCrontabTips(array $data): string
    {
        // 验证输入数组长度
        if (empty($data) || count($data) !== 5) {
            throw new InvalidArgumentException('Crontab 数据格式错误');
        }

        // 解构并验证必需的参数存在
        list($type, $week, $day, $hour, $minute) = $data;

        // 验证type参数
        if (!is_numeric($type) || $type < 1 || $type > 7) {
            throw new InvalidArgumentException('Crontab 类型错误');
        }

        // 校验各字段是否为有效数值
        if (!is_numeric($hour) || $hour < 0 || $hour > 23) {
            throw new InvalidArgumentException('Crontab 小时数据错误');
        }

        if (!is_numeric($minute) || $minute < 0 || $minute > 59) {
            throw new InvalidArgumentException('Crontab 分钟数据错误');
        }

        if (!is_numeric($day) || $day < 1 || $day > 31) {
            throw new InvalidArgumentException('Crontab 日/天数据错误');
        }

        if (!is_numeric($week) || $week < 1 || $week > 7) {
            throw new InvalidArgumentException('Crontab 周数据错误');
        }

        return match ((int)$type) {
            1 => sprintf('每天, %d点%d分 执行', $hour, $minute),
            2 => sprintf('每隔%d天, %d点%d分 执行', $day, $hour, $minute),
            3 => sprintf('每小时, 第%d分钟 执行', $minute),
            4 => sprintf('每隔%d小时, 第%d分钟 执行', $hour, $minute),
            5 => sprintf('每隔%d分钟执行', $minute),
            6 => sprintf('每%s, %d点%d分 执行', self::getWeekName($week), $hour, $minute),
            7 => sprintf('每月, %d日 %d点%d分 执行', $day, $hour, $minute),
            default => throw new InvalidArgumentException('Crontab 类型错误'),
        };
    }

    /**
     * 将数字星期映射为中文名称
     * @param int $week
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    public static function getWeekName(int $week): string
    {
        $weeks = [
            1 => '周一',
            2 => '周二',
            3 => '周三',
            4 => '周四',
            5 => '周五',
            6 => '周六',
            7 => '周日'
        ];

        return $weeks[$week] ?? '未知';
    }
}