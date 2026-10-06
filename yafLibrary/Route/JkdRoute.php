<?php
/**
 * @name JkdRoute
 * @author JKD
 * @date 2021年10月24日 02:58
 */

namespace Route;


use Log\JkdLog;

class JkdRoute
{
    /**
     * @var JkdRoute
     */
    private static $instance;

    private static $routeList;

    /**
     * Get the instance of JkdRoute.
     *
     * @return JkdRoute
     */
    public static function get()
    {
        if (!self::$instance) {
            self::$instance = new JkdRoute();
            self::$routeList = \Yaf\Registry::get('JkdRouteList');
        }
        return self::$instance;
    }


    public function getRoutePrefixs()
    {
        return \Yaf\Registry::get('JkdRoutePrefixList');
    }


    /**
     * 获取路由信息
     *
     * @param $uri
     * @return array|mixed
     */
    public function getRoute($uri)
    {
        return self::$routeList[$uri] ?? [];
    }


    /**
     * 检查路由是否存在
     *
     * @param $uri
     * @return bool
     */
    public function checkRouteExist($uri)
    {
        return isset(self::$routeList[$uri]) ? true : false;
    }


    /**
     * 获取路由中间价
     *
     * @param $uri
     * @return array|mixed
     */
    public function getRouteMiddleware()
    {
        $serverData = jkdContext()['REQUEST_SERVER'] ?? [];
        $route = $serverData['request_uri'] ?? '';  //客户端请求的路由
        return self::$routeList[$route]['middleware'] ?? [];
    }

}