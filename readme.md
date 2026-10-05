# JkdYaf - V2.2.3

> 基于 **YAF + SWOOLE** 的 PHP API 框架

```
       ____ __ ______  _____    ______
      / / //_// __ \ \/ /   |  / ____/
 __  / / ,<  / / / /\  / /| | / /_
/ /_/ / /| |/ /_/ / / / ___ |/ __/
\____/_/ |_/_____/ /_/_/  |_/_/
```

简单、直接、非传统。

JkdYaf 是一个简单、高性能的常驻内存 PHP 框架，基于 [Yaf](https://www.php.net/manual/zh/book.yaf.php) 与 [Swoole](https://www.swoole.com/) 开发，性能较传统基于 PHP-FPM 的框架有质的提升。它是一款专为 API 开发的轻量级框架，面向中小型企业级项目，具备高可用、低门槛的特点。

---

## 特性

- **HTTP 服务**：基于 Swoole 协程 HTTP 服务器，常驻内存
- **Mysql 连接池**：基于 Swoole `PDOPool`，自动回收、可监控
- **Redis 连接池**：基于 Swoole `RedisPool`，自动回收、可监控
- **JWT 认证**：内置 `firebase/php-jwt` 风格的 JWT 编解码
- **接口签名**：请求时间戳 + 字典序签名校验
- **协程化**：请求处理全程协程
- **定时任务**：秒级 cron 表达式定时任务
- **异步任务**：`dispatch` / `delay` / `tick` 三种异步任务
- **日志管理**：按通道分类、按天分文件、自动清理
- **路由管理**：ini 文件集中管理、支持方法限定
- **Yac 无锁共享内存**：进程间共享缓存
- **注解 AOP**：`@AopBefore` / `@AopAfter` / `@AopAround` 注解
- **中间件**：路由级中间件，支持应用内与框架级两类

## 服务器要求

| 组件   | 版本要求        | 说明                     |
| ------ | --------------- | ------------------------ |
| PHP    | 7.x 或更高      | 建议 7.4+ / 8.0+         |
| Yaf    | 3.3.x 或更高    | 需开启命名空间            |
| Swoole | 4.5.x 或更高    | 需 `swoole.use_shortname` 或使用命名空间类 |
| Mysql  | —               | 数据存储                  |
| Redis  | —               | 缓存 / 连接池 / 防重复     |
| Yac    | 2.3.x 或更高    | 可选，无锁共享内存缓存     |

---

## 安装

```bash
git clone https://github.com/crytjy/JkdYaf.git
```

### php.ini 扩展配置

```ini
extension=yaf.so
[yaf]
yaf.environ=product
yaf.cache_config=1
yaf.use_namespace=1
yaf.library="/path/JkdYaf/yafLibrary/"   ; 全局类库目录（框架核心库）

extension=yac.so
[yac]
; 是否开启 yac，1 表示开启，0 表示关闭
yac.enable=1
; 4M 可得到 32768 个 key，32M 可得到 262144 个 key
yac.keys_memory_size=4M
; 申请的最大 value 内存
yac.values_memory_size=64M
; 是否压缩数据
yac.compress_threshold='-1'
; 关闭在 cli 下使用 yac（常驻进程需开启）
yac.enable_cli=1
```

> 注意：`yaf.library` 必须指向项目内的 `yafLibrary/` 目录，且需与 `app.ini` 中的 `comLibsPath` 保持一致。

---

## 快速开始

### 1. 配置

编辑 `yaf/conf/app.ini`，将 `comLibsPath` 改为你的实际类库路径：

```ini
[common]
comLibsPath = "/path/JkdYaf/yafLibrary/"
```

按需修改 `yaf/conf/db.ini`（数据库）、`yaf/conf/redis.ini`（缓存）、`yaf/conf/jkdYaf.ini`（监听端口与进程数）。

### 2. 启动

```bash
cd yaf
php bin/JkdYaf.php start      # 前台启动
php bin/JkdYaf.php start -d   # 守护进程方式启动
```

其他命令：

```bash
php bin/JkdYaf.php stop       # 停止
php bin/JkdYaf.php restart    # 重启
php bin/JkdYaf.php status     # 查看状态
```

### 3. 验证

浏览器访问：

```
http://localhost:12222/api/index
```

返回：

```json
{"code":1,"message":"success","data":"Hello JkdYaf !"}
```

---

## 目录结构

```
JkdYaf/
├── yaf/                          # 业务应用目录（APP_PATH）
│   ├── bin/JkdYaf.php            # 启动入口（start/stop/restart/status）
│   ├── conf/                     # 配置文件
│   │   ├── app.ini               # Yaf 应用配置 + io 开关 + 日志
│   │   ├── jkdYaf.ini            # 服务监听、进程数、pid 文件
│   │   ├── db.ini                # 数据库 / 连接池
│   │   ├── redis.ini             # Redis / 连接池
│   │   ├── redisKey.ini          # Redis 业务 key 集中管理
│   │   ├── crontab.ini           # 定时任务
│   │   ├── route.ini             # 路由前缀 / 模块 / 中间件
│   │   ├── channel.ini           # 站点配置（JWT 等）
│   │   └── routes/               # 各模块具体路由
│   │       └── api.ini
│   ├── app/
│   │   ├── Bootstrap.php         # 应用引导（继承框架 JkdBootstrap）
│   │   ├── controllers/          # 全局控制器（Error 等）
│   │   ├── modules/              # 多模块
│   │   │   └── Api/controllers/  # Api 模块控制器
│   │   ├── services/             # 服务层（按模块划分）
│   │   ├── models/               # 模型层
│   │   ├── middleware/           # 应用级中间件
│   │   ├── crontab/              # 定时任务类
│   │   ├── task/                 # 异步任务类
│   │   └── library/Com/          # 业务本地类库（Com 命名空间）
│   └── runtime/                  # 运行时（日志 / pid / 连接池监控）
└── yafLibrary/                   # 框架核心库（LIB_PATH）
    ├── HttpServer.php            # Swoole HTTP 服务
    ├── Aop/                      # 注解 AOP
    ├── Auth/                     # JWT / 签名
    ├── Cache/                    # Redis 操作封装
    ├── Conf/                     # 配置读取
    ├── Cron/                     # 定时任务
    ├── Db/                       # 数据库 / 模型基类
    ├── Job/                      # 日志 Job
    ├── Jkd/                      # 控制器/模型/服务/响应基类
    ├── Jwt/                      # JWT 实现
    ├── Log/                      # 日志
    ├── Middleware/               # 框架级中间件
    ├── Plugin/                   # Yaf 插件（中间件/AOP 挂载点）
    ├── Pool/                     # Mysql/Redis 连接池
    ├── Route/                    # 路由
    ├── Task/                     # 异步任务
    ├── UnAutoLoader/             # 引导 / 全局函数
    └── Yac/                      # Yac 缓存封装
```

---

## 配置概览

| 配置文件          | 用途                                       |
| ----------------- | ------------------------------------------ |
| `app.ini`         | 应用目录、模块、类库路径、io 开关、日志     |
| `jkdYaf.ini`      | 监听 IP/端口、进程数、进程名、pid 文件       |
| `db.ini`          | 数据库连接与连接池参数                      |
| `redis.ini`       | Redis 连接与连接池参数                      |
| `redisKey.ini`    | Redis key 集中定义                          |
| `route.ini`       | 路由前缀、模块映射、路由级中间件            |
| `routes/*.ini`    | 具体路由（URI → 方法 → 控制器动作）          |
| `crontab.ini`     | 秒级定时任务                                |
| `channel.ini`     | 站点配置（JWT 签发人/受众等）                |

详细说明见 [docs/02-configuration.md](docs/02-configuration.md)。

---

## 文档

| 文档 | 内容 |
| ---- | ---- |
| [01-getting-started.md](docs/01-getting-started.md) | 安装环境、快速开始、目录结构 |
| [02-configuration.md](docs/02-configuration.md)      | 全部配置项详解 |
| [03-routing.md](docs/03-routing.md)                  | 路由定义与匹配 |
| [04-mvc.md](docs/04-mvc.md)                          | 控制器 / 服务 / 模型 / 数据库 |
| [05-middleware-aop.md](docs/05-middleware-aop.md)    | 中间件与注解 AOP |
| [06-task-crontab.md](docs/06-task-crontab.md)        | 异步任务与定时任务 |
| [07-cache-log.md](docs/07-cache-log.md)              | 缓存（Redis/Yac）与日志 |
| [08-auth.md](docs/08-auth.md)                        | JWT 认证与接口签名 |
| [09-deploy.md](docs/09-deploy.md)                    | 部署与运维 |

---

## 开源协议

本项目基于 [Apache License 2.0](LICENSE) 开源。
