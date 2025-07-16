<!doctype html>
<html lang="zh-CN" class="mdui-theme-light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <title id="title">{$title}</title>
    {include file="publicHeader.tpl"}
    <link rel="stylesheet" href="/static/css/init.css?v={$__v}">
    <style id="style">

        /* "已读"卡片整体淡化 */
        .opacity-60 {
            opacity: 0.6;
        }

        /* 通知类型样式 */
        .notification-card {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .notification-success {
            border-left-color: #4caf50;
        }

        .notification-success .notification-type-icon {
            color: #4caf50;
        }

        .notification-warning {
            border-left-color: #ff9800;
        }

        .notification-warning .notification-type-icon {
            color: #ff9800;
        }

        .notification-error {
            border-left-color: #f44336;
        }

        .notification-error .notification-type-icon {
            color: #f44336;
        }

        .notification-default {
            border-left-color: #2196f3;
        }

        .notification-default .notification-type-icon {
            color: #2196f3;
        }

        /* 操作按钮样式 */
        .notification-card .flex {
            display: flex;
        }

        .notification-card .gap-2 {
            gap: 8px;
        }

        .notification-card .mt-3 {
            margin-top: 12px;
        }

    </style>
</head>

<body class="bg">
{include file="publicScript.tpl"}
<script src="/static/js/notify.js?v={$__v}"></script>
<mdui-layout style="height: calc(var(--vh))">
    <mdui-top-app-bar scroll-behavior="elevate" scroll-target=".layout-main">
        <mdui-button-icon icon="menu" id="navigation-drawer-switch"></mdui-button-icon>
        <mdui-top-app-bar-title>{$title}</mdui-top-app-bar-title>
        <div style="flex-grow: 1"></div>
        <mdui-button-icon icon="settings" href="/manage"></mdui-button-icon>
        <theme-switcher iconBtn="true"></theme-switcher>
        <lang-switcher iconBtn="true"></lang-switcher>

        <div class="layout-header-right">
            <mdui-dropdown>
                <mdui-button variant="text" class="layout-header-user-info"  slot="trigger">
                    <mdui-avatar src="{$header}"></mdui-avatar>
                    <span>{$nickname}</span>
                </mdui-button>
                <mdui-menu>
                    <mdui-menu-item href="/logout">退出登陆</mdui-menu-item>
                </mdui-menu>
            </mdui-dropdown>


        </div>
    </mdui-top-app-bar>

    <mdui-navigation-drawer open class="navigation-drawer " id="navigation-drawer" close-on-overlay-click >
        <mdui-list class="m-2" id="channels">

        </mdui-list>
    </mdui-navigation-drawer>

    <mdui-layout-main class="layout-main" >
        <div id="container" class="container">

        </div>
    </mdui-layout-main>
</mdui-layout>
<script>

</script>
<script id="script"> </script>
</body>
</html>

