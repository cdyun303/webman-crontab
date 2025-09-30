webman定时器
=====
webman-crontab插件

### 安装

```
composer require cdyun/webman-crontab
```

定时器进程文件(初始化路由、初始化上下文、初始化定时器进程):

```PHP
use Cdyun\WebmanCrontab\Task;
```
```PHP
// 定时任务日志列表
UseRouter::get(UseTool::getConfig('logs'), [CrontabEnforcer::class, 'logsCrontab']);
// 重新加载定时器
UseRouter::get(UseTool::getConfig('reload'), [CrontabEnforcer::class, 'reloadCrontab']);
// 定时任务状态
UseRouter::get(UseTool::getConfig('ping'), [CrontabEnforcer::class, 'pingCrontab']);
```