<?php

namespace Jkd;

/**
 * 响应类
 *
 * Class JkdResponse
 */
class JkdResponse
{

    /**
     * 返回json
     *
     * @param $array
     * @throws Exception
     */
    public static function output($array, $isException = false)
    {
        // 存入协程上下文，由 HttpServer 统一读取后 json_encode 返回，
        // 避免 echo + ob 捕获造成的二次 JSON 编解码，且协程隔离更安全。
        jkdContext()['jkdResponse'] = $array;
        if ($isException) {
            throw new \Exception('JkdReturn', 676);
        } else {
            return true;
        }
    }


    /**
     * 成功返回json
     *
     * @param string $data
     * @param string $message
     * @param int $status
     * @param int $code
     * @throws Exception
     */
    public static function Success($data = "", $message = "success", $status = 200, $code = 1)
    {
        $outArray = ['code' => $code, 'message' => $message, 'data' => $data, 'status' => $status];
        return self::output($outArray);
    }


    /**
     * 失败返回json
     *
     * @param string $message
     * @param int $status
     * @param int $code
     * @throws Exception
     */
    public static function Fail($message = "fail", $status = 200, $code = 2)
    {
        $outArray = ['code' => $code, 'message' => $message, 'status' => $status];
        return self::output($outArray, true);
    }


    /**
     * 错误返回json
     *
     * @param string $message
     * @param int $status
     * @param int $code
     * @throws Exception
     */
    public static function Error($message = "500 System error！", $status = 500, $code = 2)
    {
        $outArray = ['code' => $code, 'message' => $message, 'status' => $status];
        return self::output($outArray, true);
    }


    /**
     * debug返回json
     *
     * @param string $data
     * @param string $message
     * @param int $status
     * @param int $code
     * @throws Exception
     */
    public static function Debug($data = "", $message = "debug", $status = 400, $code = 3)
    {
        $outArray = ['code' => $code, 'message' => $message, 'data' => $data, 'status' => $status];
        return self::output($outArray, true);
    }

}
