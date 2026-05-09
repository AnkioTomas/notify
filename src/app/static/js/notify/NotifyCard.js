class NotifyCard extends HTMLElement {
    static get observedAttributes() {
        return ['priority', 'heading', 'channel', 'time', 'markdown', 'back-href', 'actions'];
    }

    static #priorityMeta = {
        info:    { label: '信息', icon: 'info',         cls: 'secondary' },
        warning: { label: '警告', icon: 'warning',      cls: 'tertiary' },
        error:   { label: '错误', icon: 'error',        cls: 'error' },
        success: { label: '成功', icon: 'check_circle', cls: 'primary' },
    };

    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this._p = { body: null, actions: null };
        this._slot = { body: null };
    }

    connectedCallback() {
        const tpl = this.querySelector('template[data-role="body"]');
        if (tpl) this._slot.body = tpl.innerHTML;
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
    get actions() {
        if (this._p.actions != null && typeof this._p.actions === 'object') return this._p.actions;
        const raw = this.getAttribute('actions')?.trim();
        if (!raw) return null;
        try { return JSON.parse(decodeURIComponent(raw)); } catch {
            try { return JSON.parse(raw); } catch { return null; }
        }
    }

    #paint() {
        const meta = NotifyCard.#priorityMeta[this.priority] || NotifyCard.#priorityMeta.info;
        const cls = meta.cls;

        const tones = ['primary', 'secondary', 'tertiary', 'error'];
        const paletteCss = tones
            .map(
                (t) =>
                    `
.text-${t} {
    color: rgba(var(--mdui-color-${t})) !important;
}
.bg-${t} {
    background-color: rgba(var(--mdui-color-${t})) !important;
}
.text-on-${t} {
    color: rgba(var(--mdui-color-on-${t})) !important;
}
`
            )
            .join('\n');
        const actionToneCss = tones
            .map(
                (t) =>
                    `
.notify-actions--${t} mdui-button::part(button) {
    background-color: rgba(var(--mdui-color-${t})) !important;
    color: rgba(var(--mdui-color-on-${t})) !important;
}
.notify-actions--${t} mdui-button:focus-visible::part(button) {
    outline: 2px solid rgba(var(--mdui-color-${t}));
    outline-offset: 2px;
}
`
            )
            .join('\n');

        const act = this.actions;
        const rows = act && typeof act === 'object' ? Object.entries(act) : [];
        const tone = tones.includes(cls) ? cls : 'primary';
        const actionsHtml =
            rows.length === 0
                ? ''
                : `<div class="d-flex flex-wrap gap-2 mt-3 notify-actions notify-actions--${tone}">${rows
                      .map(
                          ([label, href]) =>
                              `<mdui-button variant="filled" href="${href}" target="_blank" rel="noopener noreferrer">${label}</mdui-button>`
                      )
                      .join('')}</div>`;

        const ch = this.channel;
        const channelLine = ch
            ? `<span class="d-inline-flex items-center body-small text-on-surface-variant"><mdui-icon name="forum" class="notify-icon-sm mr-1"></mdui-icon>${ch}</span>`
            : '';

        const footerT = this.time
            ? `<span class="d-inline-flex items-center body-small text-on-surface-variant"><mdui-icon name="schedule" class="notify-icon-sm mr-1"></mdui-icon>${this.time}</span>`
            : '';
        const footerB = this.backHref
            ? `<a href="${this.backHref}" class="d-inline-flex items-center no-underline text-on-surface-variant"><mdui-icon name="home" class="notify-icon-sm mr-1"></mdui-icon>返回首页</a>`
            : '';
        const footerHtml =
            footerT || footerB
                ? `<mdui-divider class="my-3"></mdui-divider><div class="d-flex flex-wrap items-center gap-3 body-small text-on-surface-variant">${footerT}<div class="flex-1"></div>${footerB}</div>`
                : '';

        const bodyHtml = this._p.body ?? this._slot.body ?? '';

        this.shadowRoot.innerHTML = `<style>
:host {
    display: block;
    flex: 1 1 100%;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

h1 {
    margin-top: 0;
}

.headline-medium {
    font-size: var(--mdui-typescale-headline-medium-size);
    line-height: var(--mdui-typescale-headline-medium-line-height);
    letter-spacing: var(--mdui-typescale-headline-medium-tracking);
    font-weight: var(--mdui-typescale-headline-medium-weight);
}

.body-large {
    font-size: var(--mdui-typescale-body-large-size);
    line-height: var(--mdui-typescale-body-large-line-height);
    letter-spacing: var(--mdui-typescale-body-large-tracking);
    font-weight: var(--mdui-typescale-body-large-weight);
}

.body-small {
    font-size: var(--mdui-typescale-body-small-size);
    line-height: var(--mdui-typescale-body-small-line-height);
    letter-spacing: var(--mdui-typescale-body-small-tracking);
    font-weight: var(--mdui-typescale-body-small-weight);
}

.font-medium {
    font-weight: 500 !important;
}

.font-bold {
    font-weight: 700 !important;
}

${paletteCss}

.text-on-surface {
    color: rgba(var(--mdui-color-on-surface)) !important;
}

.text-on-surface-variant {
    color: rgba(var(--mdui-color-on-surface-variant)) !important;
}

.d-flex {
    display: flex !important;
}

.d-inline-flex {
    display: inline-flex !important;
}

.flex-wrap {
    flex-wrap: wrap !important;
}

.items-center {
    align-items: center !important;
}

.flex-1 {
    flex: 1 1 0% !important;
}

.gap-2 {
    gap: 0.5rem !important;
}

.gap-3 {
    gap: 0.75rem !important;
}

.w-full {
    width: 100% !important;
}

.mr-1 {
    margin-right: 0.25rem !important;
}

.mt-3 {
    margin-top: 1rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.my-3 {
    margin-top: 1rem !important;
    margin-bottom: 1rem !important;
}

.p-3 {
    padding: 1rem !important;
}

.break-words {
    overflow-wrap: break-word !important;
}

.no-underline {
    text-decoration: none !important;
}

.badge {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    font-weight: 500;
}

.badge-sm {
    padding: 0.37rem 0.55rem;
    font-size: var(--mdui-typescale-label-small-size);
    line-height: var(--mdui-typescale-label-small-line-height);
}

.rounded-full {
    border-radius: 9999px !important;
}

.notify-card {
    box-sizing: border-box;
    width: 100%;
    max-width: none;
    border-left: 6px solid rgba(var(--mdui-color-primary));
    transition: background-color 0.2s ease;
}

.notify-tertiary {
    border-left-color: rgba(var(--mdui-color-tertiary));
}

.notify-error {
    border-left-color: rgba(var(--mdui-color-error));
}

.notify-secondary {
    border-left-color: rgba(var(--mdui-color-secondary));
}

.notify-primary {
    background-color: rgba(var(--mdui-color-primary-container));
}

.notify-secondary {
    background-color: rgba(var(--mdui-color-secondary-container));
}

.notify-tertiary {
    background-color: rgba(var(--mdui-color-tertiary-container));
}

.notify-error {
    background-color: rgba(var(--mdui-color-error-container));
}

${actionToneCss}

.notify-icon-sm {
    font-size: 1rem;
}

.notify-body {
    line-height: 1.7;
}

.notify-body > *:first-child {
    margin-top: 0;
}

.notify-body > *:last-child {
    margin-bottom: 0;
}

.notify-body img {
    max-width: 100%;
    border-radius: 8px;
}

.notify-body pre {
    background: rgba(var(--mdui-color-surface-container));
    padding: 0.75rem 1rem;
    border-radius: 8px;
    overflow: auto;
}

.notify-body code {
    background: rgba(var(--mdui-color-surface-container));
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
    font-size: 0.92em;
}

.notify-body pre code {
    background: transparent;
    padding: 0;
}

.notify-body blockquote {
    border-left: 4px solid rgba(var(--mdui-color-outline-variant));
    padding: 0.25rem 0.85rem;
    margin: 0.75rem 0;
    color: rgba(var(--mdui-color-on-surface-variant));
}

.notify-body table {
    width: 100%;
    border-collapse: collapse;
    margin: 0.5rem 0;
}

.notify-body td,
.notify-body th {
    border: 1px solid rgba(var(--mdui-color-outline-variant));
    padding: 0.4rem 0.6rem;
}

.notify-body hr {
    border: 0;
    border-top: 1px solid rgba(var(--mdui-color-outline-variant));
    margin: 1rem 0;
}

.notify-body ul,
.notify-body ol {
    padding-left: 1.5rem;
    margin: 0.5rem 0;
}

.notify-body li {
    list-style: revert;
    margin: 0.15rem 0;
}
</style>
<mdui-card variant="elevated" class="notify-card notify-${cls} w-full p-3">
    <div class="d-flex items-center flex-wrap gap-2 mb-3"><span class="badge badge-sm rounded-full bg-${cls} text-on-${cls} d-inline-flex items-center font-medium"><mdui-icon name="${meta.icon}" class="notify-icon-sm mr-1"></mdui-icon>${meta.label}</span><div class="flex-1"></div>${channelLine}</div>
    <h1 class="headline-medium text-${cls} font-bold mb-3 break-words">${this.heading || '（无标题）'}</h1>
    <div class="notify-body body-large text-on-surface">${bodyHtml}</div>${actionsHtml}${footerHtml}
</mdui-card>`;
    }
}

customElements.define('notify-card', NotifyCard);
