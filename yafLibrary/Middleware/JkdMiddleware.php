<?php
/**
 * @name JkdMiddleware
 * @deprecated 中间价
 * @author JKD
 * @date 2021年10月24日 03:12
 */

namespace Middleware;

use Route\JkdRoute;

class JkdMiddleware
{
    /**
     * @var JkdMiddleware
     */
    private static $instance;

    /**
     * Get the instance of JkdMiddleware.
     *
     * @return JkdMiddleware
     */
    public static function get()
    {
        if (!self::$instance) {
            self::$instance = new JkdMiddleware();
        }
        return self::$instance;
    }


    public function handle()
    {
        $middlewareArr = JkdRoute::get()->getRouteMiddleware();

        foreach ($middlewareArr as $type => $middleware) {
            foreach ($middleware as $middle) {
                if ($type == 'app') {
                    $className = 'app\middleware\\' . $middle;
                } else {
                    $className = 'Middleware\\' . $middle;
                }
                $thisMiddleware = new $className();
                $thisMiddleware->handle();
            }
        }
    }

}