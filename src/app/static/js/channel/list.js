window.pageLoadFiles = [
    'DataTable',
    "Form",
    "DialogForm",
];
window.pageOnLoad = function (loading) {
    const esc = (v) => $.escapeHtml(v == null ? '' : String(v));
    const WECOM_KEY = 'notify.wecom.admin.key';

    function initDataBase() {
        let database = new DataTable("#dataTable");
        database.load({
            uri: "/channel/list",
            columns: [
                {
                    field: "logo",
                    name: "图标",
                    align: "center",
                    width: 72,
                    formatter: (v) => {
                        if (!v) {
                            return '<span style="opacity:.4">—</span>';
                        }
                        return `<img src="${esc(v)}" alt="" width="36" height="36" style="border-radius:8px;object-fit:cover;vertical-align:middle">`;
                    },
                },
                {
                    field: "name",
                    name: "显示名称",
                    align: "center",
                    width: 140,
                    formatter: (v) => esc(v),
                },
                {
                    field: "short_name",
                    name: "短标识",
                    align: "center",
                    width: 140,
                    formatter: (v) => esc(v),
                },
                {
                    field: "agent_id",
                    name: "AgentId",
                    align: "center",
                    width: 120,
                    formatter: (v) => esc(v) || '<span style="opacity:.5">未同步</span>',
                },
                {
                    field: "id",
                    name: "操作",
                    align: "center",
                    width: 320,
                    fixed: "right",
                    formatter: function (value, row, index) {
                        return `
<mdui-button-icon data-index="${index}" icon="science" class="action-test" title="测试发布"></mdui-button-icon>
<mdui-dropdown class="action-copy-dd" data-index="${index}" placement="bottom-end">
<mdui-button-icon slot="trigger" icon="content_copy" title="复制 Webhook URL"></mdui-button-icon>
<mdui-menu>
<mdui-menu-item value="dingding">钉钉 webhook</mdui-menu-item>
<mdui-menu-item value="feishu">飞书 webhook</mdui-menu-item>
<mdui-menu-item value="wechat">企业微信 webhook</mdui-menu-item>
<mdui-menu-item value="form">通用 Form 请求</mdui-menu-item>
<mdui-menu-item value="json">JSON 模式</mdui-menu-item>
</mdui-menu>
</mdui-dropdown>
<mdui-button-icon data-index="${index}" icon="sync" class="action-sync" title="同步企业微信应用"></mdui-button-icon>
<mdui-button-icon data-index="${index}" icon="edit" class="action-editor" title="编辑"></mdui-button-icon>
<mdui-button-icon data-index="${index}" icon="delete" class="action-delete" title="删除"></mdui-button-icon>`;
                    },
                },
            ],
            mobile: true,
            lineHeight: "auto",
            height: "auto",
            events: {
                onRowClick: function (row, rowIndex) {
                },
                onCellClick: function (row, rowIndex, colIndex, colName) {

                },
                onPaged: function (page, pageSize) {

                },
            },
            empty_msg: "无数据",
            page: true,
            selectable: false
        });
        return database;
    }

    let database = initDataBase();

    /**
     * POST 发布地址，查询串含 type、authorization（与 {@see Main::publish} 一致）。
     * @param {"dingding"|"feishu"|"wechat"|"form"|"json"} webhookType
     * @param {string} shortName
     * @param {string} authorization
     */
    function buildPublishWebhookUrl(webhookType, shortName, authorization) {
        const base = `${location.origin}/${encodeURIComponent(shortName)}`;
        const q = new URLSearchParams();
        q.set("type", webhookType);
        q.set("authorization", authorization);
        return `${base}?${q.toString()}`;
    }

    const qrDialog = document.querySelector("#wecomQrDialog");
    const qrImg = document.querySelector("#wecomQr");
    const qrStatus = document.querySelector("#wecomQrStatus");
    const captchaBox = document.querySelector("#wecomCaptchaBox");
    const captchaInput = document.querySelector("#wecomCaptcha");
    const captchaSubmit = document.querySelector("#wecomCaptchaSubmit");
    let qrSession = null;
    let qrOnReady = null;

    function savedWecomKey() {
        return sessionStorage.getItem(WECOM_KEY) || '';
    }

    function rememberWecomKey(key) {
        if (key) {
            sessionStorage.setItem(WECOM_KEY, key);
        }
    }

    function forgetWecomKey() {
        sessionStorage.removeItem(WECOM_KEY);
    }

    function setQrStatus(text) {
        qrStatus.textContent = text;
    }

    function showCaptcha(show) {
        captchaBox.style.display = show ? "block" : "none";
        captchaSubmit.style.display = show ? "" : "none";
        if (!show) {
            captchaInput.value = "";
        }
    }

    /** 只停轮询，不销毁服务端会话（会话要复用） */
    function stopQrPoll() {
        showCaptcha(false);
        qrOnReady = null;
        if (qrSession) {
            qrSession.stopped = true;
            qrSession = null;
        }
    }

    /** 用户主动关闭登录框：停轮询；若这次还没登录成功，结束本次 begin 出来的临时会话 */
    function abortQrLogin() {
        const key = qrSession ? qrSession.key : '';
        const pending = !!qrOnReady;
        stopQrPoll();
        if (pending && key && key !== savedWecomKey()) {
            $.request.postForm("/wechat/qr/end", {key: key}, () => {});
        }
    }

    /**
     * 有可用管理后台会话就直接回调；否则扫码。鉴权失效由调用方 clear 后再调一次。
     * @param {(key: string) => void} onReady
     */
    function withWecomAuth(onReady) {
        const key = savedWecomKey();
        if (!key) {
            beginWecomLogin(onReady);
            return;
        }
        $.request.postForm("/wechat/session", {key: key}, (resp) => {
            if (resp.code === 200 && resp.data && resp.data.alive) {
                onReady(key);
                return;
            }
            forgetWecomKey();
            beginWecomLogin(onReady);
        });
    }

    function beginWecomLogin(onReady) {
        stopQrPoll();
        qrOnReady = onReady;
        qrImg.style.display = "none";
        showCaptcha(false);
        setQrStatus("正在获取二维码…");
        qrDialog.open = true;
        $.request.postForm("/wechat/qr/begin", {}, (resp) => {
            if (resp.code !== 200) {
                setQrStatus(resp.msg || "获取二维码失败");
                return;
            }
            const s = {key: resp.data.key, stopped: false, tlKey: ""};
            qrSession = s;
            qrImg.src = resp.data.qr;
            qrImg.style.display = "inline-block";
            setQrStatus("请用企业微信扫码并确认登录管理后台");
            pollQr(s, "QRCODE_SCAN_NEVER");
        });
    }

    function onWecomReady(key) {
        rememberWecomKey(key);
        const cb = qrOnReady;
        qrOnReady = null;
        stopQrPoll();
        qrDialog.open = false;
        cb && cb(key);
    }

    function pollQr(s, status) {
        if (s.stopped) {
            return;
        }
        $.request.postForm("/wechat/qr/poll", {key: s.key, status: status}, (resp) => {
            if (s.stopped) {
                return;
            }
            if (resp.code !== 200) {
                setQrStatus(resp.msg || "登录失败，请关闭后重试");
                return;
            }
            const state = resp.data.status;
            if (state === "LOGGED_IN") {
                setQrStatus("登录成功");
                onWecomReady(s.key);
            } else if (state === "NEED_CAPTCHA") {
                s.tlKey = resp.data.tl_key || "";
                qrImg.style.display = "none";
                showCaptcha(true);
                const mobile = resp.data.mobile ? `（${resp.data.mobile}）` : "";
                setQrStatus(`企微检测到异地登录，验证码已发到管理员手机${mobile}，请填写后提交`);
            } else if (state === "QRCODE_SCAN_ING") {
                setQrStatus("已扫码，请在手机上确认登录");
                pollQr(s, state);
            } else if (state === "QRCODE_SCAN_NEVER") {
                pollQr(s, state);
            } else {
                setQrStatus("二维码已失效，请关闭后重试");
            }
        });
    }

    $("#wecomCaptchaSubmit").on("click", () => {
        if (!qrSession || !qrSession.tlKey) {
            return;
        }
        const captcha = String(captchaInput.value || "").trim();
        if (!captcha) {
            $.toaster.error("请输入验证码");
            return;
        }
        setQrStatus("正在校验验证码…");
        $.request.postForm("/wechat/qr/captcha", {
            key: qrSession.key,
            tl_key: qrSession.tlKey,
            captcha: captcha,
        }, (resp) => {
            if (resp.code !== 200) {
                setQrStatus(resp.msg || "验证码错误");
                return;
            }
            showCaptcha(false);
            setQrStatus("登录成功");
            onWecomReady(qrSession.key);
        });
    });

    $("#wecomQrClose").on("click", () => {
        qrDialog.open = false;
        abortQrLogin();
    });

    /** 业务接口若报会话失效，清 key 再扫码重试一次 */
    function withWecomRetry(run) {
        withWecomAuth((key) => {
            run(key, (resp) => {
                if (resp && resp.code === 401) {
                    forgetWecomKey();
                    withWecomAuth((key2) => run(key2, null));
                    return;
                }
            });
        });
    }

    const logoInput = document.querySelector("#channelLogo");
    const logoFile = document.querySelector("#channelLogoFile");
    const logoPreview = document.querySelector("#channelLogoPreview");

    function setLogoPreview(dataUrl) {
        logoInput.value = dataUrl || '';
        if (dataUrl) {
            logoPreview.src = dataUrl;
            logoPreview.style.display = "block";
        } else {
            logoPreview.removeAttribute("src");
            logoPreview.style.display = "none";
        }
    }

    logoFile.addEventListener("change", () => {
        const file = logoFile.files && logoFile.files[0];
        if (!file) {
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            $.toaster.error("图标请小于 2MB");
            logoFile.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = () => setLogoPreview(String(reader.result || ''));
        reader.readAsDataURL(file);
    });

    let dialog = document.querySelector("#channelDialog");
    let testDialog = document.querySelector("#testDialog");
    $("#dataTable")
        .on("click", ".action-copy-dd mdui-menu-item", function () {
            const value = this.getAttribute("value");
            if (!value) {
                return;
            }
            const index = $(this).closest(".action-copy-dd").data("index");
            const row = database.getRow(index);
            if (!row || !row.short_name) {
                $.toaster.error("无法获取频道信息");
                return;
            }
            $.request.get("/token/get", {}, (authResp) => {
                const auth = authResp.data;
                if (!auth) {
                    $.toaster.error("无法获取授权 Token");
                    return;
                }
                $.copy(buildPublishWebhookUrl(value, row.short_name, auth));
                $.toaster.success("已复制URL");
            });
        })
        .on('click', '.action-sync', function () {
            const row = database.getRow($(this).data("index"));
            withWecomRetry((key, onResp) => {
                $.request.postForm("/wechat/sync", {key: key, id: row.id}, (resp) => {
                    if (onResp) {
                        onResp(resp);
                        if (resp.code === 401) {
                            return;
                        }
                    }
                    if (resp.code !== 200) {
                        $.toaster.error(resp.msg || "同步失败");
                        return;
                    }
                    $.toaster.success(resp.msg || "同步成功");
                    database.reload({}, true);
                });
            });
        })
        .on('click', '.action-delete', function () {
            const row = database.getRow($(this).data("index"));
            const doDel = (key, onResp) => {
                const payload = {id: row.id};
                if (key) {
                    payload.key = key;
                }
                $.request.postForm("/channel/del", payload, (data) => {
                    if (onResp) {
                        onResp(data);
                        if (data.code === 401) {
                            return;
                        }
                    }
                    if (data.code !== 200) {
                        $.toaster.error(data.msg || "删除失败");
                        return;
                    }
                    $.toaster.success(data.msg || "删除成功");
                    database.reload({}, true);
                });
            };
            if (row.agent_id) {
                withWecomRetry(doDel);
            } else {
                doDel('', null);
            }
        }).on('click', '.action-editor', function () {
        let row = database.getRow($(this).data("index"));
        dialog.open();
        dialog.setValue(row);
        logoFile.value = '';
        setLogoPreview(row.logo || '');
    }).on('click', '.action-test', function () {
        let row = database.getRow($(this).data("index"));

        testDialog.open();
        testDialog.setValue({
            url: `${location.origin}/${encodeURIComponent(row.short_name)}`,
            title: "测试通知",
            message: "这是一条测试消息",
            priority: "info",
            actionLeftText: "",
            actionLeftUrl: "",
            actionRightText: "",
            actionRightUrl: ""
        });
    });
    $("#refresh").on("click", () => {
        database.reload({}, true);
    });

    $("#addApp").on("click", () => {
        dialog.open(true);
        logoFile.value = '';
        setLogoPreview('');
    });

    dialog.submit("/channel/edit", (data, response) => {
        database.reload({}, true);
    });

    testDialog.submit(null, (data) => {
        const parts = [];
        if (data.actionLeftText && data.actionLeftUrl) {
            parts.push(`${data.actionLeftText},${data.actionLeftUrl}`);
        }
        if (data.actionRightText && data.actionRightUrl) {
            parts.push(`${data.actionRightText},${data.actionRightUrl}`);
        }
        const xActions = parts.join(";");

        $.request.get("/token/get", {}, (authResp) => {
            const auth = authResp.data;
            $.request.setHeaders({
                'Authorization': auth,
                'X-Title': encodeURIComponent(data.title),
                'X-Priority': data.priority || 'info',
                'X-Actions': encodeURIComponent(xActions),
            });
            $.request.postForm(data.url, data.message, (response) => {
                if(response.code !== 200){
                    $.toaster.error(response.msg);
                }else{
                    $.toaster.success("测试通知发送成功");
                    testDialog.close();
                }

            });
        });
    });
    window.pageOnUnLoad = function () {
        abortQrLogin();
        database.destroy();
    };
    return false
};
