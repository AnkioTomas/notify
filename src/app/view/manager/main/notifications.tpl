<title id="title">通知列表 - {$title}</title>
<style id="style">

    /* cardView：两列栅格，窄屏单列 */
    .notifications-card-view {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;
        align-items: stretch;
    }

    @media (min-width: 900px) {
        .notifications-card-view {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .notification-card-slot {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .notification-card-slot notify-card {
        flex: 1 1 auto;
    }

</style>

<div id="container" class="container">
    <div class="row">
        <div class="col-xs12 title-large center-vertical mb-4">
            <mdui-icon name="campaign" class="refresh mr-2"></mdui-icon>
            <span>通知列表</span>
        </div>
        <div class="col-xs12">
            <mdui-card class="p-4 w-100 items-center d-flex mb-3" style="display: flex">
                <mdui-select id="notifPageSize" value="10" label="每页条数" variant="outlined" style="min-width: 8rem;">
                    <mdui-menu-item value="6">6</mdui-menu-item>
                    <mdui-menu-item value="10">10</mdui-menu-item>
                    <mdui-menu-item value="20">20</mdui-menu-item>
                    <mdui-menu-item value="50">50</mdui-menu-item>
                </mdui-select>
                <div style="flex-grow: 1"></div>
                <mdui-button-icon icon="refresh" id="notifRefresh" title="刷新"></mdui-button-icon>
            </mdui-card>

            <div id="notificationsCardView" class="notifications-card-view w-100" aria-busy="false"></div>

            <mdui-card class="p-3 mt-3 w-100 d-flex items-center justify-between flex-wrap gap-3">
                <span id="notifPageInfo" class="body-medium text-on-surface-variant"></span>
                <div class="d-flex items-center gap-2">
                    <mdui-button id="notifPrev" variant="outlined">上一页</mdui-button>
                    <mdui-button id="notifNext" variant="filled">下一页</mdui-button>
                </div>
            </mdui-card>
        </div>
    </div>
</div>

<script id="script"
        src="/static/bundle?file=js/notify/NotifyCard.js,js/notifications/list.js&type=js&v={$__v}"></script>
