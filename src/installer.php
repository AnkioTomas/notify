<?php

declare(strict_types=1);

/**
 * 安装向导步骤与字段配置。
 * 字段 path 对应 config.php 键（如 db.host）；提交后由安装插件写入配置。
 */
return [
    'agreement' => <<<EOT
【最终用户许可与免责协议】

欢迎使用本系统！继续安装和使用即表示您知悉并完全同意以下条款：

1. 核心免责声明
本软件按“原样（AS IS）”免费提供，没有任何明示或暗示的保证（包括但不限于对适销性、特定用途的适用性和非侵权性的保证）。

2. 数据安全与隐私
本系统涉及通知推送、接口令牌与可选的企业微信对接。在任何情况下，作者或版权所有者均不对任何直接或间接的索赔、损害、数据丢失或凭证泄露负责。您须自行保管 Authorization 令牌与企业微信密钥，并定期轮换。

3. 权利与限制
您可以自由修改、学习或分发本软件，但需在副本中保留原有的版权声明。严禁利用本软件从事任何违反当地法律法规的行为，一切因不当使用造成的法律后果均由使用者自行承担。
EOT,
    'env' => [
        'php' => '8.3.0',
        'extensions' => ['curl', 'gd', 'mbstring', 'pdo'],
        'optional_extensions' => ['redis'],
        'pdo_drivers' => ['mysql'],
        'writable' => ['runtime'],
    ],
    'steps' => [
        '数据库配置' => [
            'fields' => [
                'db.host' => [
                    'title' => '数据库主机',
                    'type' => 'input',
                    'default' => '127.0.0.1',
                    'required' => true,
                    'desc' => 'Docker 部署可填容器名，如 mysql',
                ],
                'db.port' => [
                    'title' => '数据库端口',
                    'type' => 'input',
                    'default' => '3306',
                    'required' => true,
                    'desc' => '默认 3306',
                ],
                'db.username' => [
                    'title' => '数据库账号',
                    'type' => 'input',
                    'default' => '',
                    'required' => true,
                    'desc' => '',
                ],
                'db.password' => [
                    'title' => '数据库密码',
                    'type' => 'password',
                    'default' => '',
                    'required' => false,
                    'desc' => '无密码可留空',
                ],
                'db.db' => [
                    'title' => '数据库名',
                    'type' => 'input',
                    'default' => 'notify',
                    'required' => true,
                    'desc' => '请事先创建空库（utf8mb4）；安装向导会自动建表',
                ],
            ],
        ],
        '基本设置' => [
            'fields' => [
                'login.systemName' => [
                    'title' => '系统名称',
                    'type' => 'input',
                    'default' => 'AnkioのNotify',
                    'required' => true,
                    'desc' => '显示在页面标题与登录页',
                ],
                'authorization' => [
                    'title' => '发布接口 Authorization',
                    'type' => 'input',
                    'default' => '',
                    'required' => true,
                    'desc' => '发通知接口校验令牌，装完后务必保存',
                ],
            ],
        ],
        '企业微信（可选）' => [
            'fields' => [
                'work_wechat.corpid' => [
                    'title' => 'CorpID',
                    'type' => 'input',
                    'default' => '',
                    'required' => false,
                    'desc' => '可后续在后台修改',
                ],
                'work_wechat.to_user' => [
                    'title' => '默认接收人',
                    'type' => 'input',
                    'default' => '',
                    'required' => false,
                    'desc' => '',
                ],
                'work_wechat.token' => [
                    'title' => '回调 Token',
                    'type' => 'input',
                    'default' => '',
                    'required' => false,
                    'desc' => '',
                ],
                'work_wechat.aes_key' => [
                    'title' => '回调 EncodingAESKey',
                    'type' => 'input',
                    'default' => '',
                    'required' => false,
                    'desc' => '',
                ],
            ],
        ],
    ],
    'require_admin' => true,
];
