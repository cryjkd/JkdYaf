<?php

include __DIR__ . "/bin/Jkd.php";

use Swoole\Coroutine\Http\Server;
use Swoole\Process;

define('JKDYAF_VERSION', '2.2.3');

class HttpServer
{
    private $jkdYafConfig;
    private $app;

    protected static $instance = null;
    protected static $daemonize = null;

    private $ip;
    private $port;

    private function __construct()
    {
        // 获取JKdYaf配置文件
        $this->jkdYafConfig = parse_ini_file(CONF_PATH . "/jkdYaf.ini", true);

        $this->ip = $this->jkdYafConfig['common']['ip'] ?? '';
        $this->port = $this->jkdYafConfig['common']['port'] ?? '';
    }


    /**
     * @param string $argv2
     * @return HttpServer|null
     */
    public static function getInstance($argv2 = '')
    {
        $daemonize = ($argv2 ?? '') == '-d' ? true : false; //守护进程  true|false

        if (empty(self::$instance) || !(self::$instance instanceof HttpServer)) {
            self::$instance = new self();
            self::$daemonize = $daemonize;
        }

        return self::$instance;
    }


    /**
     * 启动
     *
     * @return bool
     */
    public function start()
    {
        $pidFile = $this->jkdYafConfig['common']['pid_file'];
        $pids = is_file($pidFile) ? file_get_contents($pidFile) : '';
        if ($pids) {
            return \Jkd::echoStr('JkdYaf is running');
        } else {
            \Jkd::start($this->ip, $this->port, self::$daemonize);
            return $this->onManagerStart();
        }
    }


    /**
     * 停止
     *
     * @return bool
     */
    public function stop()
    {
        $pidFile = $this->jkdYafConfig['common']['pid_file'];
        $pids = is_file($pidFile) ? file_get_contents($pidFile) : '';
        if ($pids) {
            $pids = explode('|', $pids);
            foreach ($pids as $pid) {
                Process::kill($pid, SIGKILL);
            }
            file_put_contents($this->jkdYafConfig['common']['pid_file'], '');
            return \Jkd::echoStr('JkdYaf is stopped', 3);
        }

        return \Jkd::echoStr('JkdYaf can not stop', 3);
    }


    /**
     * 重启
     */
    public function restart()
    {
        $this->stop();
        self::$daemonize = true;
        $this->start();
    }


    public function status()
    {
        $pidFile = $this->jkdYafConfig['common']['pid_file'];
        $pids = is_file($pidFile) ? file_get_contents($pidFile) : '';
        $pid = $pids ? (explode('|', $pids)[0] ?? 0) : 0;
        if ($pid && Process::kill($pid, PRIO_PROCESS)) {
            self::$daemonize = true;
            \Jkd::start($this->ip, $this->port, self::$daemonize);
            return true;
        }

        return \Jkd::echoStr('JkdYaf is not running', 2);
    }


    public function onManagerStart()
    {
        // 清空初始化文件
        file_put_contents($this->jkdYafConfig['common']['timer_pid_file'], '');
        file_put_contents($this->jkdYafConfig['common']['pid_file'], '');

        if (self::$daemonize) {
            Process::daemon();
        }
        $pool = new Process\Pool($this->jkdYafConfig['common']['worker_num'], SWOOLE_IPC_NONE, 0, true);
        swoole_set_process_name($this->jkdYafConfig['common']['manager_process_name']);
        if (self::$daemonize) {
            file_put_contents($this->jkdYafConfig['common']['pid_file'], posix_getpid());
        }
        $pool->on('WorkerStart', [$this, 'onWorkerStart']);
        $pool->on('WorkerStop', [$this, 'onWorkerStop']);
        $pool->start();

        return true;
    }


