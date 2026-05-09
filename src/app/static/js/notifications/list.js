/**
 * 后台通知列表：CardView + notify-card；筛选参考 book.js（#notificationSearchForm + change/input + throttle）
 */
window.pageLoadFiles = ['Form', 'CardView', 'js/notify/NotifyCard.js'];



window.pageOnLoad = function () {
    let cv = null;


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
                { field: 'title', name: '标题' },
                { field: 'priority', name: '优先级' },
                { field: 'channel', name: '频道' },
                { field: 'time', name: '时间' },
                { field: 'detailHref', name: '链接' },
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
