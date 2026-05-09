/**
 * 后台通知列表：CardView + notify-card；筛选交互参考 book.js（表单 + change/input 自动刷新，无单独筛选按钮）
 */
window.pageLoadFiles = [
    'Form',
    'CardView',
    'js/notify/NotifyCard.js',
];


window.pageOnLoad = function () {

    let cv = null;

    function initCardView() {
        cv = new CardView('#notificationsRoot');

        cv.load({
            uri: '/notifications/list',
            params: '#notificationSearchForm',
            template: `
<div class="notification-card-inner" >
<notify-card priority="{{priority}}" heading="{{title}}" channel="{{channel}}" time="{{time}}">
<template data-role="body">{{messageHtml}}</template>
</notify-card>
</div>`,
            columns: [
                {
                    field: 'title',
                    name: '标题',
                },
                {field: 'priority', name: '优先级'},
                {field: 'channel', name: '频道'},
                {field: 'time', name: '时间'},
                {
                    field: 'detailHref',
                    name: '链接',
                },
            ],
            selectable: false,
            cardWidth: '720px',
            empty_msg: '暂无通知',
            pageSizes: [6, 10, 20, 50],
            page: true,
            events: {
                onCardClick: function () {
                },
            },
        });

        const triggerReload = () => {
            if (!cv) {
                return;
            }
            cv.reload(true);
        };

        const throttledSearch = $.throttle(triggerReload, 300);

        $('#notificationSearchForm').on('input change', 'mdui-text-field, mdui-select, input, select', () => {
            throttledSearch();
        });


    }
    $.request.get(
        '/channel/list',
        { page: 1, pageSize: 500 },
        (res) => {
            const sel = document.querySelector("#notificationSearchForm [name=\"app_id\"]");
            if (sel) {
                (res.data || []).forEach((app) => {
                    const mi = document.createElement('mdui-menu-item');
                    mi.value = String(app.id);
                    mi.textContent = app.name ;
                    sel.appendChild(mi);
                });
            }
            initCardView();
        }
    );

    return false;
};
