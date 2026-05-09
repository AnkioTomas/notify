# Ankio Notify

一个基于 Nova Framework 的轻量通知中心，支持：

- 多通知渠道（channel）管理
- 统一 Token 鉴权的通知发布接口
- 通知短链详情页
- 企业微信主动推送与回调接入
- Web 后台管理（渠道、通知列表、Token、企业微信配置、账户安全）

---

## 项目截图（待补充）

> [图片占位-1] 后台首页 / 通知列表  
> TODO: 在此插入截图链接

> [图片占位-2] 渠道管理页面  
> TODO: 在此插入截图链接

> [图片占位-3] 企业微信配置页面  
> TODO: 在此插入截图链接

> [图片占位-4] 通知详情（短链）页面  
> TODO: 在此插入截图链接

---

## 功能特性

- **通知发布**：`POST /{channel}` 发布通知，支持 Header 承载标题、优先级、动作按钮。
- **统一鉴权**：通过 `Authorization` 请求头进行接口鉴权。
- **短链详情**：自动生成 `short_url`，支持通知公开详情访问。
- **通知管理**：后台分页查看通知，并支持按渠道/优先级筛选。
- **渠道管理**：支持通知渠道新增、编辑、删除（删除渠道会清理对应通知）。
- **企业微信集成**：
  - 通知可主动推送至企业微信应用
  - 提供回调入口 `GET/POST /hook`（验签、解密、接收）

---

## 技术栈

- PHP 8.0+
- Nova Framework（项目内置）
- MySQL 8.x（建议）
- Nginx（推荐，需 rewrite 到 `index.php`）

---

## 快速开始

### 1) 克隆项目

```bash
git clone https://github.com/<your-org>/notify.git
cd notify
```

### 2) 初始化配置

复制示例配置：

```bash
cp src/example.config.php src/config.php
```

然后修改 `src/config.php` 中至少以下项目：

- `db.host` / `db.port` / `db.username` / `db.password` / `db.db`
- `authorization`（用于接口鉴权）
- `work_wechat.corpid` / `work_wechat.to_user`（如启用企业微信）
- `work_wechat.token` / `work_wechat.aes_key`（如启用企业微信回调）

### 3) 启动服务

项目提供 npm scripts（实际执行为 PHP 命令）：

```bash
npm run start
```

可选命令：

```bash
npm run build
npm run test
npm run fix
```

---

## Web Server 配置

项目提供了 `nginx.conf` 核心 rewrite 规则：

```nginx
rewrite ^(.*)$ /index.php/$1 last;
```

你需要将站点根目录指向 `src/public`，并保证请求最终进入 `src/public/index.php`。

---

## 配置说明（核心）

配置文件：`src/config.php`

### 通用

- `debug`：是否启用调试
- `timezone`：时区（默认 `Asia/Shanghai`）
- `domain`：允许访问域名/IP

### 数据库

- `db.type`：数据库类型（当前为 `mysql`）
- `db.charset`：字符集（建议 `utf8mb4`）

### 登录与会话

- `login.*`：后台登录/SSO 相关参数
- `session.*`：会话时长与 Session 名称

### 通知接口鉴权

- `authorization`：通知发布接口鉴权值，对应请求头 `Authorization`

### 企业微信

- `work_wechat.corpid`
- `work_wechat.to_user`
- `work_wechat.token`
- `work_wechat.aes_key`

---

## 接口速览

### 1) 发布通知

- **URL**: `POST /{channel}`
- **鉴权**: `Authorization: <your-token>`
- **Headers（可选）**:
  - `X-Title`: 通知标题（建议 URL 编码）
  - `X-Priority`: `info | warning | error | success`
  - `X-Actions`: 动作按钮，格式：`名称,链接;名称,链接;`
- **Body**: 通知正文（支持 Markdown）

示例：

```bash
curl -X POST "http://127.0.0.1:8080/ops" \
  -H "Authorization: your-token" \
  -H "X-Title: Deploy%20Success" \
  -H "X-Priority: success" \
  -H "X-Actions: Dashboard,https://example.com;Logs,https://example.com/logs;" \
  --data-binary "发布完成，服务已恢复。"
```

### 2) 企业微信回调

- **URL**: `GET/POST /hook`
- 用于企业微信回调校验与消息接收（当前接收后记录日志，未实现被动回复）

---

## 路由概览

- `/`：后台入口
- `/notifications`：通知列表页
- `/channel`：通知渠道管理
- `/token`：订阅 Token 管理
- `/wechat`：企业微信配置页
- `/hook`：企业微信回调入口
- `/{short}`：通知详情短链
- `/{channel}`：通知发布接口

---

## 目录结构

```text
notify/
├── src/
│   ├── app/
│   │   ├── controller/      # 控制器（index / manager）
│   │   ├── database/        # DAO 与 Model
│   │   ├── static/          # 前端静态资源与模板
│   │   └── utils/           # 工具类（企业微信加解密等）
│   ├── nova/                # Nova Framework 与插件
│   ├── public/
│   │   └── index.php        # Web 入口
│   ├── config.php           # 运行配置（请勿提交真实密钥）
│   └── example.config.php   # 配置示例
├── nginx.conf               # Nginx rewrite 示例
├── package.json             # 项目命令入口
└── README.md
```

---

## 安全建议

- 不要在仓库中提交真实 `src/config.php`（包含数据库和密钥信息）。
- 若密钥已经泄露，请立即轮换：
  - 数据库账号密码
  - `authorization`
  - 企业微信 `secret` / `token` / `aes_key`
- 生产环境请关闭 `debug`。

---

## 开发与代码规范

格式化命令：

```bash
npm run fix
```

建议在提交前执行基础自测并验证核心流程：

- 发布通知
- 后台查看通知
- 短链详情访问
- 企业微信消息推送/回调

---

## 贡献

欢迎提交 Issue 和 Pull Request。

建议提 PR 时包含：

- 变更背景（为什么改）
- 影响范围（改了哪些行为）
- 验证方式（如何复现与测试）

---

## License

MIT