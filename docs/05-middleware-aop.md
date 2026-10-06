# 05 · 中间件与注解 AOP

JkdYaf 提供两类切面扩展：**中间件**（请求前置处理）和**注解 AOP**（方法级切面）。

## 1. 中间件

### 1.1 开关

在 `app.ini` 中控制：

```ini
[io]
io.middlewareStatus = TRUE
```

### 1.2 定义中间件

中间件是一个实现了 `handle()` 方法的类，可放在两个位置：

**应用级**（`yaf/app/middleware/`，命名空间 `app\middleware\`）：

```php
<?php
namespace app\middleware;

class Test
{
    public function handle()
    {
        // 处理逻辑
    }
}
```

**框架级**（`yafLibrary/Middleware/`，命名空间 `Middleware\`）：

```php
<?php
namespace Middleware;

class JkdSign
{
    public function handle()
    {
        // 签名校验
    }
}
```

> `JkdRouter` 会根据文件位置自动判断中间件类型（app/com）。

### 1.3 挂载中间件

在 `route.ini` 中为前缀配置公共中间件（逗号分隔、按顺序执行）：

```ini
[route]
api.middleware = "JkdSign,JkdAuth"
```

也可通过中间件组对特定路由挂载，详见 [03-routing.md](03-routing.md) 第 4 节。

### 1.4 执行时机

中间件在 Yaf `routerStartup` 钩子中执行（见 `Plugin\Jkd`），即路由分发前。中间件中可通过协程上下文获取请求参数：`jkdContext()['REQUEST_PARAMS']`。

### 1.5 内置中间件

| 中间件    | 作用                                   |
| --------- | -------------------------------------- |
| `JkdSign` | 校验请求时间戳 `ts` 与签名 `sign`       |
| `JkdAuth` | 校验 `token`（JWT），解析出用户标识     |

> 内置中间件返回失败时调用 `JkdResponse::Fail()` 中断请求。

---

## 2. 注解 AOP

### 2.1 开关

```ini
[io]
io.aopStatus = TRUE
```

### 2.2 定义切面类

切面类放在业务本地类库 `yaf/app/library/Com/`（命名空间 `Com`）：

```php
<?php
namespace Com;

class TestAop
{
    public function test1()
    {
        // 前置
    }

    public function test2()
    {
        // 后置
    }

    public function test3()
    {
        // 环绕
    }
}
```

### 2.3 使用注解

在控制器动作的 DocBlock 中声明：

```php
/**
 * Index
 *
 * @AopBefore(Com\TestAop, test1)
 * @AopAfter(Com\TestAop, test2)
 * @AopAround(Com\TestAop, test3)
 *
 * @return mixed
 */
public function indexAction()
{
    return $this->JkdService->index();
}
```

### 2.4 注解类型与执行时机

| 注解          | 执行时机（Yaf 钩子）                                        |
| ------------- | ---------------------------------------------------------- |
| `@AopBefore`  | `routerShutdown`（分发前）                                  |
| `@AopAfter`   | `postDispatch`（动作执行后）                                |
| `@AopAround`  | `routerShutdown` 与 `postDispatch` 各执行一次（环绕）        |

语法：

```
@AopBefore(类名, 方法名)
```

### 2.5 实现原理

`Aop\JkdAop` 在 `routerShutdown` 时：

1. 通过协程上下文中的 `YAF_HTTP_REQUEST` 拿到模块 / 控制器 / 动作；
2. 用 `ReflectionMethod` 读取动作方法的 DocBlock；
3. `DocParser` 解析出 `AopBefore/AopAfter/AopAround` 列表；
4. 在对应钩子中实例化切面类并调用指定方法。

---

相关文档：[03-routing.md](03-routing.md) · [06-task-crontab.md](06-task-crontab.md)
