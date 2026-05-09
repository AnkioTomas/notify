<title id="title">{$title}</title>
<style id="style">
    .notify-page-shell {
        width: 100%;
        max-width: 960px;
        margin: 0 auto;
    }
</style>

<div id="container" class="container notify-page-shell">
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

<script id="script">
    /** 布局已加载 MDUI；仅按需拉取自定义元素脚本，与 nova/plugin/tpl/error/error.tpl 同源流程 */
    window.pageLoadFiles = ["js/notify/NotifyCard.js&type=js&v={$__v}"];
    window.pageOnLoad = function () {
        return false;
    };
</script>
