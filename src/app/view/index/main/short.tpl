<!doctype html>
<html lang="zh-CN" class="mdui-theme-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no"/>
    <meta name="renderer" content="webkit"/>
    <title>{$title}</title>
    <!-- 预连接到 Google Fonts,减少 DNS 查询延迟 -->
    <link rel="preconnect" href="https://fonts.loli.net">
    <link rel="preconnect" href="https://gstatic.loli.net" crossorigin>
    <!-- 使用 font-display=swap 避免字体加载时的布局偏移 -->
    <link href="https://fonts.loli.net/css2?family=Material+Icons&family=Material+Icons+Outlined&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/static/bundle?file=framework/libs/mdui.css,framework/base.css&type=css&v={$__v}"/>

    <link rel="apple-touch-icon" sizes="180x180" href="/static/icons/apple-touch-icon.png?v={$__v}"/>
    <link rel="icon" type="image/png" sizes="32x32" href="/static/icons/favicon-32x32.png?v={$__v}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="/static/icons/favicon-16x16.png?v={$__v}"/>

    <style>
        /* 仅保留 base.css 无法表达的内容： */
        /* 1) 卡片宽度（720px 经典阅读宽度，base.css 没有完全匹配的 max-w-*） */
        /* 2) priority 边框色（4 个 mdui token 变体） */
        /* 3) Markdown 正文（Parsedown 输出的 HTML 不带 class，必须靠选择器） */

        .notify-card {
            max-width: 720px;
            border-left: 6px solid rgba(var(--mdui-color-primary));
        }
        .notify-card.notify-tertiary  { border-left-color: rgba(var(--mdui-color-tertiary)); }
        .notify-card.notify-error     { border-left-color: rgba(var(--mdui-color-error)); }
        .notify-card.notify-secondary { border-left-color: rgba(var(--mdui-color-secondary)); }

        .notify-icon-sm { font-size: 1rem; }

        .notify-body { line-height: 1.7; }
        .notify-body > *:first-child { margin-top: 0; }
        .notify-body > *:last-child  { margin-bottom: 0; }

        .notify-body img {
            max-width: 100%;
            border-radius: 8px;
        }

        .notify-body pre {
            background: rgba(var(--mdui-color-surface-container));
            padding: 0.75rem 1rem;
            border-radius: 8px;
            overflow: auto;
        }

        .notify-body code {
            background: rgba(var(--mdui-color-surface-container));
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
            font-size: 0.92em;
        }

        .notify-body pre code {
            background: transparent;
            padding: 0;
        }

        .notify-body blockquote {
            border-left: 4px solid rgba(var(--mdui-color-outline-variant));
            padding: 0.25rem 0.85rem;
            margin: 0.75rem 0;
            color: rgba(var(--mdui-color-on-surface-variant));
        }

        .notify-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.5rem 0;
        }

        .notify-body th,
        .notify-body td {
            border: 1px solid rgba(var(--mdui-color-outline-variant));
            padding: 0.4rem 0.6rem;
        }

        .notify-body hr {
            border: 0;
            border-top: 1px solid rgba(var(--mdui-color-outline-variant));
            margin: 1rem 0;
        }

        .notify-body ul,
        .notify-body ol {
            padding-left: 1.5rem;
            margin: 0.5rem 0;
        }

        .notify-body li {
            list-style: revert;
            margin: 0.15rem 0;
        }
    </style>
</head>

<body class="bg-background text-on-background">

<div class="min-h-screen d-flex items-start justify-center p-3">
    <mdui-card variant="elevated" class="notify-card notify-{$priorityClass} w-full p-3">

        <div class="d-flex items-center flex-wrap gap-2 mb-3">
            <span class="badge badge-sm rounded-full bg-{$priorityClass} text-on-{$priorityClass} d-inline-flex items-center font-medium">
                <mdui-icon name="{$priorityIcon}" class="notify-icon-sm mr-1"></mdui-icon>
                {$priorityLabel}
            </span>
            <div class="flex-1"></div>
            <span class="d-inline-flex items-center body-small text-on-surface-variant">
                <mdui-icon name="forum" class="notify-icon-sm mr-1"></mdui-icon>
                {$channelName}
            </span>
        </div>

        <h1 class="headline-medium text-{$priorityClass} font-bold mb-3 break-words">{$notifyTitle}</h1>

        <div class="notify-body body-large text-on-surface">{$bodyHtml nofilter}</div>

        {if !empty($actions)}
            <div class="d-flex flex-wrap gap-2 mt-3">
                {foreach $actions as $label => $href}
                    <mdui-button variant="filled" href="{$href}" target="_blank" rel="noopener noreferrer">
                        {$label}
                    </mdui-button>
                {/foreach}
            </div>
        {/if}

        <mdui-divider class="my-3"></mdui-divider>

        <div class="d-flex flex-wrap items-center gap-3 body-small text-on-surface-variant">
            {if $time !== ''}
                <span class="d-inline-flex items-center">
                    <mdui-icon name="schedule" class="notify-icon-sm mr-1"></mdui-icon>
                    {$time}
                </span>
            {/if}
            <div class="flex-1"></div>
            <a href="/" class="d-inline-flex items-center no-underline text-on-surface-variant">
                <mdui-icon name="home" class="notify-icon-sm mr-1"></mdui-icon>
                返回首页
            </a>
        </div>
    </mdui-card>
</div>

<script src="/static/bundle?file=framework/libs/mdui.global.min.js&type=js&v={$__v}"></script>
</body>
</html>
