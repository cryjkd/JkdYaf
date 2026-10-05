# 03 · 路由

JkdYaf 的路由由两个配置文件共同决定：`route.ini`（前缀与模块映射）和 `routes/*.ini`（具体路由）。

## 1. route.ini（前缀与模块）

```ini
[route]
api.prefix = "api"          ; URL 前缀
api.modules = "Api"         ; 对应 Yaf 模块
api.middleware = ""         ; 该前缀下所有路由的公共中间件（逗号分隔）

; web.prefix = "web"
; web.modules = "Web"
; web.middleware = "Test"

[middlewareGroup]
middlewareGroup = "Test1,Test2"   ; 中间件组（供 routes 中的 [middlewareGroup] 段引用）
```

> Yaf 的 ini 解析器会把点号 `api.prefix` 解析为嵌套结构 `route.api.prefix`，因此 `[route]` 段中每个 `xxx.prefix` 代表一个独立路由前缀。

### 新增一个前缀（模块）

```ini
[route]
api.prefix = "api"
api.modules = "Api"
api.middleware = ""

web.prefix = "web"
web.modules = "Web"
web.middleware = "Test"
```

同时在 `yaf/conf/routes/` 下创建同名文件 `web.ini`。

---

## 2. routes/*.ini（具体路由）

以 `routes/api.ini` 为例：

```ini
test.post = "/Index/index"
index.get = "/Index/index"
task.get = "/Index/task"
article.get = "/Index/article"

[middlewareGroup]
index.post = "/Index/index"
```

### 语法

```
uri.http方法 = "/控制器/动作"
```

- `uri`：访问路径（不含前缀）
- `http方法`：`get` / `post` / `put`
- `"/控制器/动作"`：Yaf 控制器与动作，前面会自动拼接模块名

例如 `test.post = "/Index/index"` 表示：

```
POST /api/test  →  Api 模块 / Index 控制器 / index 动作
```

### 请求方法约束

路由定义中限定了 HTTP 方法，只有方法匹配时才命中，否则返回 404：

```json
{"code":0,"message":"404 not found","data":[],"status":404}
```

---

## 3. 路由匹配流程

1. 启动时 `Route\JkdRouter` 读取 `route.ini` 与 `routes/*.ini`，构建路由表 `routeList`，并写入 `Yaf\Registry`。
2. `HttpServer` 为每个前缀注册 Swoole 路由：`$server->handle('/api/', [$this, 'onRequest'])`。
3. 请求到来时，`JkdRoute::getRoute($uri)` 按完整 URI（如 `/api/index`）精确匹配路由表。
4. 命中后实例化 `Yaf\Request\Http`，交由 Yaf 分发到对应控制器动作。

---

## 4. 路由级中间件

路由中间件有两种配置方式：

### 4.1 前缀级公共中间件

在 `route.ini` 的 `api.middleware` 中配置，对该前缀下所有路由生效：

```ini
[route]
api.middleware = "JkdSign,JkdAuth"
```

### 4.2 中间件组（针对特定路由）

使用 `[middlewareGroup]` 段：

1. 在 `route.ini` 的 `[middlewareGroup]` 段定义中间件列表：

```ini
[middlewareGroup]
middlewareGroup = "Test1,Test2"
```

2. 在 `routes/api.ini` 的 `[middlewareGroup]` 段重新声明需要该组中间件的路由：

```ini
[middlewareGroup]
index.post = "/Index/index"
```

上面的配置表示：`POST /api/index` 额外应用 `Test1`、`Test2` 中间件。

> 中间件按「前缀级 + 组」顺序依次执行。中间件文件可在应用目录 `yaf/app/middleware/`（命名空间 `app\middleware\`）或框架目录 `yafLibrary/Middleware/`（命名空间 `Middleware\`）中定义。

---

## 5. 完整示例

需求：新增 `POST /api/order/create`，并应用签名中间件 `JkdSign`。

1. `routes/api.ini`：

```ini
order.create.post = "/Order/create"
```

2. 在 `yaf/app/modules/Api/controllers/` 下创建 `Order.php`：

```php
<?php
class OrderController extends \Jkd\JkdBaseController
{
    public function createAction()
    {
        return $this->JkdService->create();
    }
}
```

3. 在 `yaf/app/services/Api/` 下创建 `Order.php`：

```php
<?php
namespace app\services\Api;

use Jkd\JkdBaseService;
use Jkd\JkdResponse;

class Order extends JkdBaseService
{
    public function create()
    {
        return JkdResponse::Success($this->JkdRequest);
    }
}
```

4. 若需签名校验，在 `route.ini` 设置：

```ini
[route]
api.middleware = "JkdSign"
```

---

相关文档：[02-configuration.md](02-configuration.md) · [04-mvc.md](04-mvc.md) · [05-middleware-aop.md](05-middleware-aop.md)
