<?php
/**
 * Aop
 *
 * Class AOP
 */

namespace Aop;

class JkdAop
{
    /**
     * @var JkdAop
     */
    private static $instance;

    /**
     * AOP 解析结果缓存（按模块/控制器/动作缓存，避免每次请求重复反射解析）
     *
     * @var array
     */
    private static $aopCache = [];

    /**
     * Get the instance of JkdAop.
     *
     * @return JkdAop
     */
    public static function get()
    {
        if (!self::$instance) {
            self::$instance = new JkdAop();
        }
        return self::$instance;
    }


    /**
     * 获取AOP列表
     *
     * @throws \ReflectionException
     */
    public function getAopParser()
    {
        $yafRequest = jkdContext()['YAF_HTTP_REQUEST'] ?? null;
        $moduleName = $yafRequest->module ?? '';
        $controllerName = $yafRequest->controller ?? '';
        $className = $controllerName . 'Controller';
        $functionName = $yafRequest->action . 'Action';

        $cacheKey = $moduleName . '/' . $className . '/' . $functionName;
        if (isset(self::$aopCache[$cacheKey])) {
            \Swoole\Coroutine::getContext()['aopList'] = self::$aopCache[$cacheKey];
            return true;
        }

        \Yaf\Loader::import(APP_PATH . '/app/modules/' . $moduleName . '/controllers/' . $controllerName . '.php');
        $ref = new \ReflectionMethod($className, $functionName);
        $doc = $ref->getDocComment();
        $docParser = new DocParser();
        $aopList = $docParser->parse($doc);
        self::$aopCache[$cacheKey] = $aopList;
        \Swoole\Coroutine::getContext()['aopList'] = $aopList;

        return true;
    }


    /**
     * 启动AOP
     *
     * @param $type
     * @return bool
     */
    public function runAop($type)
    {
        $aopList = \Swoole\Coroutine::getContext()['aopList'] ?? [];
        $list = $aopList[$type] ?? [];
        if ($list) {
            foreach ($list as $li) {
                $thisClass = $li['class'] ?? '';
                $thisFunction = $li['function'] ?? '';
                if ($thisClass && $thisFunction) {
                    $thisInstance = new $thisClass();
                    $thisInstance->$thisFunction();
                }
            }
        }

        return true;
    }

}
