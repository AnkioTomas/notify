window.pageLoadFiles = [
    'js/libs/highlight.min.js',
    'js/libs/highlight-github.min.css'
];

window.pageOnLoad = function () {
    const MODE_DESC = {
        ntfy: '默认：请求头传递 X-Title、X-Priority、X-Actions；正文为纯文本（与 ntfy 兼容）。Authorization 放在请求头。',
        dingding: '钉钉机器人 JSON：<code>msgtype</code> + <code>text.content</code>。URL 查询参数 <code>type=dingding</code> 与 <code>authorization</code>。',
        feishu: '飞书：<code>msg_type</code> + <code>content.text</code>。URL 带 <code>type=feishu</code> 与 <code>authorization</code>。',
        wechat: '企业微信机器人 JSON（与钉钉结构类似）。URL 带 <code>type=wechat</code> 与 <code>authorization</code>。',
        form: '表单：<code>title</code> / <code>message</code> / <code>priority</code>。URL 带 <code>type=form</code> 与 <code>authorization</code>。',
        json: 'JSON 正文：<code>title</code>、<code>message</code>、<code>priority</code> 等。URL 带 <code>type=json</code> 与 <code>authorization</code>。',
    };

    const ELS = {
        base: document.getElementById('pubBaseUrl'),
        channel: document.getElementById('pubChannelSelect'),
        mode: document.getElementById('pubModeSelect'),
        modeDesc: document.getElementById('pubModeDesc'),
        token: document.getElementById('pubToken'),
        title: document.getElementById('pubTitle'),
        priority: document.getElementById('pubPriority'),
        actions: document.getElementById('pubActions'),
        message: document.getElementById('pubMessage'),
        tabs: document.getElementById('snippetTabs'),
        pubActionsWrap: document.getElementById('pubActionsWrap'),
        pubPriorityWrap: document.getElementById('pubPriorityWrap'),
    };

    function normBase(s) {
        return String(s || '').trim().replace(/\/+$/, '');
    }

    function fullUrl() {
        const b = normBase(ELS.base.value);
        const c = String(ELS.channel.value || '').trim().replace(/^\/+/, '');
        if (!b || !c) return '';
        return `${b}/${encodeURIComponent(c)}`;
    }

    /** ntfy：无 type；其它模式：查询串 type + authorization */
    function publishUrl(st) {
        const base = fullUrl();
        if (!base) return '';
        const mode = st.mode || 'ntfy';
        if (mode === 'ntfy') {
            return base;
        }
        const q = new URLSearchParams();
        q.set('type', mode);
        q.set('authorization', st.token || '');
        return `${base}?${q.toString()}`;
    }

    function readState() {
        const mode = String(ELS.mode?.value || 'ntfy');
        const token = String(ELS.token.value || '');
        return {
            mode,
            url: publishUrl({ mode, token }),
            token,
            title: String(ELS.title.value || ''),
            priority: String(ELS.priority.value || 'info'),
            actions: String(ELS.actions.value || ''),
            message: String(ELS.message.value || ''),
        };
    }

    /** 钉钉 / 企微正文：首行可做标题，与后端 splitTitleBody 一致 */
    function webhookPlainContent(st) {
        const t = st.title.trim();
        const m = st.message;
        if (t && m) return `${t}\n${m}`;
        return t || m;
    }

    function dingdingJson(st) {
        return JSON.stringify({
            msgtype: 'text',
            text: { content: webhookPlainContent(st) },
        });
    }

    function feishuJson(st) {
        return JSON.stringify({
            msg_type: 'text',
            content: { text: webhookPlainContent(st) },
        });
    }

    function jsonModeBody(st) {
        return JSON.stringify({
            title: st.title,
            message: st.message,
            priority: st.priority || 'info',
        });
    }

    /** 与 PHP 侧 ntfy 一致（含 Content-Type） */
    function collectHeadersNtfy(st) {
        const h = [
            ['Authorization', st.token],
            ['X-Title', st.title],
            ['X-Priority', st.priority || 'info'],
        ];
        const act = String(st.actions || '').trim();
        if (act) h.push(['X-Actions', act]);
        h.push(['Content-Type', 'text/plain; charset=utf-8']);
        return h;
    }

    function bashSQ(s) {
        return `'${String(s).replace(/'/g, `'\\''`)}'`;
    }

    function updateModeUi() {
        const m = String(ELS.mode?.value || 'ntfy');
        if (ELS.modeDesc) {
            ELS.modeDesc.innerHTML = MODE_DESC[m] || '';
        }
        const ntfy = m === 'ntfy';
        const hidePriority = m === 'dingding' || m === 'feishu' || m === 'wechat';
        if (ELS.pubActionsWrap) ELS.pubActionsWrap.hidden = !ntfy;
        if (ELS.pubPriorityWrap) ELS.pubPriorityWrap.hidden = hidePriority;
    }

    // ---------- ntfy 示例（沿用原逻辑，URL 用 publishUrl） ----------
    function snipCurlNtfy(st) {
        if (!st.url) return '# 请先填写发布根 URL 并选择渠道';
        const tag = `EOF_${Math.random().toString(36).slice(2, 10)}`;
        const hs = collectHeadersNtfy(st)
            .map(([k, v]) => `  -H ${bashSQ(`${k}: ${v}`)}`)
            .join(' \\\n');
        return `curl -X POST ${bashSQ(st.url)} \\
${hs} \\
  --data-binary @- <<'${tag}'
${st.message}
${tag}`;
    }

    function snipHttpNtfy(st) {
        if (!st.url) return '# 请先填写发布根 URL 并选择渠道';
        let u;
        try {
            u = new URL(st.url);
        } catch {
            return '# 发布 URL 格式不正确';
        }
        const path = (u.pathname || '/') + (u.search || '');
        const body = st.message;
        const len = new TextEncoder().encode(body).length;
        const lines = [
            `POST ${path} HTTP/1.1`,
            `Host: ${u.host}`,
            ...collectHeadersNtfy(st).map(([k, v]) => `${k}: ${v}`),
            `Content-Length: ${len}`,
            '',
            body,
        ];
        return lines.join('\r\n');
    }

    function snipJsNtfy(st) {
        if (!st.url) return '// 请先填写发布根 URL 并选择渠道';
        const headers = collectHeadersNtfy(st).reduce((o, [k, v]) => {
            o[k] = v;
            return o;
        }, {});
        const lines = Object.keys(headers).map((k) => `    ${JSON.stringify(k)}: ${JSON.stringify(headers[k])},`);
        return `fetch(${JSON.stringify(st.url)}, {
  method: 'POST',
  headers: {
${lines.join('\n')}
  },
  body: ${JSON.stringify(st.message)},
});`;
    }

    function snipGoNtfy(st) {
        if (!st.url) return '// 请先填写发布根 URL 并选择渠道';
        const headers = collectHeadersNtfy(st);
        const sets = headers.map(([k, v]) => `    req.Header.Set(${JSON.stringify(k)}, ${JSON.stringify(v)})`).join('\n');
        return `package main

import (
    "bytes"
    "fmt"
    "io"
    "net/http"
)

func main() {
    url := ${JSON.stringify(st.url)}
    body := ${JSON.stringify(st.message)}

    req, err := http.NewRequest(http.MethodPost, url, bytes.NewReader([]byte(body)))
    if err != nil {
        panic(err)
    }
${sets}

    resp, err := http.DefaultClient.Do(req)
    if err != nil {
        panic(err)
    }
    defer resp.Body.Close()
    out, _ := io.ReadAll(resp.Body)
    fmt.Println(resp.Status, string(out))
}`;
    }

    function snipPyNtfy(st) {
        if (!st.url) return '# 请先填写发布根 URL 并选择渠道';
        const lines = collectHeadersNtfy(st).map(([k, v]) => `    ${JSON.stringify(k)}: ${JSON.stringify(v)},`);
        return `import requests

url = ${JSON.stringify(st.url)}
headers = {
${lines.join('\n')}
}
data = ${JSON.stringify(st.message)}.encode("utf-8")

r = requests.post(url, headers=headers, data=data)
print(r.status_code, r.text)`;
    }

    function snipPhpNtfy(st) {
        if (!st.url) return '// 请先填写发布根 URL 并选择渠道';
        const hdr = collectHeadersNtfy(st).map(([k, v]) => `${k}: ${v}`).join('\r\n');
        return `<?php
file_get_contents(${JSON.stringify(st.url)}, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => ${JSON.stringify(hdr)},
        'content' => ${JSON.stringify(st.message)},
    ],
]));`;
    }

    // ---------- 其它 type：URL 已含 authorization ----------
    function snipCurlWebhook(st, body, jsonBody) {
        if (!st.url) return '# 请先填写发布根 URL 并选择渠道';
        if (jsonBody) {
            return `curl -X POST ${bashSQ(st.url)} \\
  -H ${bashSQ('Content-Type: application/json; charset=utf-8')} \\
  --data-binary ${bashSQ(body)}`;
        }
        return `curl -X POST ${bashSQ(st.url)} \\
  -H ${bashSQ('Content-Type: application/x-www-form-urlencoded; charset=utf-8')} \\
  --data-urlencode ${bashSQ(`title=${st.title}`)} \\
  --data-urlencode ${bashSQ(`message=${st.message}`)} \\
  --data-urlencode ${bashSQ(`priority=${st.priority || 'info'}`)}`;
    }

    function snipHttpWebhook(st, body, contentType) {
        if (!st.url) return '# 请先填写发布根 URL 并选择渠道';
        let u;
        try {
            u = new URL(st.url);
        } catch {
            return '# 发布 URL 格式不正确';
        }
        const path = (u.pathname || '/') + (u.search || '');
        const len = new TextEncoder().encode(body).length;
        const lines = [
            `POST ${path} HTTP/1.1`,
            `Host: ${u.host}`,
            `Content-Type: ${contentType}`,
            `Content-Length: ${len}`,
            '',
            body,
        ];
        return lines.join('\r\n');
    }

    function snipJsWebhook(st, body, jsonBody) {
        if (!st.url) return '// 请先填写发布根 URL 并选择渠道';
        if (jsonBody) {
            return `fetch(${JSON.stringify(st.url)}, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json; charset=utf-8' },
  body: ${JSON.stringify(body)},
});`;
        }
        return `const p = new URLSearchParams({
  title: ${JSON.stringify(st.title)},
  message: ${JSON.stringify(st.message)},
  priority: ${JSON.stringify(st.priority || 'info')},
});
fetch(${JSON.stringify(st.url)}, {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8' },
  body: p.toString(),
});`;
    }

    function snipGoWebhook(st, bodyStr, jsonBody) {
        if (!st.url) return '// 请先填写发布根 URL 并选择渠道';
        if (jsonBody) {
            return `package main

