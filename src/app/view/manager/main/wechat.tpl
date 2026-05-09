<title id="title">企业微信 - {$title}</title>
<style id="style">

    mdui-card {
        width: 100%;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .wechat-form {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    mdui-button {
        --mdui-button-radius: 16px;
    }

</style>

<div id="container" class="container">
    <div class="row">
        <div class="col-xs12 title-large center-vertical mb-4">
            <mdui-icon name="forum" class="refresh mr-2"></mdui-icon>
            <span>企业微信</span>
        </div>
        <div class="col-xs12">
            <mdui-card class="p-4">
                <form id="wechatForm" class="wechat-form" autocomplete="off">
                    <mdui-text-field
                            name="corpid"
                            label="企业 ID（CorpID）"
                            variant="outlined"
                            clearable
                            required
                    ></mdui-text-field>

                    <mdui-text-field
                            name="secret"
                            label="应用 Secret"
                            type="password"
                            toggle-password
                            variant="outlined"
                            clearable
                            required
                    ></mdui-text-field>

                    <mdui-text-field
                            name="to_user"
                            label="接收人 UserID（多个用 | 分隔，填 @all 推送给全员）"
                            variant="outlined"
                            clearable
                            required
                    ></mdui-text-field>

                    <div>
                        <mdui-button type="submit" icon="save">保存</mdui-button>
                    </div>
                </form>
            </mdui-card>
        </div>
    </div>
</div>

<script id="script" src="/static/js/wechat/info.js?v={$__v}"></script>
