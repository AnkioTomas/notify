<title id="title">通知渠道 - {$title}</title>
<style id="style">


    .table-card {
        box-sizing: border-box;
    }

    mdui-card{
        width: 100%;
    }

</style>

<div id="container" class="container">
    <div class="row">
        <div class="col-xs12 title-large center-vertical mb-4">
           <mdui-icon name="notifications" class="refresh mr-2"></mdui-icon>
            <span > 通知渠道</span>
        </div>
        <div class="col-xs12">
            <mdui-card class="p-4 w-100 items-center "  style="display: flex">
                <mdui-button icon="add" id="addApp">创建渠道</mdui-button>
                <div style="flex-grow: 1"></div>
                <mdui-button-icon icon="refresh" id="refresh"></mdui-button-icon>
            </mdui-card>

            <!-- 数据表格卡片 -->
            <div id="dataTable" class="table-card mt-2 w-100" ></div>
        </div>
    </div>

    <mdui-dialog-form label="渠道编辑" id="channelDialog">
        <form class="row col-space16 " >
            <mdui-text-field name="id" type="hidden"></mdui-text-field>
            <div class="col-md12">
                <mdui-text-field label="显示名称" name="name" variant="outlined" required></mdui-text-field>
            </div>
            <div class="col-md12">
                <mdui-text-field label="URL 短标识" name="short_name" variant="outlined" required></mdui-text-field>
            </div>
            <div class="col-md6">
                <mdui-text-field label="企业微信 AgentId" name="agent_id" variant="outlined"></mdui-text-field>
            </div>
            <div class="col-md6">
                <mdui-text-field
                        label="应用 Secret"
                        name="secret"
                        type="password"
                        toggle-password
                        variant="outlined"
                        helper="该应用专属，用于换取 access_token"
                ></mdui-text-field>
            </div>
        </form>
    </mdui-dialog-form >
    <mdui-dialog-form label="测试通知" id="testDialog">
        <form class="row col-space16">
            <div class="col-md12">
            <mdui-text-field name="url" label="发布 URL" variant="outlined" readonly></mdui-text-field>
            </div>
            <div class="col-md12">
                <mdui-text-field label="消息标题" name="title" variant="outlined" required></mdui-text-field>
            </div>
            <div class="col-md12">
                <mdui-text-field label="消息内容" name="message" variant="outlined" rows="3" required></mdui-text-field>
            </div>
            <div class="col-md6">
                <mdui-select label="优先级" name="priority" variant="outlined">
                    <mdui-menu-item value="info">信息</mdui-menu-item>
                    <mdui-menu-item value="warning">警告</mdui-menu-item>
                    <mdui-menu-item value="error">错误</mdui-menu-item>
                    <mdui-menu-item value="success">成功</mdui-menu-item>
                </mdui-select>
            </div>

            <div class="col-md6">
                <mdui-text-field label="左侧按钮文本" name="actionLeftText" variant="outlined"></mdui-text-field>
            </div>
            <div class="col-md6">
                <mdui-text-field label="左侧按钮URL" name="actionLeftUrl" variant="outlined"></mdui-text-field>
            </div>
            <div class="col-md6">
                <mdui-text-field label="右侧按钮文本" name="actionRightText" variant="outlined"></mdui-text-field>
            </div>
            <div class="col-md6">
                <mdui-text-field label="右侧按钮URL" name="actionRightUrl" variant="outlined"></mdui-text-field>
            </div>
        </form>
    </mdui-dialog-form>
</div>

<script id="script" src="/static/js/channel/list.js?v={$__v}"></script>


