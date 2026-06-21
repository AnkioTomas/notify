class NotifyCard extends HTMLElement {
    static get observedAttributes() {
        return ['priority', 'heading', 'channel', 'time', 'markdown', 'back-href', 'actions'];
    }

    static priorityMeta = {
        info:    { label: '信息', icon: 'info',         tag: 'info' },
        warning: { label: '警告', icon: 'warning',      tag: 'warning' },
        error:   { label: '错误', icon: 'error',        tag: 'red' },
        success: { label: '成功', icon: 'check_circle', tag: 'success' },
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
        this.paint();
    }

    attributeChangedCallback() {
        if (this.isConnected) this.paint();
    }

    set priority(v) { this.setAttribute('priority', v); }
    get priority()  { return this.getAttribute('priority') || 'info'; }

    set heading(v) { this.setAttribute('heading', v); }
    get heading()  { return this.getAttribute('heading') || ''; }

    set channel(v) { this.setAttribute('channel', v); }
    get channel()  { return this.getAttribute('channel') || ''; }

    set time(v) { this.setAttribute('time', v); }
    get time()  { return this.getAttribute('time') || ''; }

    set body(v) { this._p.body = v; if (this.isConnected) this.paint(); }
    get body()  { return this._p.body; }

    set actions(v) { this._p.actions = v; if (this.isConnected) this.paint(); }
    get actions() {
        if (this._p.actions != null && typeof this._p.actions === 'object') return this._p.actions;
        const raw = this.getAttribute('actions')?.trim();
        if (!raw) return null;
        try { return JSON.parse(decodeURIComponent(raw)); } catch {
            try { return JSON.parse(raw); } catch { return null; }
        }
    }

    static tagAccentCss() {
        return Object.values(NotifyCard.priorityMeta)
            .map(({ tag }) => `
.notify-card[data-tag="${tag}"] .notify-status-badge {
    background-color: rgb(var(--tag-${tag}-bg));
    color: rgb(var(--tag-${tag}-fg));
}
`)
            .join('');
    }

    paint() {
        const meta = NotifyCard.priorityMeta[this.priority] || NotifyCard.priorityMeta.info;
        const tag = meta.tag;

        const act = this.actions;
        const rows = act && typeof act === 'object' ? Object.entries(act) : [];
        const actionsHtml =
            rows.length === 0
                ? ''
                : `<div class="notify-actions">${rows
                      .map(
                          ([label, href]) =>
                              `<mdui-button class="notify-action-btn" variant="filled" href="${href}" target="_blank" rel="noopener noreferrer">${label}</mdui-button>`
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
    padding: 1rem;
    border: none;
    box-shadow: none;
    background: rgba(var(--mdui-color-surface-container-low));
}

${NotifyCard.tagAccentCss()}

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
    padding: 0.25rem 0.5rem;
    font-size: var(--mdui-typescale-label-small-size);
    line-height: var(--mdui-typescale-label-small-line-height);
    border: none;
    box-shadow: none;
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
    padding: 0.25rem 0.85rem;
    margin: 0.75rem 0;
    color: rgba(var(--mdui-color-on-surface-variant));
    background: rgba(var(--mdui-color-surface-container), 0.6);
    border-radius: 6px;
}

.notify-body table {
    width: 100%;
    border-collapse: collapse;
    margin: 0.5rem 0;
}

.notify-body td,
.notify-body th {
    padding: 0.4rem 0.6rem;
    border-bottom: 1px solid rgba(var(--mdui-color-outline-variant), 0.35);
}

.notify-body th {
    border-bottom-width: 1px;
    font-weight: 600;
}

.notify-body tr:last-child td {
    border-bottom: none;
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
    gap: 0.25rem 0.5rem;
    margin-top: 0.75rem;
}

.notify-action-btn {
    text-decoration: none;
}

.notify-action-btn::part(button) {
    border: none;
    box-shadow: none;
    border-radius: 9999px;
    min-height: 2rem;
    padding-inline: 0.875rem;
    font-weight: 500;
    font-size: var(--mdui-typescale-label-large-size);
    line-height: var(--mdui-typescale-label-large-line-height);
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
<mdui-card variant="filled" class="notify-card" data-tag="${tag}">
    <div class="notify-header">
        <span class="notify-status-badge"><mdui-icon name="${meta.icon}" class="notify-icon-sm"></mdui-icon>${meta.label}</span><div class="notify-header-spacer"></div>${channelLine}
    </div>
    <h1 class="notify-title">${this.heading || '（无标题）'}</h1>
    <div class="notify-body">${bodyHtml}</div>
${actionsHtml}${footerHtml}
</mdui-card>`;
    }
}

customElements.define('notify-card', NotifyCard);
