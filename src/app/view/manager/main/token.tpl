<title id="title">订阅通知 - {$title}</title>
<link rel="stylesheet" href="/static/js/libs/highlight-github.min.css?v={$__v}"/>
<script src="/static/js/libs/highlight.min.js?v={$__v}"></script>
<style id="style">

    .token-auth-block {
        padding-bottom: 1.25rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid rgba(var(--mdui-color-outline-variant), 0.6);
    }

    .token-auth-block .token-auth-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
    }

    .token-auth-block mdui-text-field {
        flex: 1 1 280px;
        min-width: 220px;
    }

    .token-demo-block .token-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
    }

    .token-demo-block mdui-text-field,
    .token-demo-block mdui-select {
        flex: 1 1 200px;
        min-width: 160px;
    }

    .token-demo-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-top: 12px;
    }

    .token-api-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
        margin: 12px 0 16px;
    }

    .token-api-table th,
    .token-api-table td {
        border: 1px solid rgba(var(--mdui-color-outline-variant), 0.5);
        padding: 8px 10px;
        text-align: left;
        vertical-align: top;
    }

    .token-api-table th {
        background: rgba(var(--mdui-color-surface-container), 0.6);
        font-weight: 600;
        white-space: nowrap;
    }

    .token-api-table code {
        font-size: 0.8125rem;
    }

    .token-tabs {
        margin-top: 12px;
    }

    .token-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 6px;
    }

    .token-pre {
        margin: 0;
        padding: 0;
        border-radius: 12px;
        overflow: auto;
        max-height: min(420px, 55vh);
        background: rgba(var(--mdui-color-surface-container-high), 1);
        border: 1px solid rgba(var(--mdui-color-outline-variant), 0.35);
    }

    .token-pre code {
        display: block;
        padding: 12px 14px;
        font-size: 13px;
        line-height: 1.45;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    .token-pre code.hljs {
        background: transparent;
    }

</style>

<div id="container" class="container">
    <div class="row col-space16">
        <div class="col-xs12 title-large center-vertical mb-4">
            <mdui-icon name="rss_feed" class="refresh mr-2"></mdui-icon>
            <span>订阅通知</span>
        </div>

        <div class="col-xs12 pt-4">

            <section class="token-auth-block" aria-labelledby="token-auth-heading">
                <div id="token-auth-heading" class="title-medium mb-2">Authorization 令牌</div>
                <div class="token-auth-row">
                    <mdui-text-field id="pubToken" label="令牌" readonly></mdui-text-field>
                    <mdui-button id="copyTokenBtn" variant="outlined" icon="key">复制令牌</mdui-button>
                    <mdui-button id="resetBtn" variant="tonal" icon="lock_reset">重置令牌</mdui-button>
                </div>
            </section>

            <section class="token-demo-block">
                <div class="title-medium mb-2">发布接口与示例</div>
                <p class="body-small text-on-surface-variant mb-2">
                    <code>POST /{渠道短标识}</code>，正文为 <strong>纯文本</strong>；
                </p>


                <div class="mb-2 row col-space16">
                    <div class="col-sm12 col-xs6 col-md4 ">
                        <mdui-text-field id="pubBaseUrl" label="发布根 URL" readonly ></mdui-text-field>
                    </div>
                    <div class="col-sm12 col-xs6 col-md4 ">
                        <mdui-select id="pubChannelSelect" label="渠道"  ></mdui-select>
                    </div>
                    <div class="col-sm12 col-xs6 col-md4 ">
                        <mdui-select id="pubPriority" label="X-Priority"  value="info">
                            <mdui-menu-item value="info">info（信息）</mdui-menu-item>
                            <mdui-menu-item value="warning">warning（警告）</mdui-menu-item>
                            <mdui-menu-item value="error">error（错误）</mdui-menu-item>
                            <mdui-menu-item value="success">success（成功）</mdui-menu-item>
                        </mdui-select>
                    </div>
                    <div class="col-sm12 col-xs6 col-md4 ">
                        <mdui-text-field id="pubTitle" label="X-Title" value="通知标题"></mdui-text-field>
                    </div>
                    <div class="col-sm12 col-xs6 col-md4 ">
                        <mdui-text-field id="pubActions" label="X-Actions" rows="2"  value="文档,https://example.com;工单,https://example.com""></mdui-text-field>
                    </div>
                    <div class="col-sm12 col-xs6 col-md4 ">
                        <mdui-text-field id="pubMessage" label="正文（body）" rows="2" value="Backup successful 😀"></mdui-text-field>
                    </div>





                </div>


                <mdui-tabs id="snippetTabs" class="token-tabs" variant="secondary" full-width value="curl">
                    <mdui-tab value="curl">cURL</mdui-tab>

                    <mdui-tab value="http">HTTP</mdui-tab>
                    <mdui-tab value="js">JavaScript</mdui-tab>
                    <mdui-tab value="go">Go</mdui-tab>
                    <mdui-tab value="py">Python</mdui-tab>
                    <mdui-tab value="php">PHP</mdui-tab>

                    <mdui-tab-panel slot="panel" value="curl">
                        <div class="token-panel-head">
                            <span class="body-small text-on-surface-variant">含全部请求头；正文 heredoc</span>
                            <mdui-button-icon icon="content_copy" data-copy-for="curl" class="token-copy-snippet" title="复制"></mdui-button-icon>
                        </div>
                        <pre class="token-pre"><code id="code-curl" class="language-bash"></code></pre>
                    </mdui-tab-panel>


                    <mdui-tab-panel slot="panel" value="http">
                        <div class="token-panel-head">
                            <span class="body-small text-on-surface-variant">HTTP/1.1 原始报文</span>
                            <mdui-button-icon icon="content_copy" data-copy-for="http" class="token-copy-snippet" title="复制"></mdui-button-icon>
                        </div>
                        <pre class="token-pre"><code id="code-http" class="language-ini"></code></pre>
                    </mdui-tab-panel>

                    <mdui-tab-panel slot="panel" value="js">
                        <div class="token-panel-head">
                            <span class="body-small text-on-surface-variant">fetch</span>
                            <mdui-button-icon icon="content_copy" data-copy-for="js" class="token-copy-snippet" title="复制"></mdui-button-icon>
                        </div>
                        <pre class="token-pre"><code id="code-js" class="language-javascript"></code></pre>
                    </mdui-tab-panel>

                    <mdui-tab-panel slot="panel" value="go">
                        <div class="token-panel-head">
                            <span class="body-small text-on-surface-variant">net/http</span>
                            <mdui-button-icon icon="content_copy" data-copy-for="go" class="token-copy-snippet" title="复制"></mdui-button-icon>
                        </div>
                        <pre class="token-pre"><code id="code-go" class="language-go"></code></pre>
                    </mdui-tab-panel>

                    <mdui-tab-panel slot="panel" value="py">
                        <div class="token-panel-head">
                            <span class="body-small text-on-surface-variant">requests</span>
                            <mdui-button-icon icon="content_copy" data-copy-for="py" class="token-copy-snippet" title="复制"></mdui-button-icon>
                        </div>
                        <pre class="token-pre"><code id="code-py" class="language-python"></code></pre>
                    </mdui-tab-panel>

                    <mdui-tab-panel slot="panel" value="php">
                        <div class="token-panel-head">
                            <span class="body-small text-on-surface-variant">file_get_contents</span>
                            <mdui-button-icon icon="content_copy" data-copy-for="php" class="token-copy-snippet" title="复制"></mdui-button-icon>
                        </div>
                        <pre class="token-pre"><code id="code-php" class="language-php"></code></pre>
                    </mdui-tab-panel>
                </mdui-tabs>
            </section>
        </div>
    </div>
</div>

<script id="script" src="/static/js/token/info.js?v={$__v}"></script>
