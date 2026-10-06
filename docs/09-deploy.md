# 09 · 部署与运维

## 1. 启动 / 停止 / 重启

```bash
cd yaf

php bin/JkdYaf.php start        # 前台启动
php bin/JkdYaf.php start -d     # 守护进程启动
php bin/JkdYaf.php stop         # 停止
php bin/JkdYaf.php restart      # 重启
php bin/JkdYaf.php status       # 查看状态
```

## 2. 进程模型

JkdYaf 使用 Swoole `Process\Pool` 管理多进程：

| 进程   | 说明                                   |
| ------ | -------------------------------------- |
| Master | 主进程（守护模式下记录 pid）            |
| Manager| 进程管理器                              |
| Worker | 处理 HTTP 请求的协程服务器（默认 32 个） |
| Timer  | 定时任务（仅 worker 0 启动）            |

进程名可通过 `jkdYaf.ini` 自定义。

## 3. PID 文件

| 文件                | 记录内容         |
| ------------------- | ---------------- |
| `runtime/master.pid` | master/manager pid |
| `runtime/worker.pid` | worker pid       |
| `runtime/tasker.pid` | tasker pid       |
| `runtime/timer.pid`  | timer pid        |

## 4. 连接池监控

当 `db.ini` / `redis.ini` 中 `is_monitor = true` 时，每 5 秒将各 worker 的当前连接数写入：

```
runtime/pool/mysql_pool_num.count
runtime/pool/redis_pool_num.count
```

内容为 `{workerId: 剩余连接数}` 的 JSON，可用于监控连接池水位。

## 5. 日志管理

- 业务日志：`runtime/log/{通道}/{通道}-{日期}.log`
- Swoole 日志：`jkdYaf.ini` 中 `log_file` 配置
- 日志自动清理：每天 0 点清理超过 `log.day` 天的日志

## 6. 生产环境建议

1. **调整 worker 数**：按 CPU 核数设置 `jkdYaf.ini` 的 `worker_num`（如 2×核数）。
2. **使用守护进程**：`php bin/JkdYaf.php start -d`，配合进程守护工具（systemd / supervisor）。
3. **更换密钥**：务必修改 `JwtAuth::Key` 与 `ApiAuth::KEY`。
4. **配置域名**：`channel.ini` 的 `siteUrl` 改为实际访问域名。
5. **关闭调试**：确认 `app.ini` 中 `application.dispatcher.throwException` 与 `catchException` 配置合理。
6. **连接池上限**：根据实际并发调整 `db.ini` / `redis.ini` 的 `pool_max`。
7. **环境区分**：通过 `php.ini` 的 `yaf.environ` 区分 `product` / `develop`，配置中使用 `[product : common]` 继承。
8. **协程安全**：框架的请求级数据（参数、响应、AOP 列表等）存放在协程上下文（`jkdContext()`）中，协程隔离、随请求结束自动释放。业务代码中请勿用 `$GLOBALS` 或静态属性保存单次请求的数据，以免并发串数据。

## 7. 常见问题

### 重启后端口未释放

确认 `stop` 命令执行成功，或使用 `status` 查看是否有残留进程；必要时 `ps -ef | grep JkdYaf` 手动清理。

### 内存占用持续增长

请求级数据已改用协程上下文（`jkdContext()`）存储，随协程结束自动释放；业务代码仍应避免用全局/静态变量持有大对象或循环引用。框架已内置每小时 `gc_mem_caches()`。

### 修改代码不生效

常驻内存模式下修改 PHP 代码后需重启服务：`php bin/JkdYaf.php restart`。

---

相关文档：[01-getting-started.md](01-getting-started.md) · [02-configuration.md](02-configuration.md)
