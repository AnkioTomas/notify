/**
 * 后台通知列表：cardView + notify-card
 */
window.pageLoadFiles = [];
window.pageOnLoad = function () {
    const root = document.getElementById('notificationsCardView');
    const pageInfoEl = document.getElementById('notifPageInfo');
    const prevBtn = document.getElementById('notifPrev');
    const nextBtn = document.getElementById('notifNext');
    const refreshBtn = document.getElementById('notifRefresh');
    const pageSizeSel = document.getElementById('notifPageSize');

    let page = 1;
    let pageSize = 10;
    let total = 0;

    function escapeAttr(s) {
        return String(s == null ? '' : s)
            .replaceAll('&', '&amp;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;');
    }

    function totalPages() {
        return total <= 0 ? 1 : Math.max(1, Math.ceil(total / pageSize));
    }

    function updatePager() {
        const tp = totalPages();
        page = Math.min(page, tp);
        pageInfoEl.textContent = total > 0
            ? `第 ${page} / ${tp} 页 · 共 ${total} 条`
            : '暂无数据';
        prevBtn.disabled = page <= 1;
        nextBtn.disabled = page >= tp || total <= 0;
    }

    function render(rows) {
        root.innerHTML = '';
        if (!rows.length) {
            root.innerHTML = '<p class="body-large text-on-surface-variant mt-3">暂无通知</p>';
            return;
        }
        rows.forEach((row) => {
            const wrap = document.createElement('div');
            wrap.className = 'notification-card-slot';

            const card = document.createElement('notify-card');
            card.setAttribute('priority', row.priority || 'info');
            card.setAttribute('heading', row.title || '');
            card.setAttribute('channel', row.channel || '');
            card.setAttribute('time', row.time || '');
            card.markdown = row.message || '';
            card.actions = row.actions && typeof row.actions === 'object' ? row.actions : {};

            const foot = document.createElement('div');
            foot.className = 'd-flex justify-end mt-2';
            const href = escapeAttr(row.detailHref || '');
            foot.innerHTML = `<mdui-button variant="text" href="${href}" target="_blank" rel="noopener noreferrer">查看原文</mdui-button>`;

            wrap.appendChild(card);
            wrap.appendChild(foot);
            root.appendChild(wrap);
        });
    }

    function loadList() {
        root.setAttribute('aria-busy', 'true');
        $.request.get(
            '/notifications/list',
            { page, pageSize },
            (res) => {
                total = Number(res.count) || 0;
                render(Array.isArray(res.data) ? res.data : []);
                updatePager();
                root.setAttribute('aria-busy', 'false');
            },
            () => {
                root.setAttribute('aria-busy', 'false');
            }
        );
    }

    if (pageSizeSel) {
        pageSizeSel.value = String(pageSize);
        pageSizeSel.addEventListener('change', () => {
            pageSize = parseInt(pageSizeSel.value, 10) || 10;
            page = 1;
            loadList();
        });
    }

    refreshBtn.addEventListener('click', () => loadList());

    prevBtn.addEventListener('click', () => {
        if (page > 1) {
            page -= 1;
            loadList();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (page < totalPages()) {
            page += 1;
            loadList();
        }
    });

    loadList();

    return false;
};
