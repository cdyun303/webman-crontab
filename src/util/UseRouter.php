<?php
/**
 * UseRouter.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/10/1 0:07
 */
declare(strict_types=1);

namespace Cdyun\WebmanCrontab\util;

class UseRouter
{
    /**
     * 路由集合
     * @var array
     */
    private static array $routes = [];

    /**
     * 注册Get路由
     * @param string $uri
     * @param array $callback
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public static function get(string $uri, array $callback): void
    {
        static::addRoute('GET', $uri, $callback);
    }

    /**
     * 注册路由的通用方法
     * @param string $method
     * @param string $uri
     * @param array $callback
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    private static function addRoute(string $method, string $uri, array $callback): void
    {
        static::$routes[] = [
            'method' => $method,
            'uri' => $uri,
            'callback' => $callback
        ];
    }

    /**
     * 注册Post路由
     * @param string $uri
     * @param array $callback
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public static function post(string $uri, array $callback): void
    {
        static::addRoute('POST', $uri, $callback);
    }

    /**
     * 路由分发
     * @param string $method
     * @param string $uri
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public static function dispatch(string $method, string $uri): array
    {
        foreach (static::$routes as $vo) {
            if ($vo['method'] === $method && $vo['uri'] === $uri) {
                return $vo['callback'];
            }
        }
        return [];
    }
}