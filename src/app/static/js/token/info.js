window.pageLoadFiles = [
    'js/libs/highlight.min.js',
    'js/libs/highlight-github.min.css'
];

window.pageOnLoad = function () {
    const ELS = {
        base: document.getElementById('pubBaseUrl'),
        channel: document.getElementById('pubChannelSelect'),
        token: document.getElementById('pubToken'),
        title: document.getElementById('pubTitle'),
        priority: document.getElementById('pubPriority'),
        actions: document.getElementById('pubActions'),
        message: document.getElementById('pubMessage'),
        tabs: document.getElementById('snippetTabs'),
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

    /** 与 PHP 发布接口实际读取的头一致（含 Content-Type） */
    function collectHeaders(st) {
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

    function readState() {
        return {
            url: fullUrl(),
            token: String(ELS.token.value || ''),
            title: String(ELS.title.value || ''),
            priority: String(ELS.priority.value || 'info'),
            actions: String(ELS.actions.value || ''),
            message: String(ELS.message.value || ''),
        };
    }

    function bashSQ(s) {
        return `'${String(s).replace(/'/g, `'\\''`)}'`;
    }


    function snipCurl(st) {
        if (!st.url) return '# 请先填写发布根 URL 并选择渠道';
        const tag = `EOF_${Math.random().toString(36).slice(2, 10)}`;
        const hs = collectHeaders(st)
            .map(([k, v]) => `  -H ${bashSQ(`${k}: ${v}`)}`)
            .join(' \\\n');
        return `curl -X POST ${bashSQ(st.url)} \\
${hs} \\
  --data-binary @- <<'${tag}'
${st.message}
${tag}`;
    }



    function snipHttp(st) {
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
            ...collectHeaders(st).map(([k, v]) => `${k}: ${v}`),
            `Content-Length: ${len}`,
            '',
            body,
        ];
        return lines.join('\r\n');
    }

    function snipJs(st) {
        if (!st.url) return '// 请先填写发布根 URL 并选择渠道';
        const headers = collectHeaders(st).reduce((o, [k, v]) => {
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

    function snipGo(st) {
        if (!st.url) return '// 请先填写发布根 URL 并选择渠道';
        const headers = collectHeaders(st);
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

    function snipPy(st) {
        if (!st.url) return '# 请先填写发布根 URL 并选择渠道';
        const lines = collectHeaders(st).map(([k, v]) => `    ${JSON.stringify(k)}: ${JSON.stringify(v)},`);
        return `import requests

url = ${JSON.stringify(st.url)}
headers = {
${lines.join('\n')}
}
data = ${JSON.stringify(st.message)}.encode("utf-8")

r = requests.post(url, headers=headers, data=data)
print(r.status_code, r.text)`;
    }

    function snipPhp(st) {
        if (!st.url) return '// 请先填写发布根 URL 并选择渠道';
        const hdr = collectHeaders(st).map(([k, v]) => `${k}: ${v}`).join('\r\n');
        return `<?php
file_get_contents(${JSON.stringify(st.url)}, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => ${JSON.stringify(hdr)},
        'content' => ${JSON.stringify(st.message)},
    ],
]));`;
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
