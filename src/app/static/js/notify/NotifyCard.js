class NotifyCard extends HTMLElement {
    static get observedAttributes() {
        return ['priority', 'heading', 'channel', 'time', 'markdown', 'back-href'];
    }

    static #priorityMeta = {
        info:    { label: '信息', icon: 'info',         cls: 'primary'   },
        warning: { label: '警告', icon: 'warning',      cls: 'tertiary'  },
        error:   { label: '错误', icon: 'error',        cls: 'error'     },
        success: { label: '成功', icon: 'check_circle', cls: 'secondary' },
    };

    /** 与 #priorityMeta 的 cls（primary…error）对齐，用于重复的 token 样式生成 */
    static #paletteTones = ['primary', 'secondary', 'tertiary', 'error'];

    static #paletteCss() {
        return NotifyCard.#paletteTones
            .map(
                (t) =>
                    `notify-card .text-${t} { color: rgba(var(--mdui-color-${t})) !important; }
notify-card .bg-${t} { background-color: rgba(var(--mdui-color-${t})) !important; }
notify-card .text-on-${t} { color: rgba(var(--mdui-color-on-${t})) !important; }
`
            )
            .join('');
    }

    static #actionToneCss() {
        return NotifyCard.#paletteTones
            .map(
                (t) =>
                    `notify-card .notify-actions--${t} mdui-button::part(button) {
    background-color: rgba(var(--mdui-color-${t})) !important;
    color: rgba(var(--mdui-color-on-${t})) !important;
}
notify-card .notify-actions--${t} mdui-button:focus-visible::part(button) {
    outline: 2px solid rgba(var(--mdui-color-${t}));
    outline-offset: 2px;
}
`
            )
            .join('');
    }

    /** 组件内自洽：utility + 正文 markdown + 全宽（不依赖页面 base.css） */
    static #styleText = `
notify-card {
    display: block;
    flex: 1 1 100%;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
}
notify-card h1 { margin-top: 0; }

notify-card .headline-medium {
    font-size: var(--mdui-typescale-headline-medium-size);
    line-height: var(--mdui-typescale-headline-medium-line-height);
    letter-spacing: var(--mdui-typescale-headline-medium-tracking);
    font-weight: var(--mdui-typescale-headline-medium-weight);
}
notify-card .body-large {
    font-size: var(--mdui-typescale-body-large-size);
    line-height: var(--mdui-typescale-body-large-line-height);
    letter-spacing: var(--mdui-typescale-body-large-tracking);
    font-weight: var(--mdui-typescale-body-large-weight);
}
notify-card .body-small {
    font-size: var(--mdui-typescale-body-small-size);
    line-height: var(--mdui-typescale-body-small-line-height);
    letter-spacing: var(--mdui-typescale-body-small-tracking);
    font-weight: var(--mdui-typescale-body-small-weight);
}

notify-card .font-medium { font-weight: 500 !important; }
notify-card .font-bold { font-weight: 700 !important; }

