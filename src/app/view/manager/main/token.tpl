<title id="title">订阅通知 - {$title}</title>
<style id="style">


    .table-card {
        box-sizing: border-box;
    }

    /* 卡片里的按钮和输入框排版 */
    .subscription-card {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    mdui-card {
        width: 100%;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    /* 输入框全宽 + 椭圆按钮 */
    mdui-text-field {
        flex: 1 1 240px;
    }

    mdui-button {
        --mdui-button-radius: 16px;
    }

</style>

<div id="container" class="container">
    <div class="row">
        <div class="col-xs12 title-large center-vertical mb-4">
           <mdui-icon name="rss_feed" class="refresh mr-2"></mdui-icon>
            <span > 订阅通知</span>
        </div>
        <div class="col-xs12 p-4 subscription-card">
            <!-- 订阅链接卡片 -->
            <mdui-text-field
                    id="linkInput"
                    class="subscription-link"
                    label="订阅链接"
                    value=""
                    readonly
                    suffix-icon="content_copy"
            ></mdui-text-field>

            <!-- 复制按钮 -->
            <mdui-button
                    id="copyBtn"
                    variant="outlined"
                    icon="content_copy"
                    aria-label="复制链接"
            >复制</mdui-button>

            <!-- 重置按钮 -->
            <mdui-button
                    id="resetBtn"
                    variant="tonal"
                    icon="lock_reset"
                    aria-label="重置链接"
            >重置</mdui-button>


        </div>


    </div>

</div>

<script id="script" src="/static/js/token/info.js?v={$__v}"></script>



