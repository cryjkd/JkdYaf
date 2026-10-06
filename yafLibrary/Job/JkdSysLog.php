<?php
/**
 * 储存系统请求日志
 */

namespace Job;

use Log\JkdLog;
use Task\JkdTaskInterface;

class JkdSysLog implements JkdTaskInterface
{

    public function handle($params)
    {
        $runtime = $params['runtime'];
        $route = $params['route'];
        $result = $params['result'];
        $params = $params['params'];

        JkdLog::channel('sysReq', 'runtime：' . changeReqTime($runtime) . ' -', [
            'Request' => [
                'route' => $route,
                'params' => $params
            ],
            'Response' => $result
        ]);
    }

}