${NotifyCard.#paletteCss()}

notify-card .text-on-surface { color: rgba(var(--mdui-color-on-surface)) !important; }
notify-card .text-on-surface-variant { color: rgba(var(--mdui-color-on-surface-variant)) !important; }

notify-card .d-flex { display: flex !important; }
notify-card .d-inline-flex { display: inline-flex !important; }
notify-card .flex-wrap { flex-wrap: wrap !important; }
notify-card .items-center { align-items: center !important; }
notify-card .flex-1 { flex: 1 1 0% !important; }

notify-card .gap-2 { gap: 0.5rem !important; }
notify-card .gap-3 { gap: 0.75rem !important; }

notify-card .w-full { width: 100% !important; }

notify-card .mr-1 { margin-right: 0.25rem !important; }
notify-card .mt-3 { margin-top: 1rem !important; }
notify-card .mb-3 { margin-bottom: 1rem !important; }
notify-card .my-3 {
    margin-top: 1rem !important;
    margin-bottom: 1rem !important;
}
notify-card .p-3 { padding: 1rem !important; }

notify-card .break-words { overflow-wrap: break-word !important; }
notify-card .no-underline { text-decoration: none !important; }

notify-card .badge {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    font-weight: 500;
}
notify-card .badge-sm {
    padding: 0.37rem 0.55rem;
    font-size: var(--mdui-typescale-label-small-size);
    line-height: var(--mdui-typescale-label-small-line-height);
}
notify-card .rounded-full { border-radius: 9999px !important; }

notify-card .notify-card {
    box-sizing: border-box;
    width: 100%;
    max-width: none;
    border-left: 6px solid rgba(var(--mdui-color-primary));
    transition: background-color 0.2s ease;
}
notify-card .notify-card.notify-tertiary  { border-left-color: rgba(var(--mdui-color-tertiary)); }
notify-card .notify-card.notify-error     { border-left-color: rgba(var(--mdui-color-error)); }
notify-card .notify-card.notify-secondary { border-left-color: rgba(var(--mdui-color-secondary)); }

/* 卡片背景：与优先级对应的 M3 container 色 */
notify-card .notify-card.notify-primary   { background-color: rgba(var(--mdui-color-primary-container)); }
notify-card .notify-card.notify-secondary { background-color: rgba(var(--mdui-color-secondary-container)); }
notify-card .notify-card.notify-tertiary  { background-color: rgba(var(--mdui-color-tertiary-container)); }
notify-card .notify-card.notify-error     { background-color: rgba(var(--mdui-color-error-container)); }

/* 操作按钮：与优先级同色系的 filled（穿透 shadow 的 button part） */
${NotifyCard.#actionToneCss()}

notify-card .notify-icon-sm { font-size: 1rem; }

notify-card .notify-body { line-height: 1.7; }
notify-card .notify-body > *:first-child { margin-top: 0; }
notify-card .notify-body > *:last-child { margin-bottom: 0; }

notify-card .notify-body img { max-width: 100%; border-radius: 8px; }

notify-card .notify-body pre {
    background: rgba(var(--mdui-color-surface-container));
    padding: 0.75rem 1rem;
    border-radius: 8px;
    overflow: auto;
}

notify-card .notify-body code {
    background: rgba(var(--mdui-color-surface-container));
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
    font-size: 0.92em;
}
notify-card .notify-body pre code { background: transparent; padding: 0; }

notify-card .notify-body blockquote {
    border-left: 4px solid rgba(var(--mdui-color-outline-variant));
    padding: 0.25rem 0.85rem;
    margin: 0.75rem 0;
    color: rgba(var(--mdui-color-on-surface-variant));
}

notify-card .notify-body table {
    width: 100%;
    border-collapse: collapse;
    margin: 0.5rem 0;
}
notify-card .notify-body th,
notify-card .notify-body td {
    border: 1px solid rgba(var(--mdui-color-outline-variant));
    padding: 0.4rem 0.6rem;
}
notify-card .notify-body hr {
    border: 0;
    border-top: 1px solid rgba(var(--mdui-color-outline-variant));
    margin: 1rem 0;
}
notify-card .notify-body ul,
notify-card .notify-body ol { padding-left: 1.5rem; margin: 0.5rem 0; }
notify-card .notify-body li { list-style: revert; margin: 0.15rem 0; }
`;

    static #esc(s) {
        return String(s == null ? '' : s)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    static #iconLine(icon, text, spanClass = '') {
        if (!text) return '';
        return `<span class="d-inline-flex items-center ${spanClass}"><mdui-icon name="${icon}" class="notify-icon-sm mr-1"></mdui-icon>${NotifyCard.#esc(text)}</span>`;
    }

    static #actionsBlock(obj, toneCls) {
        const rows = obj && typeof obj === 'object' ? Object.entries(obj) : [];
        if (!rows.length) return '';
        const tone = NotifyCard.#paletteTones.includes(toneCls) ? toneCls : 'primary';
        return `<div class="d-flex flex-wrap gap-2 mt-3 notify-actions notify-actions--${tone}">${rows.map(
            ([label, href]) =>
                `<mdui-button variant="filled" href="${NotifyCard.#esc(href)}" target="_blank" rel="noopener noreferrer">${NotifyCard.#esc(label)}</mdui-button>`
        ).join('')}</div>`;
    }

    static #footerBlock(time, backHref) {
        const t = NotifyCard.#iconLine('schedule', time);
        const b = backHref
            ? `<a href="${NotifyCard.#esc(backHref)}" class="d-inline-flex items-center no-underline text-on-surface-variant"><mdui-icon name="home" class="notify-icon-sm mr-1"></mdui-icon>返回首页</a>`
            : '';
        if (!t && !b) return '';
        return `<mdui-divider class="my-3"></mdui-divider><div class="d-flex flex-wrap items-center gap-3 body-small text-on-surface-variant">${t}<div class="flex-1"></div>${b}</div>`;
    }

    constructor() {
        super();
        this._p = { body: null, actions: null };
        this._slot = { body: null, actions: null };
    }

    connectedCallback() {
        const tpl = this.querySelector('template[data-role="body"]');
        if (tpl) this._slot.body = tpl.innerHTML;
        const script = this.querySelector('script[data-role="actions"]');
        if (script?.textContent.trim()) {
            try {
                this._slot.actions = JSON.parse(script.textContent);
            } catch (e) {
                console.warn('[notify-card] actions JSON 无效', e);
            }
        }
        this.#paint();
    }

    attributeChangedCallback() {
        if (this.isConnected) this.#paint();
    }

    set priority(v) { this.setAttribute('priority', v); }
    get priority()  { return this.getAttribute('priority') || 'info'; }

    set heading(v) { this.setAttribute('heading', v); }
    get heading()  { return this.getAttribute('heading') || ''; }

    set channel(v) { this.setAttribute('channel', v); }
    get channel()  { return this.getAttribute('channel') || ''; }

    set time(v) { this.setAttribute('time', v); }
    get time()  { return this.getAttribute('time') || ''; }

    set backHref(v) { this.setAttribute('back-href', v); }
    get backHref()  { return this.getAttribute('back-href') || ''; }

    set body(v) { this._p.body = v; if (this.isConnected) this.#paint(); }
    get body()  { return this._p.body; }

    set actions(v) { this._p.actions = v; if (this.isConnected) this.#paint(); }
    get actions()  { return this._p.actions ?? this._slot.actions; }

    #bodyHtml() {
        return this._p.body ?? this._slot.body ?? '';
    }

    #actionsResolved() {
        if (this._p.actions && typeof this._p.actions === 'object') return this._p.actions;
        if (this._slot.actions) return this._slot.actions;
        const raw = this.getAttribute('actions');
        if (!raw) return null;
        try { return JSON.parse(raw); } catch { return null; }
    }

    #paint() {
        const meta = NotifyCard.#priorityMeta[this.priority] || NotifyCard.#priorityMeta.info;
        const cls = meta.cls;
        const heading = this.heading || '（无标题）';

        this.innerHTML = `
<style>${NotifyCard.#styleText}</style>
<mdui-card variant="elevated" class="notify-card notify-${cls} w-full p-3">
    <div class="d-flex items-center flex-wrap gap-2 mb-3">
        <span class="badge badge-sm rounded-full bg-${cls} text-on-${cls} d-inline-flex items-center font-medium">
            <mdui-icon name="${meta.icon}" class="notify-icon-sm mr-1"></mdui-icon>${NotifyCard.#esc(meta.label)}
        </span>
        <div class="flex-1"></div>
        ${NotifyCard.#iconLine('forum', this.channel, 'body-small text-on-surface-variant')}
    </div>
    <h1 class="headline-medium text-${cls} font-bold mb-3 break-words">${NotifyCard.#esc(heading)}</h1>
    <div class="notify-body body-large text-on-surface">${this.#bodyHtml()}</div>
    ${NotifyCard.#actionsBlock(this.#actionsResolved(), cls)}
    ${NotifyCard.#footerBlock(this.time, this.backHref)}
</mdui-card>`;
    }
}

customElements.define('notify-card', NotifyCard);
