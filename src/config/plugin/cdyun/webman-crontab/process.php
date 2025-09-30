<?php
/**
 * process.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/30 22:59
 */

use Cdyun\WebmanCrontab\Task;

return [
    'crontab_task' => [
        'handler' => Task::class,
        'count' => 1,
        'listen' => config('plugin.cdyun.webman-crontab.app.crontab.listen'),
    ]
];