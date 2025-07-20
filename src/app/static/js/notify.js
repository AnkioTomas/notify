$.loader([], () => {
    /* ----------------------------- Breakpoint 适配 ----------------------------- */
    const breakpointCondition = mdui.breakpoint();
    if (breakpointCondition.down("md")) {
        $.waitClass(".navigation-drawer", document.body, () => {
            document.querySelector(".navigation-drawer").open = false;
        });
    }

    /* ----------------------------- 全局状态 ----------------------------- */
    const state = {
        allData: {},          // { channel: [notice, ...] }
        currentChannel: "",  // 当前所选渠道
        lastChanel:"",
        notifiedIds: new Set()
    };

    /* ----------------------------- 轮询控制 ----------------------------- */
    let uri = "";        // 订阅地址
    let token = "";      // 后端返回的 token
    let sinceTs = 0;      // 上次拉取时间（秒）

    /* -------------------------------------------------------------------------- */
    /*                              元素生成 & 更新                               */
    /* -------------------------------------------------------------------------- */

    /**
     * 生成通知卡片（含可选「外链」按钮）
     * @param {Object} item  { id, title, t, message, type, is_read, actionLeftUrl, actionLeftText, actionRightUrl, actionRightText }
     * @param {String} channel 数据来源
     * @returns {jQuery}  mdui-card 节点
     */
    function createCard(item, channel) {
        const isRead = !!item.is_read;

        // 根据通知类型设置样式类
        const typeClass = item.type || 'default';
        const typeIcon = {
            'success': 'check_circle',
            'warning': 'warning',
            'error': 'error',
            'default': 'info'
        }[typeClass] || 'info';

        // 右侧按钮：未读→可点击，已读→勾灰
        const btn = isRead
            ? '<mdui-icon name="done_all" title="已读" disabled></mdui-icon>'
            : `<mdui-button variant="text" icon class="mark-read" title="标记已读">
         <mdui-icon name="done"></mdui-icon>
       </mdui-button>`;

        // 操作按钮区域
        let actionButtons = '';
        if (item.actionLeftText || item.actionRightText) {
            actionButtons = '<div class="mt-3 flex gap-2">';
            
            if (item.actionLeftText) {
                actionButtons += `
                    <mdui-button variant="outlined" size="small" 
                        ${item.actionLeftUrl ? `href="${item.actionLeftUrl}" target="_blank"` : ''}>
                        ${item.actionLeftText}
                    </mdui-button>`;
            }
            
            if (item.actionRightText) {
                actionButtons += `
                    <mdui-button variant="filled" size="small" 
                        ${item.actionRightUrl ? `href="${item.actionRightUrl}" target="_blank"` : ''}>
                        ${item.actionRightText}
                    </mdui-button>`;
            }
            
            actionButtons += '</div>';
        }
        return $(`
    <mdui-card class="m-2 p-4 ${isRead ? 'opacity-60' : ''} notification-card notification-${typeClass}"
               style="width:100%;"
               data-id="${item.id}" data-channel="${channel}">
      <!-- Flex 容器：纵向排列，Link 区域推到最底部 -->
      <div style="display:flex; flex-direction:column; height:100%;">

        <!-- 头部（标题+时间+已读按钮）：仍采用 Grid -->
        <div class="card-header"
             style="
               display:grid;
               grid-template-columns:1fr auto;
               grid-template-rows:auto auto;
               gap:4px;
               align-items:center;
             ">
          <div style="grid-column:1; grid-row:1; display:flex; align-items:center; gap:8px;">
            <mdui-icon name="${typeIcon}" class="notification-type-icon"></mdui-icon>
            <span class="body-large card-title">
              ${item.title}
            </span>
          </div>

          <span class="body-small text-gray-500 card-time"
                style="grid-column:1; grid-row:2;">
            ${new Date(item.t * 1000).toLocaleString()}
          </span>

          <div style="grid-column:2; grid-row:1/3; display:flex; align-items:center;">
            ${btn}
          </div>
        </div>

        <!-- 正文（可选）-->
        ${
            item.message
                ? `<div class="body-medium whitespace-pre-line card-content mt-3">${item.message}</div>`
                : ""
        }

        <!-- 操作按钮（可选）-->
        ${actionButtons}
      </div>
    </mdui-card>
  `);
    }



    /**
     * diff 更新通知卡片
     */
    function syncCards (channel) {

        const $container = $("#container");

        const items = (state.allData[channel] || [])
            .slice()                               // 不破坏原数组
            .sort((a, b) => {
                // ① 先按是否已读分组：未读(false) → 已读(true)
                if (a.is_read !== b.is_read) {
                    return a.is_read ? 1 : -1;         // false 排前
                }
                // ② 同一分组内再按时间：新消息排最前
                return b.t - a.t;
            });
        if (channel!== state.lastChannel){
            $container.html("");
        }

        const shownIds = new Set();


        items.forEach(item => {
            shownIds.add(item.id);
            let $card = $container.find(`mdui-card[data-id="${item.id}"][data-channel="${channel}"]`);

            if (!$card.length) {
                // ---------- 新增 ----------
                $card = createCard(item, channel);
                $container.append($card);
            }
        });

        // ---------- 删除不存在的通知 ----------
        $container.find(`mdui-card[data-channel="${channel}"]`).each(function () {
            if (!shownIds.has($(this).data("id"))) $(this).remove();
        });
    }

    /* -------------------------------------------------------------------------- */
    /*                                   菜单                                    */
    /* -------------------------------------------------------------------------- */

    function setActiveChannel (channel) {
        $("mdui-list-item[data-channel]").each(function () {
            if($(this).data("channel") === channel){
                $(this).attr("active","true");
            }else{
                $(this).removeAttr("active");
            }
        });
    }

    function renderMenu (data) {
        const $menu = $("mdui-list");
        if (!$menu.length) return;      // 无侧栏布局

        Object.entries(data).forEach(([channel, items]) => {
            let $item = $menu.find(`mdui-list-item[data-channel="${channel}"]`);
            const unread = items.filter(i => !i.is_read).length;

            if (!$item.length) {
                $item = $(`
                    <mdui-list-item rounded alignment="center" data-channel="${channel}" style="position:relative" class="pl-2 pr-2">
                        <span>${channel}</span>
                    </mdui-list-item>
                `).appendTo($menu);
            }

            // 更新 / 创建角标
            let $badge = $item.find("mdui-badge");
            if (unread) {
                if ($badge.length) {
                    $badge.text(unread);
                } else {
                    $badge = $(`<mdui-badge style="position:absolute;right:1.8rem;top:50%;transform:translateY(-50%);">${unread}</mdui-badge>`);
                    $item.append($badge);
                }
            } else {
                $badge.remove();
            }
        });

        // 删除已移除的渠道
        $menu.find("mdui-list-item[data-channel]").each(function () {
            const chan = $(this).data("channel");
            if (!data[chan]) $(this).remove();
        });
    }

    /* -------------------------------------------------------------------------- */
    /*                          Web 通知（去重 & 只一次）                         */
    /* -------------------------------------------------------------------------- */

    function sendWebNotifications (data) {
        if (Notification.permission !== "granted") return;

        Object.entries(data).forEach(([channel, items]) => {
            items.filter(i => !i.is_read).forEach(item => {
                const key = `${channel}:${item.id}`;
                if (state.notifiedIds.has(key)) return;
                state.notifiedIds.add(key);

                new Notification(`${channel} - ${item.title}`, {
                    body : item.message || "(无内容)",
                    icon: "/static/icons/android-chrome-192x192.png"
                });
            });
        });
    }

    /* -------------------------------------------------------------------------- */
    /*                                事件绑定                                   */
    /* -------------------------------------------------------------------------- */

    // 切换渠道
    $(document).on("click", "mdui-list-item", (event) => {
        let targetElem = event.target;
        if (targetElem.tagName !== "MDUI-LIST-ITEM") {
            targetElem = targetElem.parentElement;
        }
        const channel = $(targetElem).data("channel");
        if (channel && channel !== state.currentChannel) {
            state.lastChanel = state.currentChannel;
            state.currentChannel = channel;
            setActiveChannel(channel);
            syncCards(channel);
        }
    });

    // 抽屉开关
    $(document).on("click", "#navigation-drawer-switch", () => {
        document.querySelector(".navigation-drawer").open ^= true;
    });

    // 标记已读
    /* ====================== 辅助：刷新指定渠道的角标 ====================== */
    function refreshBadge(channel) {
        const $item  = $(`mdui-list-item[data-channel="${channel}"]`);
        if (!$item.length) return;

        // 重新统计未读数
        const unread = (state.allData[channel] || []).filter(i => !i.is_read).length;

        let $badge = $item.find("mdui-badge");
        if (unread) {
            // 有角标 → 更新；无角标 → 创建
            if ($badge.length) {
                $badge.text(unread);
            } else {
                $badge = $(`<mdui-badge style="position:absolute;right:1.8rem;top:50%;transform:translateY(-50%);">${unread}</mdui-badge>`);
                $item.append($badge);
            }
        } else {
            // 已全部读完 → 隐藏角标
            $badge.remove();
        }
    }

    /* ====================== 标记已读 ====================== */
    $(document).on("click", ".mark-read", function () {
        if (!token) return;                    // token 未就绪
        const $btn   = $(this);
        if ($btn.prop("disabled")) return;     // 避免重复点击

        const $card  = $btn.closest("mdui-card");
        const id     = $card.data("id");
        const channel = $card.data("channel");



        // 后端同步
        $.request.get(`/read/${channel}/${id}/${token}`, {}, () => {
            // 先 Optimistic UI：立刻变灰 & 按钮替换
            $card.addClass("opacity-60");
            $btn.replaceWith('<mdui-icon name="done_all" class="ml-auto text-disabled" title="已读"></mdui-icon>');

            // 更新本地状态，减少一次遍历
            const notice = (state.allData[channel] || []).find(n => n.id === id);
            if (notice) notice.is_read = true;

            // **✦ 新增：立即刷新角标 ✦**
            refreshBadge(channel);
        });
    });


    /* -------------------------------------------------------------------------- */
    /*                               数据增量合并                                */
    /* -------------------------------------------------------------------------- */

    function mergeIncrementalData (incremental) {
        Object.entries(incremental).forEach(([channel, items]) => {
            const existing = state.allData[channel] || [];
            const ids = new Set(existing.map(i => i.id));
            const merged = [...existing];

            items.forEach(item => {
                if (!ids.has(item.id)) {
                    merged.push(item);
                    ids.add(item.id);
                }
            });
            state.allData[channel] = merged;
        });
    }

    /* -------------------------------------------------------------------------- */
    /*                           主入口：菜单 + 通知体                             */
    /* -------------------------------------------------------------------------- */

    function buildUI (data) {
        if (!state.currentChannel || !data[state.currentChannel]) {
            state.currentChannel = Object.keys(data)[0] || "";
        }
        renderMenu(data);
        setActiveChannel(state.currentChannel);
        syncCards(state.currentChannel);
        sendWebNotifications(data);
    }

    /* -------------------------------------------------------------------------- */
    /*                               轮询 & 初始化                                */
    /* -------------------------------------------------------------------------- */

    function fetchSubUri (cb) {
        $.request.get("/sub/get", {}, resp => {
            token = resp.data;
            uri = `/subscribe/${token}`;

            // token 到手，解锁所有未读按钮
            $(".mark-read").prop("disabled", false);
            cb(uri);
        });
    }

    function pollNotifications () {
        const doPoll = u => {
            let t =  sinceTs;
            $.request.get(u, { since_ts: t }, resp => {
                // 只有在数据不为空时才更新时间戳
                if (resp.data && Object.keys(resp.data).length > 0) {
                    sinceTs = Math.floor(Date.now() / 1000) - 10;
                }
                mergeIncrementalData(resp.data);
                buildUI(state.allData);
                setTimeout(() => doPoll(u), 5000);
            }, err => {
                console.error("poll error", err);
                // 失败重试（10s）
                setTimeout(() => doPoll(u), 10000);
            });
        };
        uri ? doPoll(uri) : fetchSubUri(u => doPoll(u));
    }

    document.addEventListener("DOMContentLoaded", () => {
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission().finally(pollNotifications);
        } else {
            pollNotifications();
        }
    });
});