<title id="title">通知列表 - {$title}</title>
<style id="style">

    /* 筛选：与书籍列表一致，下拉菜单限高 */
    #notificationSearchForm mdui-select::part(menu) {
        max-height: 50vh;
        width: fit-content;
        overflow-y: auto;
    }

    /* 单列、最大 720px，覆盖 CardView 默认 grid（不修改 CardView 源码） */
    #notificationsRoot .card-view-container {
        display: grid;
        grid-template-columns: minmax(0, 720px);
        justify-content: center;
        justify-items: stretch;
        gap: 1rem;
        padding: 0 0 1rem;
        box-sizing: border-box;
    }

    #notificationsRoot .card-view-item {
        max-width: 720px;
        width: 100%;
        margin-inline: auto;
        border: none !important;
        border-radius: 0;
        padding: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        cursor: default;
        transition: none;
    }

    #notificationsRoot .card-view-item:hover {
        transform: none !important;
        box-shadow: none !important;
        border-color: transparent !important;
    }

    @media (prefers-color-scheme: dark) {
        #notificationsRoot .card-view-item,
        #notificationsRoot .card-view-item:hover {
            background: transparent !important;
            border-color: transparent !important;
        }
    }

    #notificationsRoot .card-content {
        padding: 0;
        width: 100%;
        max-width: 720px;
        margin-inline: auto;
    }

    .notification-card-inner {
        width: 100%;
        max-width: 720px;
        min-width: 0;
        margin-inline: auto;
    }

    .notification-card-inner notify-card {
        display: block;
        max-width: 720px;
    }

</style>

<div id="container" class="container">
    <div  class="row col-space16">
        <div class="col-xs12 title-large center-vertical mb-4">
            <mdui-icon name="campaign" class="mr-2"></mdui-icon>
            <span>通知列表</span>
        </div>

        <div class="col-xs12">
            <form id="notificationSearchForm" class="mb-3">
                <div class="row col-space12">
                    <div class="col-xs12 col-sm6 ">
                        <mdui-select
                                class="w-100"
                                variant="outlined"
                                name="app_id"
                                label="通知渠道">
                            <mdui-menu-item value="">全部</mdui-menu-item>
                        </mdui-select>
                    </div>
                    <div class="col-xs12 col-sm6 ">
                        <mdui-select
                                class="w-100"
                                variant="outlined"
                                name="priority"
                                label="优先级">
                            <mdui-menu-item value="">全部</mdui-menu-item>
                            <mdui-menu-item value="info">信息</mdui-menu-item>
                            <mdui-menu-item value="warning">警告</mdui-menu-item>
                            <mdui-menu-item value="error">错误</mdui-menu-item>
                            <mdui-menu-item value="success">成功</mdui-menu-item>
                        </mdui-select>
                    </div>

                </div>
            </form>

            <div id="notificationsRoot"></div>
        </div>
    </div>
</div>

<script id="script" src="/static/js/notifications/list.js?v={$__v}"></script>
