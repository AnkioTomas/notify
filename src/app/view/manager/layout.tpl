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
    </style>
</head>

<body class="bg">
{include file="publicScript.tpl"}
<script src="/static/js/init.js?v={$__v}"></script>
<mdui-layout style="height: calc(var(--vh))">
    <mdui-top-app-bar scroll-behavior="elevate" scroll-target=".layout-main">
        <mdui-button-icon icon="menu" id="navigation-drawer-switch"></mdui-button-icon>
        <mdui-top-app-bar-title>{$title}</mdui-top-app-bar-title>
        <div style="flex-grow: 1"></div>
        <mdui-button-icon icon="home" href="/"></mdui-button-icon>
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
        <mdui-list class="m-2">
            {foreach $menuConfig as $item}
                {if isset($item.sub)}
                    <mdui-collapse>
                        <mdui-collapse-item value="item-{$item@index}">
                            <mdui-list-item slot="header" rounded icon="{$item.icon}">
                                <span>{$item.title}</span>
                                <mdui-icon slot="end-icon" name="keyboard_arrow_left"></mdui-icon>
                            </mdui-list-item>
                            <div style="margin-left: 2.5rem">
                                {foreach $item.sub as $sub}
                                    <mdui-list-item rounded data-match="{isset($sub['match'])?$sub['match']:''}" data-pjax="{$sub.pjax ? 'true' : 'false'}" data-target="{isset($sub['self']) ? 'self' : ''}" data-link="{$sub.url}" icon="{$sub.icon}">{$sub.title}</mdui-list-item>
                                {/foreach}
                            </div>
                        </mdui-collapse-item>
                    </mdui-collapse>
                {else}
                    <mdui-list-item rounded data-match="{isset($sub['match'])?$sub['match']:''}" data-pjax="{$item.pjax ? 'true' : 'false'}" data-target="{isset($sub['self'])  ? 'self' : ''}" data-link="{$item.url}" icon="{$item.icon}">{$item.title}</mdui-list-item>
                {/if}
            {/foreach}
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

