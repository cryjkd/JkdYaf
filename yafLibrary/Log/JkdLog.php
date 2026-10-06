<?php
/**
 * 日志类
 */
namespace Log;

class JkdLog
{

    /**
     * 感兴趣的事件。像用户登录，SQL日志
     *
     * @param $message //信息
     * @param array $content //数据
     */
    public static function info($message, $content = [])
    {
        self::writeLog($message, $content, 'info');
    }


    /**
     * 运行时发生了错误，错误需要记录下来并监视，但错误不需要立即处理。
     *
     * @param $content //数据
     */
    public static function error($content)
    {
        $content = is_array($content) ? $content : (array)$content;
        self::writeLog('Runtime error', $content, 'error');
    }


    /**
     * 详细的debug信息
     *
     * @param $message //信息
     * @param array $content //数据
     */
    public static function debug($message, $content = [])
    {
        self::writeLog($message, $content, 'debug');
    }


    /**
     * 指定通道信息
     *
     * @param string $channel //通道
     * @param $message //信息
     * @param array $content //数据
     */
    public static function channel($channel, $message, $content = [])
    {
        self::writeLog($message, $content, $channel);
    }


    /**
     * 写日志
     *
     * @param string $message
     * @param array $content
     * @param string $channel
     */
    protected static function writeLog($message, $content, $channel = '')
    {
        $logPath = \Yaf\Registry::get('config')->log['path'] ?? APP_PATH . '/runtime/log/';   //日志路径
        $dir = $logPath . $channel;
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $filename = ($channel ?: 'jkd') . '-' . date('Y-m-d') . '.log';
        $logFile = $dir . '/' . $filename;

        // 异步写入：文件写是阻塞 IO，放入独立协程避免阻塞事件循环
        \Swoole\Coroutine::create(function () use ($message, $content, $logFile) {
            if (!is_string($content)) {
                $content = json_encode($content);
            }
            $str = '[' . date('Y-m-d H:i:s') . ']' . ' ' . $message . ' ' . $content . PHP_EOL;
            \Swoole\Coroutine\System::writeFile($logFile, $str, FILE_APPEND);
        });
    }


}