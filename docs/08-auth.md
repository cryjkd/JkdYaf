# 08 · JWT 认证与接口签名

JkdYaf 内置 JWT 认证与请求签名能力，通过中间件接入。

## 1. JWT 认证

### 1.1 生成 Token

```php
use Auth\JwtAuth;

$token = JwtAuth::getToken($guid);   // $guid 为用户唯一标识
```

生成的 Token 载荷包含：

| 字段   | 说明                          |
| ------ | ----------------------------- |
| `iss`  | 签发人（`channel.ini` 的 siteUrl） |
| `aud`  | 受众（同上）                  |
| `iat`  | 签发时间                      |
| `nbf`  | 生效时间                      |
| `exp`  | 过期时间（签发后 600 秒）     |
| `guid` | 自定义参数（用户标识）        |

### 1.2 校验 Token

```php
use Auth\JwtAuth;

$guid = JwtAuth::checkToken($token);   // 解析成功返回 guid，失败返回 0
```

`JwtAuth::Key` 为签名密钥，生产环境请务必更换。

### 1.3 通过中间件校验

在 `route.ini` 挂载 `JkdAuth` 中间件：

```ini
[route]
api.middleware = "JkdAuth"
```

客户端请求需携带 `token` 参数，中间件会调用 `JwtAuth::checkToken()` 校验，失败返回 `Invalid Token` / `Missing Token`。

---

## 2. 接口签名

### 2.1 签名算法

`Auth\ApiAuth` 实现「参数字典序 + 密钥 MD5」签名：

```php
use Auth\ApiAuth;

$apiAuth = new ApiAuth();

// 生成签名
$sign = $apiAuth->getSign($params);

// 或直接得到带 sign 的参数数组
$signed = $apiAuth->setSign($params);
```

签名步骤：

1. 过滤空值，去除 `sign` 字段
2. 参数按键名字典序升序排序（`ksort`）
3. 生成 URL 格式字符串（`http_build_query` + `urldecode`）
4. 拼接 `&key=` + 密钥
5. `strtoupper(md5(...))`

密钥 `ApiAuth::KEY` 生产环境请务必更换。

### 2.2 通过中间件校验

在 `route.ini` 挂载 `JkdSign`：

```ini
[route]
api.middleware = "JkdSign"
```

`JkdSign` 中间件校验：

1. 请求需携带 `ts`（时间戳）与 `sign`；
2. `ts` 必须在有效时间窗内（`app.ini` 的 `apiTs`，默认 60 秒）；
3. 服务端按相同算法重新计算签名并与 `sign` 比对。

### 2.3 客户端请求示例

```bash
# 假设参数为 name=jack&age=18，密钥为 KEY
# sign = strtoupper(md5("age=18&name=jack&key=KEY"))

curl "http://localhost:12222/api/index?name=jack&age=18&ts=1630000000&sign=XXXX"
```

---

## 3. 组合使用

可同时挂载签名与认证：

```ini
[route]
api.middleware = "JkdSign,JkdAuth"
```

中间件按顺序执行：先校验签名，再校验 Token。

---

相关文档：[05-middleware-aop.md](05-middleware-aop.md) · [02-configuration.md](02-configuration.md)
