<!doctype html>
<html lang="zh-CN" class="mdui-theme-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <title>{$title}</title><!-- 预连接到 Google Fonts,减少 DNS 查询延迟 -->
    <link rel="preconnect" href="https://fonts.loli.net">
    <link rel="preconnect" href="https://gstatic.loli.net" crossorigin>
    <!-- 使用 font-display=swap 避免字体加载时的布局偏移 -->
    <link href="https://fonts.loli.net/css2?family=Material+Icons&family=Material+Icons+Outlined&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/static/bundle?file=framework/libs/mdui.css,framework/base.css&type=css&v={$__v}"/>

    <link rel="apple-touch-icon" sizes="180x180" href="/static/icons/apple-touch-icon.png?v={$__v}"/>
    <link rel="icon" type="image/png" sizes="32x32" href="/static/icons/favicon-32x32.png?v={$__v}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="/static/icons/favicon-16x16.png?v={$__v}"/>
</head>

<body class="bg-background text-on-background">

<div class="min-h-screen d-flex items-start justify-center p-3">
    <notify-card
        priority="{$priority}"
        heading="{$heading}"
        channel="{$channel}"
        time="{$time}"
        back-href="/">
        <template data-role="body">{$bodyHtml nofilter}</template>
        <script type="application/json" data-role="actions">{$actionsJson nofilter}</script>
    </notify-card>
</div>

<script src="/static/bundle?file=framework/libs/mdui.global.min.js,js/notify/NotifyCard.js&type=js&v={$__v}"></script>
</body>
</html>
