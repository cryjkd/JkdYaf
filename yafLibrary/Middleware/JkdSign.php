<?php
/**
 * @name JkdSign
 * @deprecated 签名中间价
 * @author JKD
 * @date 2021年10月24日 11:00
 */

namespace Middleware;

use Jkd\JkdResponse;

class JkdSign
{

    public function handle()
    {
        //请求参数
        $params = $GLOBALS['REQUEST_PARAMS'] ?? [];
        //验证请求时的时间戳
        $apiTs = \Yaf\Registry::get('config')->apiTs ?? 60;
        if (!isset($params['ts']) || $params['ts'] > time() || (time() - $params['ts'] > (int)$apiTs)) {
            JkdResponse::Fail('Exception request');
        }

        if (!isset($params['sign'])) {
            JkdResponse::Fail('Signature missing');
        }

        $apiAuth = new \Auth\ApiAuth();
        if ($apiAuth->getSign($params) != $params['sign']) {
            JkdResponse::Fail('Signature Error');
        }

        return true;
    }

}