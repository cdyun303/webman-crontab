<?php
return [
    'enable' => true,
    'crontab' => [
        // 监听地址
        'listen' => 'http://127.0.0.1:2345',
        // 定时器密钥
        'safe_key' => 'cdyun2025',
        // 数据库ORM驱动，默认值tp
        'orm' => 'tp',
        // 数据表前缀，默认值为数据库配置的前缀或web_
        'prefix' => 'web_',
        // 定时器数据库表，默认值crontab
        'crontab_table' => 'crontab',
        // 定时器日志表，默认值crontab_log
        'log_table' => 'crontab_log',
        // 定时器接口 - 重启
        'reload' => '/crontab/reload',
        // 定时器接口 - ping
        'ping' => '/crontab/ping',
        // 定时器接口 - 日志
        'logs' => '/crontab/logs',
    ],
];