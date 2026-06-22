<!doctype html>
<html lang="zh-CN" class="mdui-theme-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <title id="title">{$title}</title>
    <link rel="preconnect" href="https://fonts.loli.net">
    <link rel="preconnect" href="https://gstatic.loli.net" crossorigin>
    <link href="https://fonts.loli.net/css2?family=Material+Icons&family=Material+Icons+Outlined&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/static/bundle?file=
    framework/libs/mdui.css,
    framework/base.css,
    framework/utils/Loading.css,
    framework/pjax/nprogress.css,
    &type=css&v={$__v}">

    <link rel="apple-touch-icon" sizes="180x180" href="/static/icons/apple-touch-icon.png?v={$__v}"/>
    <link rel="icon" type="image/png" sizes="32x32" href="/static/icons/favicon-32x32.png?v={$__v}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="/static/icons/favicon-16x16.png?v={$__v}"/>

    <style id="style">
    </style>
    <style>
        /* 与 error/layout 一致：PJAX 换 #container；通知可能较长，用 min-height 允许滚动 */
        #container.container {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            min-height: 95vh;
            box-sizing: border-box;
            padding: 1rem;
        }
    </style>
</head>

<body class="bg-background text-on-background">

<script src="/static/bundle?file=
    framework/libs/vhcheck.min.js,
    framework/libs/mdui.global.min.js,
    framework/bootloader.js,
    framework/utils/Loading.js,
    framework/utils/Logger.js,
    framework/utils/Loader.js,
    framework/utils/Event.js,
    framework/utils/Toaster.js,
    framework/utils/Timing.js,
    framework/utils/Form.js,
    framework/utils/Request.js,
    framework/theme/ThemeSwitcher.js,
    framework/language/NodeUtils.js,
    framework/language/TranslateUtils.js,
    framework/language/Language.js,
    framework/pjax/pjax.min.js,
    framework/pjax/nprogress.js,
    framework/pjax/PjaxUtils.js,
    &type=js&v={$__v}"></script>
<script>
    let level = '{if $__debug}debug{else}error{/if}';
    $.logger.setLevel(level);
    $.logger.info('App is running in ' + level + ' mode');
    $.request.setBaseUrl(baseUri).setOnCode(401, (response) => {
        $.toaster.error('登录已过期，请重新登录');
        setTimeout(() => {
            window.location.href = response.data || '/login';
        }, 1000);
    }).setOnCode(301, (response) => {
        window.location.href = response.data;
    });
</script>

<div class="container" id="container">

</div>

<script>
    let pjax = new PjaxUtils(function () {

    }, "/404");
    pjax.loadUri(window.location.pathname);
    $("[data-pjax-item]").on("click", function () {
        pjax.loadUri($(this).data("href"));
    });
</script>
<script id="script"></script>
</body>
</html>