import (
    "bytes"
    "fmt"
    "io"
    "net/http"
)

func main() {
    url := ${JSON.stringify(st.url)}
    body := []byte(${JSON.stringify(bodyStr)})
    req, err := http.NewRequest(http.MethodPost, url, bytes.NewReader(body))
    if err != nil {
        panic(err)
    }
    req.Header.Set("Content-Type", "application/json; charset=utf-8")
    resp, err := http.DefaultClient.Do(req)
    if err != nil {
        panic(err)
    }
    defer resp.Body.Close()
    out, _ := io.ReadAll(resp.Body)
    fmt.Println(resp.Status, string(out))
}`;
        }
        return `package main

import (
    "fmt"
    "io"
    "net/http"
    "net/url"
    "strings"
)

func main() {
    u := ${JSON.stringify(st.url)}
    form := url.Values{}
    form.Set("title", ${JSON.stringify(st.title)})
    form.Set("message", ${JSON.stringify(st.message)})
    form.Set("priority", ${JSON.stringify(st.priority || 'info')})
    resp, err := http.Post(u, "application/x-www-form-urlencoded", strings.NewReader(form.Encode()))
    if err != nil {
        panic(err)
    }
    defer resp.Body.Close()
    out, _ := io.ReadAll(resp.Body)
    fmt.Println(resp.Status, string(out))
}`;
    }

    function snipPyWebhook(st, body, jsonBody) {
        if (!st.url) return '# 请先填写发布根 URL 并选择渠道';
        if (jsonBody) {
            return `import json
