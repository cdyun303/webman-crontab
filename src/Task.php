<?php
/**
 * Task.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/30 23:09
 */

namespace Cdyun\WebmanCrontab;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class Task
{
    public function onWorkerStart(Worker $worker)
    {

    }

    public function onMessage(TcpConnection $connection, $request){

    }
}