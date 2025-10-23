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
                    field: "channel",
                    name: "渠道名称",
                    align: "center",
                    width: 120,
                },
                {
                    field: "token",
                    name: "渠道Token",
                    align: "center",
                    width: "auto",
                },


                {
                    field: "id",
                    name: "操作",
                    align: "center",
                    width: 200, //列宽度
                    fixed: "right",
                    formatter: function (value, row, index) {
                        return `
<mdui-button-icon data-index="${index}" icon="science" class="action-test" ></mdui-button-icon>
<mdui-button-icon data-index="${index}" icon="content_copy" class="action-copy" ></mdui-button-icon>
<mdui-button-icon data-index="${index}" icon="edit" class="action-editor" ></mdui-button-icon>
<mdui-button-icon  data-index="${index}" icon="delete" class="action-delete"></mdui-button-icon>`;
                    },
                },
            ],
            mobile: true,
            lineHeight: "auto", //表格行高，默认"auto
            height: "auto", //表格高度
            events: {
                onRowClick: function (row, rowIndex) {
                },
                // row: 当前行数据
                // rowIndex: 当前行索引
                // colIndex: 当前列索引
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
        let uri = location.origin + "/" + encodeURI(row.channel) + "/" + row.token;
        $.copy(uri);
        $.toaster.success("已复制链接");
    }).on('click', '.action-test', function () {
        let row = database.getRow($(this).data("index"));
        
        testDialog.open();
        // 设置默认值和URL
        testDialog.setValue({
            url: `${location.origin}/${encodeURI(row.channel)}/${row.token}`,
            title: "测试通知",
            message: "这是一条测试消息",
            type: "info",
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

    // 测试对话框提交处理
    testDialog.submit(null,(data) => {
        // 使用新的header格式发送测试请求
        const url = data.url;
        const headers = {
            'Title': encodeURIComponent(data.title),
            'Type': encodeURIComponent(data.type),
            'Action-Left-Text': encodeURIComponent(data.actionLeftText),
            'Action-Left-Url': encodeURIComponent(data.actionLeftUrl),
            'Action-Right-Text': encodeURIComponent(data.actionRightText),
            'Action-Right-Url': encodeURIComponent(data.actionRightUrl),
        };
        $.request.setHeaders(headers)
        $.request.postForm(url, data.message, (response) => {
            $.toaster.success("测试通知发送成功");
        });
    });
    window.pageOnUnLoad = function () {
        database.destroy();
    };
    return false
};