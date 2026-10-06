<?php
/**
 * 防止重复请求
 *
 * Class PreventDuplication
 */

namespace Jkd;

class JkdPreventDuplication
{

    /**
     * 检查是否通过
     *
     * @return mixed
     */
    public static function check($type, $ttl = 3)
    {
        $redisPool = new \Cache\Redis();
        $redis = $redisPool->get();

        $key = 'PREVENTDUPLICATION' . $type;
        // 使用 setNx 保证只在 key 不存在时设置，兼容各版本 Swoole Redis 客户端
        $rs = $redis->setNx($key, 1);
        if ($rs) {
            $redis->expire($key, $ttl);
        }

        $redisPool->put();
        return $rs;
    }

}