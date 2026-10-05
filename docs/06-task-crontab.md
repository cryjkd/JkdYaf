# 06 · 异步任务与定时任务

JkdYaf 提供基于 Swoole `Timer` 的异步任务，以及秒级 cron 定时任务。

## 1. 异步任务

### 1.1 定义任务

任务类实现 `Task\JkdTaskInterface`，位于 `yaf/app/task/`（命名空间 `app\task\`）：

```php
<?php
namespace app\task;

use Task\JkdTaskInterface;

class Test implements JkdTaskInterface
{
    public function handle($params)
    {
        // 处理逻辑，$params 为传入数据
    }
}
```

### 1.2 分发任务

```php
use Task\JkdTask;

// 1 秒后执行一次
JkdTask::dispatch(Test::class, ['name' => '111', 'time' => time()]);

// 延迟指定毫秒后执行一次
JkdTask::delay(Test::class, 1500, ['name' => '222']);

// 每隔指定毫秒重复执行
$timerId = JkdTask::tick(Test::class, 1000, ['name' => '333']);

// 取消定时任务
JkdTask::clear($timerId);
```

| 方法                          | 说明                           |
| ----------------------------- | ------------------------------ |
| `dispatch($class, $data)`     | 1 秒后执行一次                 |
| `delay($class, $ms, $data)`   | 延迟 `$ms` 毫秒执行一次        |
| `tick($class, $ms, $data)`    | 每 `$ms` 毫秒重复执行          |
| `clear($timerId)`             | 取消任务                       |

> 框架内部的 SQL 日志、请求日志也通过 `JkdTask::dispatch()` 异步写入。

---

## 2. 定时任务（crontab）

### 2.1 配置

在 `yaf/conf/crontab.ini` 中定义：

```ini
; 是否开启定时器
is_start = true

[test]
class = "\app\crontab\Test"
func = "test"
cronTime = "* * * * * *"
```

每个任务段包含三项：

| 配置      | 说明                              |
| --------- | --------------------------------- |
| `class`   | 任务类（位于 `yaf/app/crontab/`） |
| `func`    | 要执行的方法名                     |
| `cronTime`| cron 表达式                        |

### 2.2 cron 表达式

支持 **6 段**（秒级）或 **5 段**（分钟级）：

```
秒 分 时 日 月 周
*  *  *  *  *  *
|  |  |  |  |  +-- 星期（0-6，0=周日）
|  |  |  |  +----- 月（1-12）
|  |  |  +-------- 日（1-31）
|  |  +----------- 时（0-23）
|  +-------------- 分（0-59）
+----------------- 秒（0-59）
```

示例：

| 表达式              | 含义                       |
| ------------------- | -------------------------- |
| `* * * * * *`       | 每秒执行                   |
| `*/5 * * * * *`     | 每 5 秒执行                |
| `0 * * * * *`       | 每分钟第 0 秒执行          |
| `0 0 * * *`         | 每天 0 点执行（5 段）      |
| `0 0 2 * * *`       | 每天 2 点执行              |
| `0 30 9 * * 1-5`    | 工作日 9:30 执行           |

### 2.3 定义任务类

```php
<?php
namespace app\crontab;

class Test
{
    public function test()
    {
        // 定时执行逻辑
    }
}
```

### 2.4 实现说明

- 定时任务仅在 `workerId == 0` 的进程中启动（`HttpServer::startCron()`）。
- `JkdCron` 通过 `JkdPreventDuplication::check('CRON')` 防止重复启动。
- 框架会自动追加一个每天 `0 0 * * *` 的日志清理任务（`Log\JkdDeleteFile`）。
- 每小时执行一次内存碎片回收 `gc_mem_caches()`。

---

相关文档：[05-middleware-aop.md](05-middleware-aop.md) · [07-cache-log.md](07-cache-log.md)
