/**
 * 后台通知列表：CardView + notify-card；筛选参考 book.js（#notificationSearchForm + change/input + throttle）
 */
window.pageLoadFiles = ['Form', 'CardView', 'js/notify/NotifyCard.js'];

window.pageOnLoad = function () {
    let cv = null;

    const esc = (v) => $.escapeHtml(v == null ? '' : String(v));

    function initCardView() {
        cv = new CardView('#notificationsRoot');
        cv.load({
            uri: '/notifications/list',
            params: $.url.getAllParams(),
            template: `<div class="notification-card-inner">
                <notify-card
                    priority="{{priority}}"
                    heading="{{title}}"
                    channel="{{channel}}"
                    time="{{time}}"
                    actions="{{actionsEncoded}}"
                >
                    <template data-role="body">{{messageHtml}}</template>
                </notify-card>
            </div>`,
            columns: [
                // title / messageHtml 为 Markdown HTML：属性位只做属性转义，正文保持 HTML
                { field: 'title', name: '标题', formatter: (v) => esc(v) },
                { field: 'priority', name: '优先级', formatter: (v) => esc(v) },
                { field: 'channel', name: '频道', formatter: (v) => esc(v) },
                { field: 'time', name: '时间', formatter: (v) => esc(v) },
                { field: 'actionsEncoded', name: '操作', formatter: (v) => esc(v) },
                { field: 'detailHref', name: '链接', formatter: (v) => esc(v) },
                // messageHtml 故意不转义：Parsedown 输出
                { field: 'messageHtml', name: '正文' },
            ],
            selectable: false,
            cardWidth: '720px',
            empty_msg: '暂无通知',
            pageSizes: [6, 10, 20, 50],
            page: true,
        });

    }

    initCardView();

    $.emitter.on('pjax:prevented',function (params) {
        cv.reload($.url.getAllParams(),true)
    })

    window.pageOnUnLoad = function (){
    }

    return false;
};
