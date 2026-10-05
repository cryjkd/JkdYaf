# 01 · 安装与快速开始

本文介绍 JkdYaf 的运行环境、安装步骤以及如何跑通第一个接口。

## 1. 环境要求

| 组件   | 版本要求        | 说明                       |
| ------ | --------------- | -------------------------- |
| PHP    | 7.x 或更高      | 建议 7.4+ / 8.0+           |
| Yaf    | 3.3.x 或更高    | 必须开启命名空间            |
| Swoole | 4.5.x 或更高    | 常驻内存 + 协程            |
| Mysql  | —               | 数据存储（可选）            |
| Redis  | —               | 缓存 / 连接池 / 防重复（可选） |
| Yac    | 2.3.x 或更高    | 可选，无锁共享内存缓存      |

> Swoole 4.5+ 已默认使用命名空间类（`Swoole\Coroutine\Http\Server` 等），本框架不依赖 `swoole.use_shortname`。

## 2. 获取代码

```bash
git clone https://github.com/crytjy/JkdYaf.git
cd JkdYaf
```

## 3. 配置 PHP 扩展

编辑 `php.ini`，加入：

```ini
extension=yaf.so
[yaf]
yaf.environ=product
yaf.cache_config=1
yaf.use_namespace=1
yaf.library="/path/JkdYaf/yafLibrary/"   ; 全局类库目录（框架核心库）

extension=yac.so
[yac]
yac.enable=1
yac.keys_memory_size=4M
yac.values_memory_size=64M
yac.compress_threshold='-1'
yac.enable_cli=1
```

> **重要**：`yaf.library` 必须指向项目内的 `yafLibrary/` 目录，并且要与 `yaf/conf/app.ini` 中的 `comLibsPath` 完全一致，否则框架类库无法自动加载。

## 4. 修改项目配置

编辑 `yaf/conf/app.ini`，将 `comLibsPath` 改为实际路径：

```ini
[common]
comLibsPath = "/path/JkdYaf/yafLibrary/"
```

按需修改：

- `yaf/conf/jkdYaf.ini`：监听端口（默认 `12222`）、worker 进程数（默认 `32`）
- `yaf/conf/db.ini`：数据库连接信息
- `yaf/conf/redis.ini`：Redis 连接信息

## 5. 启动服务

```bash
cd yaf
php bin/JkdYaf.php start        # 前台启动（Ctrl+C 退出）
php bin/JkdYaf.php start -d     # 守护进程方式启动
```

其它命令：

```bash
php bin/JkdYaf.php stop         # 停止
php bin/JkdYaf.php restart      # 重启
php bin/JkdYaf.php status       # 查看运行状态
```

启动成功后会输出组件版本表与监听信息。

## 6. 验证

浏览器访问：

```
http://localhost:12222/api/index
```

返回：

```json
{"code":1,"message":"success","data":"Hello JkdYaf !"}
```

## 7. 目录结构

```
JkdYaf/
├── yaf/                          # 业务应用目录（APP_PATH）
│   ├── bin/JkdYaf.php            # 启动入口
│   ├── conf/                     # 配置文件
│   │   ├── app.ini               # Yaf 应用 + io 开关 + 日志
│   │   ├── jkdYaf.ini            # 监听 / 进程 / pid 文件
│   │   ├── db.ini                # 数据库 / 连接池
│   │   ├── redis.ini             # Redis / 连接池
│   │   ├── redisKey.ini          # Redis key 集中管理
│   │   ├── crontab.ini           # 定时任务
│   │   ├── route.ini             # 路由前缀 / 模块 / 中间件
│   │   ├── channel.ini           # 站点配置（JWT 等）
│   │   └── routes/api.ini        # Api 模块具体路由
│   ├── app/
│   │   ├── Bootstrap.php         # 应用引导
│   │   ├── controllers/          # 全局控制器（Error 等）
│   │   ├── modules/Api/          # Api 模块控制器
│   │   ├── services/             # 服务层
│   │   ├── models/               # 模型层
│   │   ├── middleware/           # 应用级中间件
│   │   ├── crontab/              # 定时任务类
│   │   ├── task/                 # 异步任务类
│   │   └── library/Com/          # 业务本地类库
│   └── runtime/                  # 运行时（日志 / pid / 监控）
└── yafLibrary/                   # 框架核心库（LIB_PATH）
    ├── HttpServer.php            # Swoole HTTP 服务
    ├── Aop/  Auth/  Cache/  Conf/  Cron/  Db/  Job/
    ├── Jkd/  Jwt/  Log/  Middleware/  Plugin/  Pool/
    ├── Route/  Task/  UnAutoLoader/  Yac/
    └── bin/                      # 控制台输出 / 命令行工具
```

## 8. 常见问题

### 启动提示找不到类库

检查 `app.ini` 的 `comLibsPath` 与 `php.ini` 的 `yaf.library` 是否指向同一目录。

### 端口被占用

修改 `yaf/conf/jkdYaf.ini` 中的 `port`。

### 无法连接 Redis/Mysql

确认 `db.ini` / `redis.ini` 连接信息正确，且对应服务已启动。

---

相关文档：[02-configuration.md](02-configuration.md) · [03-routing.md](03-routing.md)
