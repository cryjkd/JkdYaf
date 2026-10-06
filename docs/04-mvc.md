# 04 · 控制器 / 服务 / 模型 / 数据库

JkdYaf 采用「控制器 → 服务 → 模型」分层。控制器负责接收与返回，服务负责业务逻辑，模型负责数据访问与缓存。

## 1. 控制器

所有控制器继承 `Jkd\JkdBaseController`：

```php
<?php
class IndexController extends \Jkd\JkdBaseController
{
    public function indexAction()
    {
        return $this->JkdService->index();
    }
}
```

`JkdBaseController` 在 `init()` 中会依据请求自动实例化同名服务，注入到 `$this->JkdService`：

```php
protected $JkdService;

public function init($isAuto = true)
{
    if ($isAuto) {
        $thisService = getService();
        $this->JkdService = new $thisService;
    }
}
```

`getService()` 返回 `app\services\{模块}\{控制器}`，例如 Api 模块 Index 控制器对应 `app\services\Api\Index`。

---

## 2. 服务层

服务继承 `Jkd\JkdBaseService`，通过 `JkdRequest` trait 获得请求参数：

```php
<?php
namespace app\services\Api;

use Jkd\JkdBaseService;
use Jkd\JkdResponse;

class Index extends JkdBaseService
{
    public function index()
    {
        // $this->JkdRequest 即请求参数（GET 的 query 或 POST 的 body）
        return JkdResponse::Success($this->JkdRequest ?: 'Hello JkdYaf !');
    }
}
```

### 请求参数

- `$this->JkdRequest`：请求参数数组（GET 的 query 或 POST 的 body）
- `setRequest($data)`：整体替换请求参数
- `appendRequest($key, $value)`：追加单个参数

> 请求参数在 `HttpServer::initRequestParam()` 中做了 XSS / 安全过滤后，存入**协程上下文**（`jkdContext()['REQUEST_PARAMS']`，协程隔离）。相比 `$GLOBALS`（进程级、跨协程共享），协程上下文可避免并发请求之间串数据；业务代码请统一通过 `$this->JkdRequest` 获取参数。

---

## 3. 响应

使用 `Jkd\JkdResponse` 输出统一 JSON：

```php
use Jkd\JkdResponse;

// 成功：{"code":1,"message":"success","data":...,"status":200}
JkdResponse::Success($data = "", $message = "success", $status = 200, $code = 1);

// 失败（会中断后续执行）：{"code":2,"message":"fail","status":200}
JkdResponse::Fail($message = "fail", $status = 200, $code = 2);

// 错误（500）：{"code":2,"message":"...","status":500}
JkdResponse::Error($message = "500 System error！", $status = 500, $code = 2);

// 调试（400，会中断后续执行）
JkdResponse::Debug($data = "", $message = "debug", $status = 400, $code = 3);
```

> `Fail` / `Error` / `Debug` 通过抛出 `JkdReturn`（code=676）异常来中断流程，该异常会被 `JkdBaseError` 捕获且不记录日志。

响应不再通过 `echo` + 输出缓冲（`ob_*`）捕获，而是由 `JkdResponse::output()` 把数组写入协程上下文（`jkdContext()['jkdResponse']`），`HttpServer` 统一读取后 `json_encode` 返回，避免了二次 JSON 编解码。未捕获异常（非 676）会由 `JkdBaseError` 统一返回 `500 System error!`。

---

## 4. 模型

模型继承 `Jkd\JkdBaseModel`（其又继承 `Db\BaseModel`）：

```php
<?php
class ArticleModel extends \Jkd\JkdBaseModel
{
    protected $table = 'article';        // 表名
    protected $fillAble = [];            // 可填充字段（insert/update 过滤用）
    protected $selectAble = [];          // 查询字段（默认 *）
    protected $isCache = 1;              // 是否开启缓存 0/1
    protected $cacheKey = 'Article';     // 缓存 key
    protected $primaryKey = 'id';        // 主键
    protected $foreignKey = 'category_id'; // 外键（分片缓存 key）
    protected $isList = 1;               // 是否列表数据
    protected $hasListKey = 1;           // 列表是否保留主键 key
}
```

### 属性说明

| 属性          | 说明                                                                 |
| ------------- | -------------------------------------------------------------------- |
| `$table`      | 数据表名                                                            |
| `$fillAble`   | 白名单字段，`insert/update` 传入 `isFillFilter = true` 时只保留这些字段 |
| `$selectAble` | 查询字段列表，为空表示 `*`                                           |
| `$isCache`    | 是否启用 Redis 缓存（0 关闭 / 1 开启）                               |
| `$cacheKey`   | 缓存 key 前缀，默认取表名                                            |
| `$foreignKey` | 外键字段，用作分片缓存 key（如按分类缓存文章列表）                    |
| `$isList`     | 查询结果是否为列表（列表用 Hash，单条用 String）                      |
| `$hasListKey` | 列表结果是否保留主键索引（1 保留 / 0 用 sort 重建索引）               |

### 缓存查询 getCache

```php
$articleModel = new \ArticleModel();

// 按外键查询（缓存 key 为 Article:{categoryId}）
$list = $articleModel->getCache($categoryId);

// 查询全部（缓存 key 为 Article:ALL）
$list = $articleModel->getCache();

// 按外键 + 条件查询
$list = $articleModel->getCache($categoryId, 'id', $articleId);
```

- `isList = 1`：走 `getVoList`，使用 Redis Hash（`hGetAll`/`hmset`）存储。
- `isList = 0`：走 `getVo`，使用 Redis String（`get`/`set`）存储。
- 缓存未命中时回源数据库并写入缓存，默认过期 `$cacheExpire`（86400 秒）。

### 增删改

```php
// 新增（返回自增 id）
$id = $model->insert(['name' => 'x'], $isFillFilter = false);

// 更新（$foreignValue 用于命中后清除对应分片缓存）
$model->update(['title' => 't'], ['id' => 1], false, $foreignValue = '');

// 删除
$model->delete(['id' => 1], $foreignValue = '');

// 手动清除缓存
$model->delCache($foreignValue = '');
```

---

## 5. 数据库访问

### BaseModel 基础查询

`Db\BaseModel` 提供：

```php
$model->get($where = [], $select = [], $order = '', $sort = '');    // 单条
$model->all($where = [], $select = [], $order = '', $sort = '');    // 多条
$model->insertSql($data, $isFillFilter = false);                    // 新增
$model->updateSql($data, $where = [], $isFillFilter = false);       // 更新
$model->deleteSql($where = []);                                     // 删除
```

### 连接来源

- 默认走 **连接池**：`Factory::getPool()` → `MysqlPool`。
- 传入 `$dbName` 走独立连接：`new ArticleModel('db')` → `Factory::create('db')`。

### 事务

```php
$db->transaction($sql);
```

底层 `MysqlHandle` 实现 `DbInterface`，封装 `execute/getOne/getRow/getCol/getAll/insert/update/delete/transaction/close`。

---

相关文档：[03-routing.md](03-routing.md) · [07-cache-log.md](07-cache-log.md)
