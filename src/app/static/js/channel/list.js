window.pageLoadFiles = [
    'DataTable',
    "Form",
    "DialogForm",
];
window.pageOnLoad = function (loading) {
    function initDataBase() {
        let database = new DataTable("#dataTable");
        database.load({
            uri: "/channel/list",
            columns: [
                {
                    field: "name",
                    name: "显示名称",
                    align: "center",
                    width: 140,
                },
                {
                    field: "short_name",
                    name: "短标识",
                    align: "center",
                    width: 140,
                },
                {
                    field: "agent_id",
                    name: "AgentId",
                    align: "center",
                    width: 120,
                },
                {
                    field: "id",
                    name: "操作",
                    align: "center",
                    width: 280,
                    fixed: "right",
                    formatter: function (value, row, index) {
                        return `
<mdui-button-icon data-index="${index}" icon="science" class="action-test" title="测试发布"></mdui-button-icon>
<mdui-dropdown class="action-copy-dd" data-index="${index}" placement="bottom-end">
<mdui-button-icon slot="trigger" icon="content_copy" title="复制 Webhook URL"></mdui-button-icon>
<mdui-menu>
<mdui-menu-item value="dingding">钉钉 webhook</mdui-menu-item>
<mdui-menu-item value="feishu">飞书 webhook</mdui-menu-item>
<mdui-menu-item value="wechat">企业微信 webhook</mdui-menu-item>
<mdui-menu-item value="form">通用 Form 请求</mdui-menu-item>
<mdui-menu-item value="json">JSON 模式</mdui-menu-item>
</mdui-menu>
</mdui-dropdown>
<mdui-button-icon data-index="${index}" icon="edit" class="action-editor" title="编辑"></mdui-button-icon>
<mdui-button-icon data-index="${index}" icon="delete" class="action-delete" title="删除"></mdui-button-icon>`;
                    },
                },
            ],
            mobile: true,
            lineHeight: "auto",
            height: "auto",
            events: {
                onRowClick: function (row, rowIndex) {
                },
                onCellClick: function (row, rowIndex, colIndex, colName) {

                },
                onPaged: function (page, pageSize) {

                },
            },
            empty_msg: "无数据",
            page: true,
            selectable: false
        });
        return database;
    }

    let database = initDataBase();

    /**
     * POST 发布地址，查询串含 type、authorization（与 {@see Main::publish} 一致）。
     * @param {"dingding"|"feishu"|"wechat"|"form"|"json"} webhookType
     * @param {string} shortName
     * @param {string} authorization
     */
    function buildPublishWebhookUrl(webhookType, shortName, authorization) {
        const base = `${location.origin}/${encodeURIComponent(shortName)}`;
        const q = new URLSearchParams();
        q.set("type", webhookType);
        q.set("authorization", authorization);
        return `${base}?${q.toString()}`;
    }


    let dialog = document.querySelector("#channelDialog");
    let testDialog = document.querySelector("#testDialog");
    $("#dataTable")
        .on("click", ".action-copy-dd mdui-menu-item", function () {
            const value = this.getAttribute("value");
            if (!value) {
                return;
            }
            const index = $(this).closest(".action-copy-dd").data("index");
            const row = database.getRow(index);
            if (!row || !row.short_name) {
                $.toaster.error("无法获取频道信息");
                return;
            }
            $.request.get("/token/get", {}, (authResp) => {
                const auth = authResp.data;
                if (!auth) {
                    $.toaster.error("无法获取授权 Token");
                    return;
                }
                $.copy(buildPublishWebhookUrl(value, row.short_name, auth));
                $.toaster.success("已复制URL");
            });
        })
        .on('click', '.action-delete', function () {
            let row = database.getRow($(this).data("index"));
            $.request.postForm("/channel/del", {
                id: row.id
            }, (data) => {
                $.toaster.success("删除成功");
                database.reload({}, true);
            });

        }).on('click', '.action-editor', function () {
        let row = database.getRow($(this).data("index"));
        dialog.open();
        dialog.setValue(row);
    }).on('click', '.action-test', function () {
        let row = database.getRow($(this).data("index"));

        testDialog.open();
        testDialog.setValue({
            url: `${location.origin}/${encodeURIComponent(row.short_name)}`,
            title: "测试通知",
            message: "这是一条测试消息",
            priority: "info",
            actionLeftText: "",
            actionLeftUrl: "",
            actionRightText: "",
            actionRightUrl: ""
        });
    });
    $("#refresh").on("click", () => {
        database.reload({}, true);
    });

    $("#addApp").on("click", () => {
        dialog.open(true);
    });

    dialog.submit("/channel/edit", (data, response) => {
        database.reload({}, true);
    });

    testDialog.submit(null, (data) => {
        const parts = [];
        if (data.actionLeftText && data.actionLeftUrl) {
            parts.push(`${data.actionLeftText},${data.actionLeftUrl}`);
        }
        if (data.actionRightText && data.actionRightUrl) {
            parts.push(`${data.actionRightText},${data.actionRightUrl}`);
        }
        const xActions = parts.join(";");

        $.request.get("/token/get", {}, (authResp) => {
            const auth = authResp.data;
            $.request.setHeaders({
                'Authorization': auth,
                'X-Title': encodeURIComponent(data.title),
                'X-Priority': data.priority || 'info',
                'X-Actions': encodeURIComponent(xActions),
            });
            $.request.postForm(data.url, data.message, (response) => {
                if(response.code !== 200){
                    $.toaster.error(response.msg);
                }else{
                    $.toaster.success("测试通知发送成功");
                    testDialog.close();
                }

            });
        });
    });
    window.pageOnUnLoad = function () {
        database.destroy();
    };
    return false
};
