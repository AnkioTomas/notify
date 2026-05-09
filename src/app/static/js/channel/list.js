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
                    width: 200,
                    fixed: "right",
                    formatter: function (value, row, index) {
                        return `
<mdui-button-icon data-index="${index}" icon="science" class="action-test" title="测试发布"></mdui-button-icon>
<mdui-button-icon data-index="${index}" icon="content_copy" class="action-copy" title="复制发布地址"></mdui-button-icon>
<mdui-button-icon data-index="${index}" icon="callback" class="action-hook" title="复制 Hook 地址"></mdui-button-icon>
<mdui-button-icon data-index="${index}" icon="edit" class="action-editor" title="编辑"></mdui-button-icon>
<mdui-button-icon  data-index="${index}" icon="delete" class="action-delete" title="删除"></mdui-button-icon>`;
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


    let dialog = document.querySelector("#channelDialog");
    let testDialog = document.querySelector("#testDialog");
    $("#dataTable")
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
    }).on('click', '.action-copy', function () {
        let row = database.getRow($(this).data("index"));
        let uri = location.origin + "/" + encodeURIComponent(row.short_name);
        $.copy(uri);
        $.toaster.success("已复制发布地址");
    }).on('click', '.action-hook', function () {
        let row = database.getRow($(this).data("index"));
        let uri = location.origin + "/hook/" + encodeURIComponent(row.short_name);
        $.copy(uri);
        $.toaster.success("已复制 Hook 地址");
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
                $.toaster.success("测试通知发送成功");
            });
        });
    });
    window.pageOnUnLoad = function () {
        database.destroy();
    };
    return false
};
