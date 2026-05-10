# Ankio Notify

轻量通知中心：HTTP 发布、短链详情、后台管理；可选推到企业微信应用。

---

## 特性：多格式发布

同一入口 **`POST /{渠道短标识}`**，用查询参数 **`type`** 选择载荷格式（省略时默认为 **`ntfy`**，与 ntfy 风格兼容）。鉴权支持 **`Authorization` 请求头**，也可与 **`type`** 一起放在查询参数 **`authorization`** 里（便于钉钉、飞书等只填 URL 的场景）。

| `type` | 说明 |
|--------|------|
| **`ntfy`**（默认） | 请求头：`X-Title`、`X-Priority`、`X-Actions`；正文为 **纯文本**。适合自建脚本、兼容 ntfy 的客户端。 |
| **`dingding`** | 钉钉自定义机器人：**JSON**，`msgtype` + `text.content`（首行可作标题，与后端解析一致）。 |
| **`feishu`** | 飞书：**JSON**，`msg_type` + `content.text`。 |
| **`wechat`** | 企业微信机器人：**JSON**，结构与钉钉类似（`msgtype` + `text.content`）。 |
| **`form`** | **表单**：字段 `title`、`message`、`priority` 等（`application/x-www-form-urlencoded` 或 `multipart/form-data`）。 |
| **`json`** | **JSON 正文**：如 `title`、`message`、`priority`，由服务端解析为内部通知模型。 |

**响应格式**会按 `type` 贴近各平台习惯（例如钉钉/企微返回 `errcode`/`errmsg`，飞书返回 `code`/`msg` 等），减少接入方把成功误判为失败的情况。

**后台**：「发布通知」页可按模式切换 **cURL / 原始 HTTP / JavaScript / Go / Python / PHP** 示例；「渠道」列表可复制带 **`type`** 与 **`authorization`** 的完整 URL。

---

## 1. 部署

**需要：** PHP 8.0+（含 `pdo_mysql`）、MySQL 8、Nginx（或同类）+ PHP-FPM。

- **网站根目录**必须是 **`src/public`**。
- **`src/runtime`** 需可写。
- MySQL **先建好空数据库**（向导不替你建库）。

伪静态：
```nginx
rewrite ^(.*)$ /index.php/$1 last;
```

---

## 2. 安装

浏览器打开 **`/install`**，填数据库和站点信息；

留空的 **`authorization`** 会随机生成。装完后**记下来**，发通知时要放在请求头 `Authorization` 里。

---

## 3. 企业微信

**在企业微信后台：**
    
- 记下 **企业 ID（corpid）**；
- 自建应用记下 **AgentId、Secret**，成员放进应用可见范围；
- 添加回调：**URL `https://你的域名/hook`**，Token、`EncodingAESKey` 自定，须能在公网校验。
- 发 API 的出口 IP **加可信 IP**。

**在 Notify：** 
- 后台 **`/wechat`**（或直接 `config.php`）填：`corpid`、`to_user`（接收人 UserID，多个用 **`|`**）、`token`、`aes_key`（与上面回调一致）。

**后台「渠道」：**
- 每条渠道填 **短名**（即 URL 里的 `/{channel}`）和对应的 **`agent_id`、`secret`**。
- 推送条件：`to_user` 非空且该渠道这两项已配置；**收件人共用全局 `to_user`**。

---

## 4. 发通知

更完整的格式说明见文档开头的 **「特性：多格式发布」** 一节；以下为最常见的 **ntfy 风格**示例（`type` 省略即等同于 `ntfy`）：

```bash
curl -X POST "https://你的域名/ops" \
  -H "Authorization: 安装时记的 token" \
  -H "X-Title: 标题" \
  -H "X-Priority: success" \
  -H "X-Actions: 面板,https://a.com;日志,https://a.com/logs;" \
  --data-binary "正文"
```

- **`X-Priority`：** `info` / `warning` / `error` / `success`
- **`X-Actions`：** 多段用 **`;`**，每段 **`名称,URL`**
- 企微推送失败时可能仍 **HTTP 200**，请看 JSON 里的 **`code` / `msg`**

详情短链：`GET /{short_url}`（在发布返回的 JSON 里）。

---

## 截图

![img.png](img.png)

![img_5.png](img_5.png)

![img_2.png](img_2.png)

![img_3.png](img_3.png)

![img_1.png](img_1.png)

**License：** MIT  

开发辅助：`npm run build` / `test` / `fix`
