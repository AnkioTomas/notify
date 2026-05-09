/**
 * 后台通知列表：CardView + notify-card；筛选参考 book.js（#notificationSearchForm + change/input + throttle）
 */
window.pageLoadFiles = ['Form', 'CardView', 'js/notify/NotifyCard.js'];



window.pageOnLoad = function () {
    let cv = null;

    function fillChannelSelect(rows) {
        const sel = document.querySelector('#notificationSearchForm [name="app_id"]');
        if (!sel) return;
        const frag = document.createDocumentFragment();
        for (const app of rows || []) {
            const item = document.createElement('mdui-menu-item');
            item.value = String(app.id);
            item.textContent = app.name;
            frag.appendChild(item);
        }
        sel.appendChild(frag);
    }

    function initCardView() {
        cv = new CardView('#notificationsRoot');
        cv.load({
            uri: '/notifications/list',
            params: '#notificationSearchForm',
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

        const throttledReload = $.throttle(() => cv?.reload(true), 300);
        $('#notificationSearchForm').on(
            'input change',
            'mdui-text-field, mdui-select, input, select',
            throttledReload,
        );
    }

    $.request.get('/channel/list', { page: 1, pageSize: 500 }, (res) => {
        fillChannelSelect(res.data);
        initCardView();
    });

    return false;
};
