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

        const act = this.actions;
        const rows = act && typeof act === 'object' ? Object.entries(act) : [];
        const actionsHtml =
            rows.length === 0
                ? ''
                : `<div class="notify-actions">${rows
                      .map(
                          ([label, href]) =>
                              `<mdui-button class="notify-action-btn" variant="outlined" href="${href}" target="_blank" rel="noopener noreferrer">${label}</mdui-button>`
                      )
                      .join('')}</div>`;

        const ch = this.channel;
        const channelLine = ch
            ? `<span class="notify-meta-item notify-meta-item--channel"><mdui-icon name="forum" class="notify-icon-sm"></mdui-icon>${ch}</span>`
            : '';

        const footerT = this.time
            ? `<span class="notify-meta-item notify-meta-item--time"><mdui-icon name="schedule" class="notify-icon-sm"></mdui-icon>${this.time}</span>`
            : '';
        const footerHtml =
            footerT || ''
                ? `<div class="notify-footer">${footerT}</div>`
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

.notify-card {
    box-sizing: border-box;
    width: 100%;
    max-width: none;
    padding: 1rem 1rem 1rem 0.875rem;
    border-left: 3px solid rgba(var(--mdui-color-outline-variant));
    background: rgba(var(--mdui-color-surface-container-low));
}

.notify-card--primary {
    border-left-color: rgba(var(--mdui-color-primary), 0.45);
}
.notify-card--secondary {
    border-left-color: rgba(var(--mdui-color-secondary), 0.45);
}
.notify-card--tertiary {
    border-left-color: rgba(var(--mdui-color-tertiary), 0.45);
}
.notify-card--error {
    border-left-color: rgba(var(--mdui-color-error), 0.55);
}

.notify-header {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.notify-header-spacer {
    flex: 1 1 0%;
}

.notify-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    white-space: nowrap;
    font-weight: 500;
    border-radius: 9999px;
    padding: 0.15rem 0.5rem;
    font-size: var(--mdui-typescale-label-small-size);
    line-height: var(--mdui-typescale-label-small-line-height);
    background: rgba(var(--mdui-color-surface-container));
    color: rgba(var(--mdui-color-on-surface-variant));
    border: 1px solid rgba(var(--mdui-color-outline-variant), 0.65);
}

.notify-status-badge--primary {
    color: rgba(var(--mdui-color-primary));
    border-color: rgba(var(--mdui-color-primary), 0.28);
}
.notify-status-badge--secondary {
    color: rgba(var(--mdui-color-secondary));
    border-color: rgba(var(--mdui-color-secondary), 0.28);
}
.notify-status-badge--tertiary {
    color: rgba(var(--mdui-color-tertiary));
    border-color: rgba(var(--mdui-color-tertiary), 0.28);
}
.notify-status-badge--error {
    color: rgba(var(--mdui-color-error));
    border-color: rgba(var(--mdui-color-error), 0.32);
}

.notify-title {
    margin: 0 0 0.75rem;
    font-size: var(--mdui-typescale-title-large-size);
    line-height: var(--mdui-typescale-title-large-line-height);
    letter-spacing: var(--mdui-typescale-title-large-tracking);
    font-weight: 600;
    color: rgba(var(--mdui-color-on-surface));
    overflow-wrap: break-word;
}

.notify-body {
    font-size: var(--mdui-typescale-body-medium-size);
    line-height: 1.65;
    letter-spacing: var(--mdui-typescale-body-medium-tracking);
    font-weight: var(--mdui-typescale-body-medium-weight);
    color: rgba(var(--mdui-color-on-surface));
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
    border-left: 3px solid rgba(var(--mdui-color-outline-variant));
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

.notify-icon-sm {
    font-size: 0.95rem;
    opacity: 0.85;
}

.notify-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.875rem;
    padding-top: 0.875rem;
    border-top: 1px solid rgba(var(--mdui-color-outline-variant), 0.55);
}

.notify-action-btn {
    text-decoration: none;
}

.notify-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: rgba(var(--mdui-color-on-surface-variant));
    font-size: var(--mdui-typescale-body-small-size);
    line-height: var(--mdui-typescale-body-small-line-height);
    letter-spacing: var(--mdui-typescale-body-small-tracking);
    font-weight: var(--mdui-typescale-body-small-weight);
}

.notify-footer {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    color: rgba(var(--mdui-color-on-surface-variant));
    font-size: var(--mdui-typescale-body-small-size);
    line-height: var(--mdui-typescale-body-small-line-height);
    letter-spacing: var(--mdui-typescale-body-small-tracking);
    font-weight: var(--mdui-typescale-body-small-weight);
    margin-top: 0.75rem;
}

</style>
<mdui-card variant="outlined" class="notify-card notify-card--${cls}">
    <div class="notify-header">
        <span class="notify-status-badge notify-status-badge--${cls}"><mdui-icon name="${meta.icon}" class="notify-icon-sm"></mdui-icon>${meta.label}</span><div class="notify-header-spacer"></div>${channelLine}
    </div>
    <h1 class="notify-title">${this.heading || '（无标题）'}</h1>
    <div class="notify-body">${bodyHtml}</div>
${actionsHtml}${footerHtml}
</mdui-card>`;
    }
}

customElements.define('notify-card', NotifyCard);