    public function onWorkerStart(Process\Pool $pool, $workerId)
    {
        // 清空初始化文件
        file_put_contents($this->jkdYafConfig['common']['worker_pid_file'], '');
        file_put_contents($this->jkdYafConfig['common']['tasker_pid_file'], '');
        // 初始化连接池日志文件
        $this->createPoolLog();

        $processId = $pool->master_pid . ':' . $workerId;
        $processName = sprintf($this->jkdYafConfig['common']['event_worker_process_name'], $processId);
        swoole_set_process_name($processName);
        if (is_file($this->jkdYafConfig['common']['pid_file']) && self::$daemonize) {
            file_put_contents($this->jkdYafConfig['common']['pid_file'], '|' . posix_getpid(), FILE_APPEND);
        }

        $server = new Server($this->ip, $this->port, false, true);
        //实例化yaf
        $this->app = new Yaf\Application(APP_PATH . "/conf/app.ini");
        $this->app->bootstrap();

        //设置时区
        date_default_timezone_set('PRC');

        //进程级 PHP 设置（常驻内存下仅需在 Worker 启动时设置一次）
        ini_set('memory_limit', '-1');
        ini_set('display_errors', 'On');
        ini_set('error_reporting', E_ALL);

        // 启动Redis连接池
        $this->startRedis($workerId);
        // 启动Mysql连接池
        $this->startMysql($workerId);

        //开启定时器
        if ($workerId == 0) {
            $this->startCron($pool->master_pid);
        }
//        捕获异常
//        set_exception_handler(function (Exception $e) {
//            \JkdLog::error($e);
//        });

//        捕获错误
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            \Log\JkdLog::error([
                'Code:' => $errno,
                'Msg:' => $errstr,
                'File:' => $errfile,
                'Line:' => $errline
            ]);
        }, E_ALL);

        $jkdRoutePrefixList = \Route\JkdRoute::get()->getRoutePrefixs();
        foreach ($jkdRoutePrefixList as $jkdRoutePrefix) {
            $server->handle('/' . $jkdRoutePrefix . '/', [$this, 'onRequest']);
        }
        $server->start();
    }


    public function onWorkerStop(Process\Pool $pool, $workerId)
    {
        echo("[Worker #{$workerId}] WorkerStop\n");
    }


    public function onRequest(Swoole\Http\Request $request, Swoole\Http\Response $response)
    {
//        echo '运行前内存：' . round(memory_get_usage() / 1024 / 1024, 2) . 'MB', PHP_EOL;
        $_startTime = microtime(true);

        $response->header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT');
        $response->header('Access-Control-Allow-Origin', '*');  //解决跨域
        $response->header('Content-Type', 'application/json');

        $requestMethod = $request->server['request_method'] ?? '';
        $requestRouteData = \Route\JkdRoute::get()->getRoute($request->server['request_uri'] ?? '');
        $requestRoute = $requestRouteData['action'] ?? '';
        $needMethod = $requestRouteData['method'] ?? '';
        $needMethod = strtoupper($needMethod);
        $requestParams = [];
        if ($requestRoute && $needMethod == $requestMethod) {
            //注册全局信息
            $requestParams = $this->initRequestParam($request, $needMethod);

            $yafRequest = new Yaf\Request\Http($requestRoute);
            jkdContext()['YAF_HTTP_REQUEST'] = $yafRequest;
            //关闭视图
            Yaf\Dispatcher::getInstance()->autoRender(FALSE);

            $this->app->getDispatcher()->dispatch($yafRequest);

            //从协程上下文读取响应（避免 echo/ob 捕获与二次 JSON 编解码）
            $result = jkdContext()['jkdResponse'] ?? [];
        } else {
            $result = ['code' => 0, 'message' => '404 not found', 'data' => [], 'status' => 404];
        }

        $status = 200;
        if (isset($result['status']) && $result['status']) {
            $status = $result['status'];
            unset($result['status']);
        }

        $_endTime = microtime(true);
        if (self::$daemonize != true) {
            \Jkd::reqMsg($_startTime, $_endTime, $status);
        }

        // 储存请求日志
        if (checkIoStatus('reqLogStatus')) {
            \Task\JkdTask::dispatch(\Job\JkdSysLog::class, [
                'runtime' => $_endTime - $_startTime,
                'route' => $request->server['request_uri'] ?? $requestRoute,
                'params' => $requestParams,
                'result' => $result,
            ]);
        }

        $this->unsetGlobals();
        $response->status($status);
        $response->end(json_encode($result, JSON_UNESCAPED_UNICODE));
//        echo '运行后内存：' . round(memory_get_usage() / 1024 / 1024, 2) . 'MB', PHP_EOL;
    }


    /**
     * 将请求信息放入全局注册器中
     *
     * @param \Swoole\Http\Request $request
     * @return array
     */
    private function initRequestParam(Swoole\Http\Request $request, $needMethod)
    {
        //将请求的一些环境参数放入全局变量桶中
        $server = $request->server ?? [];
        $header = $request->header ?? [];
        $get = $request->get ?? [];
        $post = $request->post ?? [];
        $cookie = $request->cookie ?? [];
        $files = $request->files ?? [];
        $rawContent = $request->rawContent() ?? '';

        // 兼容 JSON 请求体：非表单提交时尝试解析原始内容
        if (empty($post) && $rawContent !== '') {
            $jsonParams = json_decode($rawContent, true);
            if (is_array($jsonParams)) {
                $post = $jsonParams;
            }
        }

        $context = jkdContext();
        $context['REQUEST_SERVER'] = $server;
        $context['REQUEST_HEADER'] = $header;
        $context['REQUEST_GET'] = $get;
        $context['REQUEST_POST'] = $post;
        $context['REQUEST_COOKIE'] = $cookie;
        $context['REQUEST_FILES'] = $files;
        $context['REQUEST_RAW_CONTENT'] = $rawContent;

        $params = $needMethod == 'GET' ? $get : $post;
        if ($params && is_array($params)) {
            foreach ($params as $i => $requestParam) {
                $requestParam = remove_xss($requestParam);
                $params[$i] = safe_replace($requestParam);
            }
        }
        $context['REQUEST_PARAMS'] = $params;

        return $params;
    }


    /**
     * 释放全局变量
     */
    private function unsetGlobals()
    {
        $context = jkdContext();
        unset($context['REQUEST_SERVER']);
        unset($context['REQUEST_HEADER']);
        unset($context['REQUEST_GET']);
        unset($context['REQUEST_POST']);
        unset($context['REQUEST_COOKIE']);
        unset($context['REQUEST_FILES']);
        unset($context['REQUEST_RAW_CONTENT']);
        unset($context['REQUEST_PARAMS']);
        unset($context['YAF_HTTP_REQUEST']);
        unset($context['jkdResponse']);
    }


    /**
     * 初始化连接数日志
     */
    private function createPoolLog()
    {
        //Redis连接数
        $path = APP_PATH . '/runtime/pool/redis_pool_num.count';
        file_put_contents($path, '{}');

        //Mysql连接数
        $path = APP_PATH . '/runtime/pool/mysql_pool_num.count';
        file_put_contents($path, '{}');
    }


    /**
     * 打开Redis连接池
     *
     * @param $workerId
     */
    private function startRedis($workerId)
    {
        // 启动数据库连接池
        \Pool\JkdRedisPool::run()->init();
        // 启动连接池检测定时器
        \Pool\JkdRedisPool::run()->timingRecovery($workerId);
    }


    /**
     * 打开MYsql连接池
     *
     * @param $workerId
     */
    private function startMysql($workerId)
    {
        // 启动数据库连接池
        \Pool\JkdMysqlPool::run()->init();
        // 启动连接池检测定时器
        \Pool\JkdMysqlPool::run()->timingRecovery($workerId);
    }


    /**
     * 开启定时任务
     */
    private function startCron($masterPid)
    {
        \Cron\JkdCron::start($masterPid, $this->jkdYafConfig['common']['timer_pid_file']);
    }

}