import requests

url = ${JSON.stringify(st.url)}
payload = json.loads(${JSON.stringify(body)})
r = requests.post(url, json=payload)
print(r.status_code, r.text)`;
        }
        return `import requests

url = ${JSON.stringify(st.url)}
r = requests.post(url, data={
    "title": ${JSON.stringify(st.title)},
    "message": ${JSON.stringify(st.message)},
    "priority": ${JSON.stringify(st.priority || 'info')},
})
print(r.status_code, r.text)`;
    }

    function snipPhpWebhook(st, body, jsonBody) {
        if (!st.url) return '// 请先填写发布根 URL 并选择渠道';
        if (jsonBody) {
            return `<?php
file_get_contents(${JSON.stringify(st.url)}, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json; charset=utf-8',
        'content' => ${JSON.stringify(body)},
    ],
]));`;
        }
        return `<?php
$data = http_build_query([
    'title' => ${JSON.stringify(st.title)},
    'message' => ${JSON.stringify(st.message)},
    'priority' => ${JSON.stringify(st.priority || 'info')},
]);
file_get_contents(${JSON.stringify(st.url)}, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $data,
    ],
]));`;
    }

    function snipCurl(st) {
        const m = st.mode || 'ntfy';
        if (m === 'ntfy') return snipCurlNtfy(st);
        if (m === 'form') {
            return snipCurlWebhook(st, '', false);
        }
        let body;
        if (m === 'dingding' || m === 'wechat') body = dingdingJson(st);
        else if (m === 'feishu') body = feishuJson(st);
        else if (m === 'json') body = jsonModeBody(st);
        else return snipCurlNtfy(st);
        return snipCurlWebhook(st, body, true);
    }

    function snipHttp(st) {
        const m = st.mode || 'ntfy';
        if (m === 'ntfy') return snipHttpNtfy(st);
        if (m === 'form') {
            const p = new URLSearchParams({
                title: st.title,
                message: st.message,
                priority: st.priority || 'info',
            });
            return snipHttpWebhook(st, p.toString(), 'application/x-www-form-urlencoded; charset=utf-8');
        }
        let body;
        if (m === 'dingding' || m === 'wechat') body = dingdingJson(st);
        else if (m === 'feishu') body = feishuJson(st);
        else if (m === 'json') body = jsonModeBody(st);
        else return snipHttpNtfy(st);
        return snipHttpWebhook(st, body, 'application/json; charset=utf-8');
    }

    function snipJs(st) {
        const m = st.mode || 'ntfy';
        if (m === 'ntfy') return snipJsNtfy(st);
        if (m === 'form') return snipJsWebhook(st, '', false);
        let body;
        if (m === 'dingding' || m === 'wechat') body = dingdingJson(st);
        else if (m === 'feishu') body = feishuJson(st);
        else if (m === 'json') body = jsonModeBody(st);
        else return snipJsNtfy(st);
        return snipJsWebhook(st, body, true);
    }

    function snipGo(st) {
        const m = st.mode || 'ntfy';
        if (m === 'ntfy') return snipGoNtfy(st);
        if (m === 'form') return snipGoWebhook(st, '', false);
        let body;
        if (m === 'dingding' || m === 'wechat') body = dingdingJson(st);
        else if (m === 'feishu') body = feishuJson(st);
        else if (m === 'json') body = jsonModeBody(st);
        else return snipGoNtfy(st);
        return snipGoWebhook(st, body, true);
    }

    function snipPy(st) {
        const m = st.mode || 'ntfy';
        if (m === 'ntfy') return snipPyNtfy(st);
        if (m === 'form') {
            return snipPyWebhook(st, '', false);
        }
        let body;
        if (m === 'dingding' || m === 'wechat') body = dingdingJson(st);
        else if (m === 'feishu') body = feishuJson(st);
        else if (m === 'json') body = jsonModeBody(st);
        else return snipPyNtfy(st);
        return snipPyWebhook(st, body, true);
    }

    function snipPhp(st) {
        const m = st.mode || 'ntfy';
        if (m === 'ntfy') return snipPhpNtfy(st);
        if (m === 'form') {
            const p = new URLSearchParams({
                title: st.title,
                message: st.message,
                priority: st.priority || 'info',
            });
            return snipPhpWebhook(st, p.toString(), false);
        }
        let body;
        if (m === 'dingding' || m === 'wechat') body = dingdingJson(st);
        else if (m === 'feishu') body = feishuJson(st);
        else if (m === 'json') body = jsonModeBody(st);
        else return snipPhpNtfy(st);
        return snipPhpWebhook(st, body, true);
    }

    const SNIPPETS = {
        curl: snipCurl,
        http: snipHttp,
        js: snipJs,
        go: snipGo,
        py: snipPy,
        php: snipPhp,
    };

    const CODE_IDS = {
        curl: 'code-curl',
        http: 'code-http',
        js: 'code-js',
        go: 'code-go',
        py: 'code-py',
        php: 'code-php',
    };

    const HL_LANG = {
        curl: 'bash',
        http: 'ini',
        js: 'javascript',
        go: 'go',
        py: 'python',
        php: 'php',
    };

    function tryHighlight(el) {
        if (!el || !window.hljs || typeof window.hljs.highlightElement !== 'function') return;
        try {
            window.hljs.highlightElement(el);
        } catch (e) {
            $.logger?.warn?.('hljs', e);
        }
    }

    function paintSnippets() {
        updateModeUi();
        const state = readState();
        Object.keys(SNIPPETS).forEach((key) => {
            const el = document.getElementById(CODE_IDS[key]);
            if (!el) return;
            el.textContent = SNIPPETS[key](state);
            el.removeAttribute('data-highlighted');
            el.className = `language-${HL_LANG[key]}`;
            tryHighlight(el);
        });
    }

    function loadToken() {
        $.request.get('/token/get', {}, (resp) => {
            ELS.token.value = resp && resp.data != null ? String(resp.data) : '';
            paintSnippets();
        });
    }

    function loadChannels() {
        $.request.get('/channel/list', { page: 1, pageSize: 500 }, (resp) => {
            ELS.channel.innerHTML = '';
            const rows = (resp && resp.data) || [];
            if (!rows.length) {
                const mi = document.createElement('mdui-menu-item');
                mi.value = '';
                mi.textContent = '（无渠道，请先在渠道管理创建）';
                mi.setAttribute('disabled', '');
                ELS.channel.appendChild(mi);
                ELS.channel.value = '';
            } else {
                rows.forEach((r) => {
                    const mi = document.createElement('mdui-menu-item');
                    mi.value = r.short_name;
                    mi.textContent = `${r.name || r.short_name}（${r.short_name}）`;
                    ELS.channel.appendChild(mi);
                });
                ELS.channel.value = rows[0].short_name;
            }
            paintSnippets();
        });
    }

    function bindInputs(ids) {
        ids.forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', paintSnippets);
            el.addEventListener('change', paintSnippets);
        });
    }

    ELS.base.value = window.location.origin;
    loadToken();
    loadChannels();

    bindInputs(['pubBaseUrl', 'pubTitle', 'pubActions', 'pubMessage']);
    ELS.channel.addEventListener('change', paintSnippets);
    ELS.priority.addEventListener('change', paintSnippets);
    if (ELS.mode) {
        ELS.mode.addEventListener('change', paintSnippets);
    }

    $('#copyTokenBtn').on('click', () => {
        $.copy(String(ELS.token.value || ''));
        $.toaster.success('已复制令牌');
    });

    $('#resetBtn').on('click', () => {
        $.request.get('/token/reset', {}, () => {
            loadToken();
            $.toaster.success('已重置令牌');
        });
    });

    $('.token-copy-snippet').on('click', function () {
        const key = $(this).data('copy-for');
        const el = document.getElementById(CODE_IDS[key]);
        if (!el) return;
        $.copy(el.textContent || '');
        $.toaster.success('已复制示例');
    });

    ELS.tabs.addEventListener('change', () => {
        const v = ELS.tabs.value;
        const el = document.getElementById(CODE_IDS[v]);
        tryHighlight(el);
    });

    paintSnippets();
    return false;
};
