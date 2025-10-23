/**
 * 通知系统 - 全局初始化
 * 负责：pjax 路由、全局轮询、频道菜单、Web 通知
 */
$.loader(['Pjax','Toaster'], () => {
    /* ----------------------------- Breakpoint 适配 ----------------------------- */
    const breakpointCondition = mdui.breakpoint();
    if (breakpointCondition.down("md")) {
        $.waitClass(".navigation-drawer", document.body, () => {
            document.querySelector(".navigation-drawer").open = false;
        });
    }

    /* ----------------------------- 全局状态 ----------------------------- */
    window.notifyState = {
        allData: {},          // { channel: [notice, ...] }
        currentChannel: "all", // 当前频道，默认为"全部"
        notifiedIds: new Set(), // 已推送的 Web 通知 ID
        token: ""            // 订阅 token
    };

    /* -------------------------------------------------------------------------- */
    /*                              频道菜单渲染                                  */
    /* -------------------------------------------------------------------------- */

    function renderMenu(data) {
        const $menu = $("#channels");
        if (!$menu.length) return;

        const channels = Object.keys(data);
        $menu.empty();
        
        // 计算全部频道的未读数量
        let totalUnread = 0;
        channels.forEach(channel => {
            const items = data[channel] || [];
            totalUnread += items.filter(i => !i.is_read).length;
        });
        
        // 添加"全部"选项
        const allBadge = totalUnread > 0 ? `<mdui-badge style="position:absolute;right:1.8rem;top:50%;transform:translateY(-50%);">${totalUnread}</mdui-badge>` : "";
        $menu.append(`
            <mdui-list-item rounded alignment="center" 
                data-channel="all" 
                data-link="/notify"
                data-pjax="true"
                style="position:relative" 
                class="pl-2 pr-2">
                <span>全部</span>
                ${allBadge}
            </mdui-list-item>
        `);
        
        // 添加频道选项
        channels.forEach(channel => {
            const items = data[channel] || [];
            const unread = items.filter(i => !i.is_read).length;
            const badge = unread ? `<mdui-badge style="position:absolute;right:1.8rem;top:50%;transform:translateY(-50%);">${unread}</mdui-badge>` : "";
            $menu.append(`
                <mdui-list-item rounded alignment="center" 
                    data-channel="${channel}" 
                    data-link="/notify/${encodeURIComponent(channel)}"
                    data-pjax="true"
                    style="position:relative" 
                    class="pl-2 pr-2">
                    <span>${channel}</span>
                    ${badge}
                </mdui-list-item>
            `);
        });
    }

    /* -------------------------------------------------------------------------- */
    /*                          Web 通知（去重 & 只一次）                         */
    /* -------------------------------------------------------------------------- */

    function sendWebNotifications(data) {
        if (Notification.permission !== "granted") return;

        Object.entries(data).forEach(([channel, items]) => {
            items.filter(i => !i.is_read).forEach(item => {
                const key = `${channel}:${item.id}`;
                if (window.notifyState.notifiedIds.has(key)) return;
                window.notifyState.notifiedIds.add(key);

                new Notification(`${channel} - ${item.title}`, {
                    body: item.message || "(无内容)",
                    icon: "/static/icons/android-chrome-192x192.png"
                });
            });
        });
    }

    /* -------------------------------------------------------------------------- */
    /*                               数据增量合并                                */
    /* -------------------------------------------------------------------------- */

    function mergeIncrementalData(incremental) {
        Object.entries(incremental).forEach(([channel, items]) => {
            const existing = window.notifyState.allData[channel] || [];
            const ids = new Set(existing.map(i => i.id));
            const merged = [...existing];

            items.forEach(item => {
                if (!ids.has(item.id)) {
                    merged.push(item);
                    ids.add(item.id);
                }
            });
            window.notifyState.allData[channel] = merged;
        });
    }


    /* -------------------------------------------------------------------------- */
    /*                              通知卡片渲染                                  */
    /* -------------------------------------------------------------------------- */
    var md = window.markdownit();
    /**
     * 渲染通知卡片
     * @param {Array} notifications - 通知列表
     */
    function renderNotificationCards(notifications) {
        const $container = $("#container");
        if (!$container.length) return;

        if (notifications.length === 0) {
            $container.html(`
                <div class=" w-100" style="text-align: center;">
                    <mdui-icon name="notifications_none" style="font-size: 48px; opacity: 0.3;"></mdui-icon>
                    <div class="mt-2" style="opacity: 0.6;">暂无通知</div>
                </div>
            `);
            return;
        }

        const cardsHtml = notifications.map(notification => {
            const typeClass = `notification-${notification.type || 'default'}`;
            const opacityClass = notification.is_read ? 'opacity-60' : '';
            const timeAgo = formatTimeAgo(notification.t);
            
            // 构建操作按钮（使用后端驼峰字段）
            let actionButtons = '';
            if (notification.actionLeftText || notification.actionRightText) {
                const leftText = notification.actionLeftText || '';
                const leftUrl = notification.actionLeftUrl || '#';
                const rightText = notification.actionRightText || '';
                const rightUrl = notification.actionRightUrl || '#';
                actionButtons = `
                    <div class="flex gap-2 mt-3">
                        ${leftText ? 
                            `<mdui-button variant="outlined" target="_blank" size="small" href="${leftUrl}">${leftText}</mdui-button>` : ''}
                        ${rightText ? 
                            `<mdui-button variant="filled" target="_blank"  size="small" href="${rightUrl}">${rightText}</mdui-button>` : ''}
                    </div>
                `;
            }

            return `
                <mdui-card class="notification-card ${typeClass} ${opacityClass} p-4 mb-3 w-100" data-id="${notification.id}">
                    <div class="flex items-center gap-3">
                        <!-- 左：通知类型图标 -->
                        <mdui-icon class="notification-type-icon" style="font-size: 28px;" name="${getTypeIcon(notification.type)}"></mdui-icon>

                        <!-- 中：上-渠道名称，下-通知标题与内容 -->
                        <div style="flex:1; min-width:0;">
                            <div class="mb-1 text-secondary" style="font-size: 12px;">
                                ${notification.channel ? `${notification.channel}` : ''}
                            </div>
                            ${notification.title ? `
                            <div style="display:flex; align-items:center; gap:8px;">
                                <h3 class="m-0 text-primary" style="font-size: 16px; font-weight: 600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${notification.title}</h3>
                                <span class="text-tertiary" style="font-size: 12px;">${timeAgo}</span>
                            </div>
                            ` : ''}
                            ${notification.message ? `<p class="m-0 mt-1 text-secondary" style="word-break: break-word;">${md.render(notification.message)}</p>` : ''}
                            ${actionButtons}
                        </div>

                        <!-- 右：阅读状态 -->
                        <div class="ml-1">
                            ${!notification.is_read ? `
                            <mdui-button-icon icon="mark_email_read" size="small" class="mark-read-btn" data-id="${notification.id}"></mdui-button-icon>
                        ` : ''}
                        </div>
                    </div>
                </mdui-card>
            `;
        }).join('');

        $container.html(cardsHtml);
    }

    /**
     * 根据通知类型获取图标
     */
    function getTypeIcon(type) {
        const iconMap = {
            'success': 'check_circle',
            'warning': 'warning',
            'error': 'error',
            'info': 'info',
            'default': 'notifications'
        };
        return iconMap[type] || 'notifications';
    }

    /**
     * 格式化时间显示
     */
    function formatTimeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString * 1000);
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return '刚刚';
        if (diffMins < 60) return `${diffMins}分钟前`;
        if (diffHours < 24) return `${diffHours}小时前`;

        return $.formatDateTime(date);
    }

    /**
     * 根据当前频道筛选通知
     */
    function getFilteredNotifications(data, currentChannel) {
        if (currentChannel === 'all') {
            // 合并所有频道的通知
            const allNotifications = [];
            Object.entries(data).forEach(([channel, items]) => {
                items.forEach(item => {
                    allNotifications.push({
                        ...item,
                        channel: channel
                    });
                });
            });
            // 未读优先；同状态按 t 时间戳倒序（t 为秒）
            return allNotifications.sort((a, b) => (Number(a.is_read) - Number(b.is_read)) || ((b.t || 0) - (a.t || 0)));
        } else {
            // 返回特定频道的通知，并补充 channel 字段
            const list = data[currentChannel] || [];
            return list.map(item => ({ ...item, channel: currentChannel }))
                       .sort((a, b) => (Number(a.is_read) - Number(b.is_read)) || ((b.t || 0) - (a.t || 0)));
        }
    }

    /* -------------------------------------------------------------------------- */
    /*                           主入口：菜单 + 通知体                             */
    /* -------------------------------------------------------------------------- */

    function buildUI(data,send) {
        renderMenu(data);

        
        // 渲染通知卡片
        const filteredNotifications = getFilteredNotifications(data, window.notifyState.currentChannel);
        renderNotificationCards(filteredNotifications);

        if (send){
            sendWebNotifications(data);
        }
    }

    /* -------------------------------------------------------------------------- */
    /*                         SSE 实时订阅（替代轮询）                           */
    /* -------------------------------------------------------------------------- */

    let eventSource = null;


    /**
     * 使用 Server-Sent Events 订阅通知
     * 
     * 优势：
     * - 零延迟实时推送
     * - 降低服务器负载（无需轮询）
     * - 减少网络流量
     * - 自动重连机制
     */
    function subscribeSSE(token) {

        // 先拉去列表

        $.request.get(`/list/${token}`,{},function (response){
            window.notifyState.allData = response.data
            renderMenu(response.data)
            // 渲染通知卡片
            const filteredNotifications = getFilteredNotifications(response.data, window.notifyState.currentChannel);
            renderNotificationCards(filteredNotifications);
        })

        if (eventSource) {
            eventSource.close();
        }

        eventSource = new EventSource(`/sse/${token}`);

        // 接收通知数据
        eventSource.addEventListener('message', (e) => {
            try {
                const data = JSON.parse(e.data);
                mergeIncrementalData(data);
                buildUI(window.notifyState.allData,true);
            } catch (err) {
                console.error('SSE parse error:', err);
            }
        });

        // 心跳（保持连接活跃）
        eventSource.addEventListener('heartbeat', (e) => {
            console.debug('SSE heartbeat:', e.data);
        });

        // 连接打开
        eventSource.onopen = () => {
            console.log('SSE connected');
        };

        // 连接错误（自动重连）
        eventSource.onerror = (err) => {
            console.error('SSE error:', err);
            if (eventSource.readyState === EventSource.CLOSED) {
                console.log('SSE reconnecting...');
                setTimeout(() => subscribeSSE(token), 3000);
            }
        };
    }

    /**
     * 启动订阅
     */
    function startSubscribe() {
        if (window.notifyState.token) {
            subscribeSSE(window.notifyState.token);
        } else {
            $.request.get("/sub/get", {}, resp => {
                window.notifyState.token = resp.data;
                subscribeSSE(resp.data);
            });
        }
    }

    /**
     * 页面卸载时关闭 SSE 连接
     */
    window.addEventListener('beforeunload', () => {
        if (eventSource) {
            eventSource.close();
        }
    });


    /**
     * 设置当前活跃频道
     * 更新菜单项的活跃状态
     */
    function setActiveChannel(channel) {
        // 移除所有菜单项的活跃状态
        $("#channels mdui-list-item").removeAttr("active");
        
        // 设置当前频道的活跃状态
        $(`#channels mdui-list-item[data-channel="${channel}"]`).attr("active", "true");
        
        // 更新页面标题
        const title = channel === "all" ? "全部通知" : `${channel} 频道`;
        $("#page-title").text(title);
        
        // 重新渲染通知卡片
        const filteredNotifications = getFilteredNotifications(window.notifyState.allData, channel);
        renderNotificationCards(filteredNotifications);
    }

    /**
     * 标记通知为已读
     */
    function markAsRead(notificationId) {
        if (!window.notifyState.token) return;
        
        $.request.get(`/read/${notificationId}/${window.notifyState.token}`, {}, (response) => {
            if (response.code === 200) {
                // 更新本地数据
                Object.keys(window.notifyState.allData).forEach(channel => {
                    const items = window.notifyState.allData[channel];
                    const item = items.find(item => item.id === notificationId);
                    if (item) {
                        item.is_read = true;
                    }
                });
                
                // 重新渲染UI
                buildUI(window.notifyState.allData);
            }
        });
    }

    // 菜单点击处理
    $(document).on("click", "mdui-list-item[data-channel]", (event) => {
        let targetElem = event.target;
        if (targetElem.tagName !== "MDUI-LIST-ITEM") {
            targetElem = targetElem.closest("mdui-list-item");
        }

        const channel = $(targetElem).data("channel");
        setActiveChannel(channel);
    });

    // 抽屉开关
    $(document).on("click", "#navigation-drawer-switch", () => {
        const drawer = document.querySelector(".navigation-drawer");
        drawer.open = !drawer.open;
    });

    // 通知卡片交互
    $(document).on("click", ".mark-read-btn", (event) => {
        event.stopPropagation();
        const notificationId = $(event.target).data("id");
        if (notificationId) {
            markAsRead(notificationId);
        }
    });

    // 通知卡片点击（可选：展开详情）
    $(document).on("click", ".notification-card", (event) => {
        // 如果点击的是按钮，不处理卡片点击
        if ($(event.target).closest('mdui-button, mdui-button-icon').length > 0) {
            return;
        }
        
        const notificationId = $(event.target).closest('.notification-card').data("id");
        if (notificationId && !$(event.target).closest('.notification-card').hasClass('opacity-60')) {
            // 可以在这里添加展开详情的逻辑
            console.log('点击通知卡片:', notificationId);
        }
    });

    /* -------------------------------------------------------------------------- */
    /*                               启动流程                                    */
    /* -------------------------------------------------------------------------- */

    // 初始化默认状态
    function initDefaultState() {
        // 设置默认活跃频道为"全部"
        setActiveChannel("all");
    }

    if ("Notification" in window && Notification.permission === "default") {
        Notification.requestPermission().finally(() => {
            initDefaultState();
            startSubscribe();
        });
    } else {
        initDefaultState();
        startSubscribe();
    }

});

