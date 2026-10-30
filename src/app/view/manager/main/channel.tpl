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
    <div  class="row col-space16">
        <div class="col-xs12 title-large center-vertical mb-4">
           <mdui-icon name="notifications" class="refresh mr-2"></mdui-icon>
            <span > 通知渠道</span>
        </div>
        <div class="col-xs12 d-flex mb-2">
            <mdui-button icon="add" id="addApp">创建渠道</mdui-button>
            <div style="flex-grow: 1"></div>
            <mdui-button-icon icon="refresh" id="refresh"></mdui-button-icon>

        </div>
        <div class="col-xs12">

            <!-- 数据表格卡片 -->
            <div id="dataTable" class="table-card mt-2 w-100" ></div>
        </div>
    </div>

    <mdui-dialog-form label="渠道编辑" id="channelDialog">
        <form class="row col-space16 " >
            <mdui-text-field name="id" type="hidden"></mdui-text-field>
            <input type="hidden" name="logo" id="channelLogo">
            <div class="col-md12">
                <mdui-text-field label="显示名称" name="name" variant="outlined" required></mdui-text-field>
            </div>
            <div class="col-md12">
                <mdui-text-field label="URL 短标识" name="short_name" variant="outlined" required></mdui-text-field>
            </div>
            <div class="col-md12 d-flex align-center" style="gap:12px">
                <img id="channelLogoPreview" alt="" width="64" height="64" style="border-radius:12px;object-fit:cover;background:#eee;display:none">
                <div style="flex:1">
                    <input type="file" id="channelLogoFile" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" style="width:100%">
                    <div class="body-small" style="opacity:.7;margin-top:4px">应用图标，同步到企业微信（建议方形，&lt;2MB）</div>
                </div>
            </div>
            <div class="col-md6">
                <mdui-text-field
                        label="企业微信 AgentId"
                        name="agent_id"
                        variant="outlined"
                        readonly
                        helper="创建后点「同步」自动拉取，无需手填"
                ></mdui-text-field>
            </div>
            <div class="col-md6">
                <mdui-text-field
                        label="应用 Secret"
                        name="secret"
                        type="password"
                        toggle-password
                        variant="outlined"
                        helper="同步后到企微客户端复制填入"
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

    <mdui-dialog id="wecomQrDialog" headline="扫码登录企业微信管理后台" close-on-overlay-click close-on-esc>
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;text-align:center">
            <img id="wecomQr" alt="" style="width:200px;height:200px;display:none">
            <div id="wecomQrStatus" class="body-medium">请用企业微信扫码并确认登录</div>
            <div id="wecomCaptchaBox" style="display:none;width:100%;max-width:280px;text-align:left">
                <mdui-text-field id="wecomCaptcha" label="短信验证码" variant="outlined" clearable></mdui-text-field>
            </div>
        </div>
        <mdui-button slot="action" variant="text" id="wecomQrClose">关闭</mdui-button>
        <mdui-button slot="action" variant="tonal" id="wecomCaptchaSubmit" style="display:none">提交验证码</mdui-button>
    </mdui-dialog>
</div>

<script id="script" src="/static/js/channel/list.js?v={$__v}"></script>


