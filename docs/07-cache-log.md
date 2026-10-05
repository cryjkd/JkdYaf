# 07 · 缓存与日志

## 1. Redis 缓存

### 1.1 获取 Redis 连接

`Cache\Redis` 封装了 Redis 连接池的「取 / 还」：

```php
use Cache\Redis;

$redisPool = new Redis();
$redis = $redisPool->get();   // 从连接池取一个连接

// ... 使用 $redis 执行原生 Swoole Redis 命令
$redis->set('key', 'value');
$value = $redis->get('key');

$redisPool->put();            // 归还连接
```

> `Cache\Redis` 的析构函数会自动归还未手动归还的连接，避免连接泄漏。

### 1.2 模型缓存

模型层内置 Redis 缓存，通过 `$isCache` 开关控制，详见 [04-mvc.md](04-mvc.md) 第 4 节。

### 1.3 防止重复请求

```php
use Jkd\JkdPreventDuplication;

// 返回 true 表示首次执行（key 不存在）；返回 false 表示在 $ttl 秒内重复
$first = JkdPreventDuplication::check('ORDER_PAY', $ttl = 3);
```

---

## 2. Yac 无锁共享内存缓存

`Yac\YacCache` 封装 PHP Yac 扩展，用于进程间共享的无锁缓存（适合跨 worker 共享的小数据）。

```php
use Yac\YacCache;

$yac = new YacCache('prefix');   // prefix 为可选命名空间前缀

$yac->set('key', 'value', $ttl = 3600);   // 设置（$ttl 秒，-1 表示最长）
$yac->add('key', 'value');                 // 仅当 key 不存在时添加
$yac->adds(['k1' => 'v1', 'k2' => 'v2']);  // 批量添加
$value = $yac->get('key');                 // 获取
$yac->delete('key');                       // 删除（可传数组批量删除）
$yac->flush();                             // 使所有缓存失效
$yac->info();                              // 查看缓存统计信息
$keys = $yac->getAllKeys();                // 获取所有 key
```

> 最大 TTL 被限制为 `864000` 秒（10 天），避免内存无限膨胀。

---

## 3. 日志

### 3.1 日志方法

```php
use Log\JkdLog;

JkdLog::info($message, $content = []);        // 一般信息
JkdLog::error($content);                      // 运行时错误
JkdLog::debug($message, $content = []);       // 调试信息
JkdLog::channel($channel, $message, $content = []); // 自定义通道
```

- `info` 通道名 `info`
- `error` 通道名 `error`
- `debug` 通道名 `debug`
- `channel` 通道名自定义

### 3.2 日志配置

```ini
[log]
log.day = 7                                  ; 保留天数
log.path = APP_PATH "/runtime/log/"          ; 日志目录
```

日志按「通道 + 日期」分文件存储：

```
runtime/log/
├── info/info-2021-08-03.log
├── error/error-2021-08-03.log
├── sqlLog/sqlLog-2021-08-03.log
├── sysReq/sysReq-2021-08-03.log
├── middleware/middleware-2021-08-03.log
└── aop/aop-2021-08-03.log
```

### 3.3 自动清理

框架内置 `Log\JkdDeleteFile` 定时任务，每天 0 点清理超过保留天数（`log.day`）的日志文件。

### 3.4 请求日志与 SQL 日志

在 `app.ini` 中控制：

```ini
[io]
io.reqLogStatus = TRUE   ; 记录每个请求的耗时/参数/结果（通道 sysReq）
io.sqlLogStatus = TRUE   ; 记录每条 SQL（通道 sqlLog）
```

两者均通过异步任务异步写入，不阻塞请求。

---

相关文档：[04-mvc.md](04-mvc.md) · [06-task-crontab.md](06-task-crontab.md)
