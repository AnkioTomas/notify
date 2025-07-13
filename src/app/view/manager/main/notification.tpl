<title id="title">通知渠道 - {$title}</title>
<style id="style">


    .table-card {
        box-sizing: border-box;
    }

    mdui-card{
        width: 100%;
    }
    .copyable {
        position: relative;
        display: inline-block;          /* 让伪元素可绝对定位 */
        cursor: pointer;
        user-select: none;              /* 可选：防止选中文本时闪烁 */
        height: 100%;
    }

    /* 半透明遮罩 */
    .copyable::before {
        content: '';
        position: absolute;
        inset: 0;                       /* 铺满父容器 */
        background: rgba(0, 0, 0, 0.1);
        opacity: 0;
        transition: opacity .2s ease;
        pointer-events: none;           /* 避免拦截点击 */
    }


    /* 悬浮/激活时显示 */
    .copyable:hover::before,
    .copyable:hover::after,
    .copyable:active::before,
    .copyable:active::after {
        opacity: 1;
    }

</style>

<div id="container" class="container">
    <div class="row">
        <div class="col-xs12 title-large center-vertical mb-4">
           <mdui-icon name="apps" class="refresh mr-2"></mdui-icon>
            <span > 通知渠道</span>
        </div>
        <div class="col-xs12">
            <mdui-card class="p-4" style="display:flex;align-items:center">
                <mdui-button icon="add" id="addApp">创建渠道</mdui-button>
                <div style="flex-grow: 1"></div>
                <mdui-button-icon icon="refresh" id="refresh"></mdui-button-icon>
            </mdui-card>

            <!-- 数据表格卡片 -->
            <div id="dataTable" class="table-card mt-2" style="width: 100%;min-height: 10rem"></div>
        </div>
    </div>

    <mdui-dialog-form title="渠道编辑">
        <form class="row col-space16 " >
            <mdui-text-field name="id" type="hidden"></mdui-text-field>
            <div class="col-md12">
                <mdui-text-field label="渠道名称" name="name" variant="outlined" required></mdui-text-field>
            </div>
        </form>
    </mdui-dialog-form >


</div>

<script id="script" src="/static/js/app/list.js"></script>



