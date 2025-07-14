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

    function copyToClipboard(text) {
        if (navigator.clipboard?.writeText) {
            try {
                navigator.clipboard.writeText(text);
            } catch (_) {
            }
        }
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = 0;
        document.body.appendChild(ta);
        ta.select();
        try {
            return document.execCommand('copy');
        } finally {
            document.body.removeChild(ta);
        }
    }

    let dialog = document.querySelector("mdui-dialog-form");
    $("#dataTable")
        .on('click', '.action-delete', function () {
            let row = database.getRow($(this).data("index"));
            $.request.postForm("/channel/del", {
                id: row.id
            }, (data) => {
                $.toaster.success("删除成功");
                database.reload();
            });

        }).on('click', '.action-editor', function () {
        let row = database.getRow($(this).data("index"));
        dialog.open();
        dialog.setValue(row);
    }).on('click', '.action-copy', function () {
        let row = database.getRow($(this).data("index"));
        let uri = location.origin+"/publish/"+row.channel+"_"+row.token;
        copyToClipboard(uri);
        $.toaster.success("已复制链接");

    });
    $("#refresh").on("click", () => {
        database.reload();
    });

    $("#addApp").on("click", () => {
        dialog.open();
    });

    dialog.submit("/channel/edit", (data, response) => {
        database.reload();
    })
    window.pageOnUnLoad = function () {
        database.destroy();
    };
    return false
};