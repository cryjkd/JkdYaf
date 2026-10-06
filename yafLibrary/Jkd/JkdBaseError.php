<?php
/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @see http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 * @author root
 */
namespace Jkd;

use Log\JkdLog;

class JkdBaseError extends  \Yaf\Controller_Abstract
{
    //从2.1开始, errorAction支持直接通过参数获取异常
    public function errorAction($exception)
    {
        if ($exception->getCode() != 676) {
            JkdLog::error($exception);
            // 返回统一的 500 响应（不再返回空数组 + 200）
            jkdContext()['jkdResponse'] = ['code' => 2, 'message' => '500 System error!', 'status' => 500];
        }
    }
}
