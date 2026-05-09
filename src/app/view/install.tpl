<!DOCTYPE html>
<html lang="zh-CN" class="mdui-theme-light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <title>{$title} - 安装向导</title>

    <link rel="preconnect" href="https://fonts.loli.net">
    <link rel="preconnect" href="https://gstatic.loli.net" crossorigin>
    <link href="https://fonts.loli.net/css2?family=Material+Icons&family=Material+Icons+Outlined&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/static/bundle?file=
    framework/libs/mdui.css,
    framework/base.css,
    framework/utils/Loading.css
    &type=css&v={$__v}">

    <style>
        body {
            background-image: url('https://api.ankio.net/bing');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            min-height: 100vh;
            position: relative;
            margin: 0;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: var(--overlay-color);
            pointer-events: none;
        }

        :root {
            --overlay-color: rgba(0, 0, 0, 0.5);
        }

        .mdui-theme-light {
            --overlay-color: rgba(191, 191, 191, 0.3);
        }

        @media (prefers-color-scheme: light) {
            .mdui-theme-auto {
                --overlay-color: rgba(191, 191, 191, 0.3);
            }
        }

        .install-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px 16px;
            align-items: start;
        }

        .install-grid mdui-text-field {
            display: block;
            width: 100%;
            box-sizing: border-box;
        }

        .install-grid-full {
            grid-column: 1 / -1;
        }

        @media (max-width: 600px) {
            .install-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        .settings-fab mdui-menu {
            background: transparent;
            border: 0;
            box-shadow: none;
            width: unset;
            max-width: unset;
            min-width: unset;
        }

    </style>
</head>
<body>

<div class="d-flex flex-col items-center justify-center min-h-screen position-relative z-1 px-3 py-4">
    <mdui-card variant="filled" class="w-full max-w-sm p-4">
        <div class="text-center mb-4">
            <mdui-icon name="auto_fix_high" style="font-size: 40px;color: rgb(var(--mdui-color-primary));"></mdui-icon>
            <div class="headline-medium font-bold mt-1">{$title} 安装向导</div>
            <div class="body-small text-on-surface-variant mt-2">
                第一次部署？这里只需要填数据库、系统名和企业微信参数
            </div>
        </div>

        <form id="installForm">
            <div class="mb-4">
                <h3 class="d-flex items-center gap-2 mb-2 title-medium text-on-surface">
                    <mdui-icon name="storage"></mdui-icon>
                    数据库（MySQL / MariaDB）
                </h3>
                <div class="install-grid">
                    <mdui-text-field
                        name="db_host"
                        label="主机"
                        value="127.0.0.1"
                        helper="Docker 部署可填容器名，如 mysql"
                        required>
                    </mdui-text-field>
                    <mdui-text-field
                        name="db_port"
                        label="端口"
                        type="number"
                        value="3306"
                        required>
                    </mdui-text-field>
                    <mdui-text-field
                        name="db_username"
                        label="账号"
                        required>
                    </mdui-text-field>
                    <mdui-text-field
                        name="db_password"
                        label="密码"
                        type="password"
                        toggle-password>
                    </mdui-text-field>
                    <mdui-text-field
                        name="db_name"
                        label="库名"
                        helper="需提前创建空库（utf8mb4）"
                        required
                        class="install-grid-full">
                    </mdui-text-field>
                </div>
            </div>

            <mdui-divider class="mt-2 mb-3"></mdui-divider>

            <div class="mb-4">
                <h3 class="d-flex items-center gap-2 mb-2 title-medium text-on-surface">
                    <mdui-icon name="tune"></mdui-icon>
                    系统信息
                </h3>
                <div class="install-grid">
                    <mdui-text-field
                        name="system_name"
                        label="系统名称"
                        value="{$title}"
                        helper="登录页和顶栏显示的名称">
                    </mdui-text-field>
                    <mdui-text-field
                        name="authorization"
                        label="发布接口 Authorization"
                        helper="留空自动生成">
                    </mdui-text-field>
                </div>
            </div>

            <mdui-divider class="mt-2 mb-3"></mdui-divider>

            <div class="mb-4">
                <h3 class="d-flex items-center gap-2 mb-2 title-medium text-on-surface">
                    <mdui-icon name="forum"></mdui-icon>
                    企业微信（可后续在后台修改）
                </h3>
                <div class="install-grid">
                    <mdui-text-field
                        name="corpid"
                        label="CorpID"
                        class="install-grid-full">
                    </mdui-text-field>
                    <mdui-text-field
                        name="to_user"
                        label="默认接收人">
                    </mdui-text-field>
                    <mdui-text-field
                        name="token"
                        label="回调 Token">
                    </mdui-text-field>
                    <mdui-text-field
                        name="aes_key"
                        label="回调 EncodingAESKey"
                        class="install-grid-full">
                    </mdui-text-field>
                </div>
            </div>

            <div class="d-flex justify-end gap-2 mt-3">
                <mdui-button form="installForm" type="submit" variant="filled" icon="rocket_launch" full-width>
                    开始安装
                </mdui-button>
            </div>
        </form>
    </mdui-card>

    <div class="mt-3 body-small text-on-surface-variant text-center">
        <p>© {date('Y')} <a class="no-underline" href="https://ankio.net" target="_blank">Ankio</a>. All rights reserved.</p>
    </div>
</div>

<div class="settings-fab position-fixed d-flex flex-col z-100" style="right:1rem;bottom:1rem;">
    <mdui-dropdown>
        <mdui-fab icon="settings" slot="trigger"></mdui-fab>
        <mdui-menu>
            <theme-switcher class="mb-2"></theme-switcher>
        </mdui-menu>
    </mdui-dropdown>
</div>

<script src="/static/bundle?file=
framework/libs/vhcheck.min.js,
framework/libs/mdui.global.min.js,
framework/bootloader.js,
framework/utils/Loading.js,
framework/utils/Logger.js,
framework/utils/Loader.js,
framework/utils/Event.js,
framework/utils/Toaster.js,
framework/utils/Layer.js,
framework/utils/Form.js,
framework/utils/Request.js,
framework/theme/ThemeSwitcher.js,
framework/language/NodeUtils.js,
framework/language/TranslateUtils.js,
framework/language/Language.js
&type=js&v={$__v}"></script>
<script>
    (function () {
        window.mainAppLoading.close();

        const form = document.getElementById('installForm');

        $.form.submit('#installForm', {
            callback: function (data) {
                $(form).showLoading('正在写入配置并初始化数据库...');

                $.request.postForm('/install/submit', data,
                    function (res) {
                        $(form).closeLoading();
                        if (res.code !== 200) {
                            $.toaster.error(res.msg || '安装失败');
                            return;
                        }

                        const info = res.data;
                        const lines = ['安装完成'];
                        lines.push('管理员账号: admin');
                        lines.push('管理员密码: ' + info.adminPassword || '（未读取到初始密码，请查看 runtime/admin_password.txt）');

                        $.layer.alert({
                            title: '安装完成',
                            msg: lines.join('<br>'),
                            yes: function () {
                                location.href = info.redirect || '/login';
                            }
                        });
                    },
                    function () {
                        $(form).closeLoading();
                    }
                );

                return false;
            }
        });
    })();
</script>
</body>
</html>
