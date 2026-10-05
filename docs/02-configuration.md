# 02 · 配置说明

JkdYaf 使用 ini 文件管理配置，所有业务配置位于 `yaf/conf/` 目录。配置通过 `Conf\JkdConf::get()` 读取。

## 1. 配置读取方式

```php
use Conf\JkdConf;

// 读取环境段配置（默认 isEnv = true，返回 product/common 段）
$db = JkdConf::get('db');

// 读取原始配置（isEnv = false，返回整个 ini 对象）
$route = JkdConf::get('route', false);
```

- `isEnv = true`（默认）：返回 `[当前环境]` 段，即 `environ()` 对应的段（如 `product`）。
- `isEnv = false`：返回整个配置文件对象。

`JkdConf::get()` 支持点号取子段，如 `JkdConf::get('db.pool_max')` 返回 `db.ini` 中 `pool_max` 的值。

---

## 2. app.ini（应用配置）

```ini
[common]
application.directory = APP_PATH "/app"          ; 应用目录
application.dispatcher.catchException = TRUE      ; 捕获异常
application.dispatcher.throwException = TRUE      ; 抛出异常
application.library = APP_PATH "/library"         ; 本地类库
application.library.namespace = "Com"             ; 本地类库命名空间
application.modules = "Api"                       ; 模块列表
comLibsPath = "/path/JkdYaf/yafLibrary/"          ; 框架公共类库路径
apiTs = 60                                        ; 接口签名有效时间（秒）

[io]
io.aopStatus = TRUE           ; AOP 注解开关
io.middlewareStatus = TRUE    ; 中间件开关
io.reqLogStatus = TRUE        ; 请求日志开关
io.sqlLogStatus = TRUE        ; SQL 日志开关

[log]
log.day = 7                   ; 日志保留天数
log.path = APP_PATH "/runtime/log/"   ; 日志目录

[product : common : io : log] ; 生产环境继承 common/io/log 段
```

> `APP_PATH` 在启动文件 `yaf/bin/JkdYaf.php` 中定义为 `yaf/` 目录。

---

## 3. jkdYaf.ini（服务配置）

```ini
[common]
ip = "0.0.0.0"                      ; 监听地址
port = 12222                        ; 监听端口
app_name = JkdYaf                   ; 项目名（英文，多项目区分）
worker_num = 32                     ; Worker 进程数
master_process_name = JkdYaf-Master
manager_process_name = JkdYaf-Manager
event_worker_process_name = JkdYaf-Worker-%s
event_tasker_process_name = JkdYaf-Tasker-%s

pid_file = APP_PATH "/runtime/master.pid"      ; master/manager pid
worker_pid_file = APP_PATH "/runtime/worker.pid"   ; worker pid
tasker_pid_file = APP_PATH "/runtime/tasker.pid"   ; tasker pid
timer_pid_file = APP_PATH "/runtime/timer.pid"     ; timer pid
log_file = APP_PATH "/runtime/swoole.log"          ; swoole 日志

[product : common]
```

---

## 4. db.ini（数据库 / Mysql 连接池）

```ini
[common]
is_monitor = true      ; 是否开启连接数监控
pool_max = 10000       ; 连接池最大连接数

[db]
db.host = "localhost"
db.port = 3306
db.dbname = "dbname"
db.username = "username"
db.password = "password"
db.charset = "utf8mb4"

[product : common : db]
```

---

## 5. redis.ini（Redis / 连接池）

```ini
[common]
is_monitor = true      ; 是否开启连接数监控

host = "127.0.0.1"
port = 6379
pwd =                  ; 密码，无则留空
timeout = 1            ; 连接超时（秒）
dbindex = 0            ; DB 索引

pool_max = 10000       ; 连接池最大连接数

[product : common]
```

---

## 6. redisKey.ini（Redis key 集中管理）

```ini
[common]
test = "test"

[product : common]
```

读取：`\Yaf\Registry::get('redisKeyConf')->test`。集中定义 key 便于统一管理与前缀约定。

---

## 7. channel.ini（站点配置）

```ini
[common]
siteUrl = "http://localhost:12222"   ; 站点域名，用于 JWT 签发人/受众

[product : common]
```

读取：`\Yaf\Registry::get('channelConfig')->siteUrl`。部署时改为实际访问域名。

---

## 8. route.ini / routes/*.ini（路由）

详见 [03-routing.md](03-routing.md)。

---

## 9. crontab.ini（定时任务）

详见 [06-task-crontab.md](06-task-crontab.md)。

---

相关文档：[01-getting-started.md](01-getting-started.md) · [03-routing.md](03-routing.md)
