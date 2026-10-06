<?php
/**
 * @name JkdAuth
 * @deprecated 登陆验证中间价
 * @author JKD
 * @date 2021年10月24日 10:54
 */

namespace Middleware;

use Auth\JwtAuth;
use Jkd\JkdResponse;

class JkdAuth
{

    public function handle()
    {
        //请求参数
        $params = jkdContext()['REQUEST_PARAMS'] ?? [];
        $token = $params['token'] ?? '';
        if (!$token) {
            JkdResponse::Fail('Missing Token');
        }
        $userKey = JwtAuth::checkToken($token);
        if (!$userKey) {
            JkdResponse::Fail('Invalid Token');
        }

        return true;
    }

}