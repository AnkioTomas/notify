<?php

declare(strict_types=1);

namespace app\utils;

use nova\framework\cache\Cache;
use nova\framework\core\Context;
use nova\framework\core\Logger;

/**
 * 代管理员操作企业微信管理后台：扫码登录、同步自建应用、触发 Secret 查看、删除应用。
 *
 * 会话（Cookie / qr_key / 登录态）走框架 Cache，TTL 到期自动丢弃。
 * curl 仍需要 Cookie 文件，请求期间物化到临时文件，结束后写回 Cache。
 */
class WorkWechatAdmin
{
    private const string BASE = 'https://work.weixin.qq.com/wework_admin/';

    private const string CACHE_PREFIX = 'wechat.admin.';

    private const int SESSION_TTL = 1800;

    /**
     * @param array{cookies: string, qr_key: string, ok: bool} $state
     */
    private function __construct(
        private readonly string $key,
        private readonly string $jar,
        private array $state,
    ) {
    }

    public function __destruct()
    {
        $this->persist();
        @unlink($this->jar);
    }

    /**
     * 申请登录二维码。
     *
     * @return array{key: string, qr: string} qr 为 PNG 的 data URL
     * @throws WechatException
     */
    public static function begin(): array
    {
        $key = bin2hex(random_bytes(8));
        $tmp = self::create($key);
        $data = $tmp->json('wwqrlogin/mng/get_key', ['login_type' => 'login_admin', 'r' => (string)mt_rand() / mt_getrandmax()]);
        $qrKey = (string)($data['data']['qrcode_key'] ?? '');
        if ($qrKey === '') {
            throw new WechatException('获取登录二维码失败');
        }
        // 对外只暴露本地会话 key，企微的 qrcode_key 放在 Cache，避免调用方伪造
        $tmp->state['qr_key'] = $qrKey;
        $tmp->persist();
        $png = $tmp->request('wwqrlogin/mng/qrcode', ['qrcode_key' => $qrKey, 'login_type' => 'login_admin']);

        return ['key' => $key, 'qr' => 'data:image/png;base64,' . base64_encode($png)];
    }

    /**
     * 长轮询扫码状态；确认登录后完成会话建立。
     *
     * 服务器代完成登录时，企微常因异地 IP 触发短信验证码，此时返回 NEED_CAPTCHA。
     *
     * @param string $status 上一次返回的状态
     * @return array{status: string, tl_key?: string, mobile?: string}
     * @throws WechatException
     */
    public static function poll(string $key, string $status): array
    {
        $admin = self::open($key);
        $qrKey = $admin->state['qr_key'];
        $status = in_array($status, ['QRCODE_SCAN_NEVER', 'QRCODE_SCAN_ING'], true) ? $status : 'QRCODE_SCAN_NEVER';
        $data = $admin->json('wwqrlogin/mng/check', [
            'qrcode_key' => $qrKey,
            'status'     => $status,
            'r'          => (string)mt_rand() / mt_getrandmax(),
        ], null, 25)['data'] ?? [];
        $state = (string)($data['status'] ?? $status);
        if ($state !== 'QRCODE_SCAN_SUCC') {
            return ['status' => $state];
        }

        $code = (string)($data['auth_code'] ?? '');
        // 登录页是 HTML 跳转链，不能带 ajax/f=json，否则 cookie 建不起来（线上 errCode=-3 outsession）
        $tlKey = $admin->loginBrowser([
            '_r'           => (string)random_int(100, 999),
            'code'         => $code,
            'qrcode_key'   => $qrKey,
            'wwqrlogin'    => '1',
            'auth_source'  => (string)($data['auth_source'] ?? 'SOURCE_FROM_WEWORK'),
            'confirm_type' => (string)($data['confirm_type'] ?? 0),
        ]);
        if ($tlKey !== null) {
            $mobile = $admin->sendLoginCaptcha($tlKey);

            return ['status' => 'NEED_CAPTCHA', 'tl_key' => $tlKey, 'mobile' => $mobile];
        }
        $admin->markLoggedIn();

        return ['status' => 'LOGGED_IN'];
    }

    /**
     * 提交异地登录短信验证码，完成管理后台会话。
     *
     * @throws WechatException
     */
    public static function confirmCaptcha(string $key, string $tlKey, string $captcha): void
    {
        $admin = self::open($key);
        $admin->confirmLoginCaptcha($tlKey, $captcha);
        $admin->markLoggedIn();
    }

    /**
     * 按 AgentId（app_open_id）或名称查找自建应用。
     *
     * @return array{corp_id: string, app_id: string, agent_id: string, name: string}|null
     * @throws WechatException
     */
    public static function findApp(string $key, string $agentId = '', string $name = ''): ?array
    {
        $admin = self::open($key, true);
        $list = $admin->json('getCorpApplication', [], self::form([
            'app_type' => '0',
        ]))['data']['openapi_app'] ?? [];
        $byName = null;
        foreach ($list as $item) {
            $openId = (string)($item['app_open_id'] ?? '');
            $itemName = (string)($item['name'] ?? '');
            $row = [
                'corp_id'  => (string)($item['corp_id'] ?? ''),
                'app_id'   => (string)($item['app_id'] ?? ''),
                'agent_id' => $openId,
                'name'     => $itemName,
            ];
            if ($agentId !== '' && $openId === $agentId) {
                return $row;
            }
            if ($byName === null && $name !== '' && $itemName === $name) {
                $byName = $row;
            }
        }

        return $byName;
    }

    /**
     * 当前管理后台会话是否仍有效（Cache 里有登录态）。
     */
    public static function alive(string $key): bool
    {
        if (!ctype_xdigit($key)) {
            return false;
        }
        $state = self::cache()->get(self::CACHE_PREFIX . $key);

        return is_array($state) && !empty($state['ok']) && ($state['qr_key'] ?? '') !== '';
    }

    /**
     * 新建自建应用，可见范围为全公司。
     *
     * @param string $logo 图标：data URL 或纯 base64，空则用企微默认
     * @return array{corp_id: string, app_id: string, agent_id: string}
     * @throws WechatException
     */
    public static function createApp(string $key, string $name, string $logo = ''): array
    {
        $admin = self::open($key, true);
        $root = self::rootPartyId($admin);

        $data = $admin->json('apps/addOpenApiApp', [], self::form([
            'name'                => $name,
            'description'         => '',
            'english_name'        => '',
            'english_description' => '',
            'app_open'            => 'true',
            'logoimage'           => self::logoPayload($logo),
            'visible_pid[]'       => $root,
        ]))['data'] ?? [];
        if (empty($data['app_id'])) {
            throw new WechatException('创建应用失败');
        }

        return [
            'corp_id'  => (string)$data['corp_id'],
            'app_id'   => (string)$data['app_id'],
            'agent_id' => (string)$data['app_open_id'],
        ];
    }

    /**
     * 更新已有应用的名称/图标。
     *
     * @throws WechatException
     */
    public static function updateApp(string $key, string $appId, string $name, string $logo = ''): void
    {
        $admin = self::open($key, true);
        $fields = [
            'app_id'      => $appId,
            'name'        => $name,
            'description' => '',
            'app_open'    => 'true',
        ];
        if ($logo !== '') {
            $fields['logoimage'] = self::logoPayload($logo);
        }
        $resp = $admin->json('apps/saveOpenApiApp', [], self::form($fields));
        $err = $resp['result']['errCode'] ?? 0;
        if ($err !== 0) {
            throw new WechatException($resp['result']['humanMessage'] ?? '更新企业微信应用失败', (int)$err);
        }
    }

    /** data URL / 纯 base64 → 企微 logoimage 字段要的纯 base64 */
    private static function logoPayload(string $logo): string
    {
        $logo = trim($logo);
        if ($logo === '') {
            return '';
        }
        if (preg_match('/^data:image\/[a-zA-Z0-9+.-]+;base64,(.+)$/s', $logo, $m)) {
            return $m[1];
        }

        return $logo;
    }

    /**
     * 取企业根部门内部 partyid，用作新建应用的可见范围。
     * 企微后台以 openapi_partyid=1 标识根部门，parentid=0 作兼容。
     *
     * @throws WechatException
     */
    private static function rootPartyId(self $admin): string
    {
        // 企微后台 POST 必须带 _d2st，空 body 会返回空部门列表（见线上日志）
        $resp = $admin->json('contacts/party/cache', [], self::form([]));
        $parties = $resp['data']['party_list']['list']
            ?? $resp['data']['party_list']
            ?? [];
        $err = (int)($resp['result']['errCode'] ?? 0);
        if ($err === -3 || (($resp['result']['message'] ?? '') === 'outsession')) {
            throw new WechatException('企业微信管理后台会话已失效，请关闭后重新扫码');
        }
        if (!is_array($parties) || $parties === []) {
            Logger::error('企微部门列表为空，原始返回：' . substr(json_encode($resp, JSON_UNESCAPED_UNICODE), 0, 500));
            throw new WechatException('读取企业部门列表为空，登录可能已失效或无通讯录权限');
        }

        $fallback = '';
        foreach ($parties as $p) {
            if (!is_array($p)) {
                continue;
            }
            $partyId = (string)($p['partyid'] ?? $p['party_id'] ?? '');
            if ($partyId === '') {
                continue;
            }
            if ($fallback === '') {
                $fallback = $partyId;
            }
            // 官方通讯录根部门 openapi_partyid 固定为 1
            if ((string)($p['openapi_partyid'] ?? '') === '1') {
                return $partyId;
            }
            if ((string)($p['parentid'] ?? $p['parent_id'] ?? '') === '0') {
                return $partyId;
            }
        }

        if ($fallback !== '') {
            return $fallback;
        }

        throw new WechatException('读取企业根部门失败');
    }

    /**
     * 配置应用「接收消息」回调。企微保存时会立即请求该 URL 校验，所以回调必须已可公网访问。
     *
     * @throws WechatException
     */
    public static function saveCallback(string $key, string $appId, string $url, string $token, string $aesKey): void
    {
        $admin = self::open($key, true);
        $resp = $admin->json('apps/saveOpenApiApp', [], self::form([
            'callback_url'                                       => $url,
            'url_token'                                          => $token,
            'callback_aeskey'                                    => $aesKey,
            'report_loc_flag'                                    => 0,
            'is_report_enter'                                    => 'false',
            'report_approval_event'                              => 'true',
            'callback_switch[external_contact_callback]'         => 'true',
            'callback_switch[wechat_kf_callback]'                => 'true',
            'callback_switch[external_pay_callback]'             => 'true',
            'callback_switch[chain_share_callback]'              => 'true',
            'callback_switch[live_callback]'                     => 'true',
            'callback_switch[meeting_room_callback]'             => 'true',
            'callback_switch[advanced_feature_approval_callback]' => 'true',
            'app_id'                                             => $appId,
            'callback_host'                                      => (string)parse_url($url, PHP_URL_HOST),
        ]));
        $err = $resp['result']['errCode'] ?? 0;
        if ($err !== 0) {
            throw new WechatException($resp['result']['humanMessage'] ?? '保存回调失败', (int)$err);
        }
    }

    /**
     * 触发「查看 Secret」：向管理员企业微信客户端推送密钥查看消息。
     *
     * @throws WechatException
     */
    public static function sendSecret(string $key, string $appId): void
    {
        $admin = self::open($key, true);
        $resp = $admin->json('two_factor_auth_operation/create', [], self::form([
            'appid'         => $appId,
            'business_type' => '1',
            'app_type'      => '1',
        ]));
        $secretKey = (string)($resp['data']['key'] ?? '');
        if ($secretKey === '') {
            $err = $resp['result']['errCode'] ?? 0;
            throw new WechatException(
                $resp['result']['humanMessage'] ?? '触发 Secret 查看失败',
                (int)$err
            );
        }
    }

    /**
     * 删除自建应用。
     *
     * @throws WechatException
     */
    public static function deleteApp(string $key, string $appId, string $agentId): void
    {
        $admin = self::open($key, true);
        $resp = $admin->json('apps/delOpenApiApp', [], self::form([
            'app_id'      => $appId,
            'app_open_id' => $agentId,
            'app_type'    => 'APP_TYPE_MSG',
        ]));
        $err = $resp['result']['errCode'] ?? 0;
        if ($err !== 0) {
            throw new WechatException($resp['result']['humanMessage'] ?? '删除企业微信应用失败', (int)$err);
        }
    }

    public static function end(string $key): void
    {
        if (!ctype_xdigit($key)) {
            return;
        }
        self::cache()->delete(self::CACHE_PREFIX . $key);
    }

    /**
     * 后台表单 POST：自动带上 _d2st（企微前端同款随机串，缺了部分接口会返回空数据）。
     *
     * @param array<string, scalar> $fields
     */
    private static function form(array $fields): string
    {
        $fields['_d2st'] = 'a' . random_int(1000000, 9999999);

        return http_build_query($fields);
    }

    /**
     * @throws WechatException
     */
    private static function create(string $key): self
    {
        $state = ['cookies' => '', 'qr_key' => '', 'ok' => false];
        self::cache()->set(self::CACHE_PREFIX . $key, $state, self::SESSION_TTL);

        return self::hydrate($key, $state);
    }

    /**
     * @throws WechatException
     */
    private static function open(string $key, bool $needLogin = false): self
    {
        if (!ctype_xdigit($key)) {
            throw new WechatException('登录会话不存在或已过期，请重新扫码');
        }
        $state = self::cache()->get(self::CACHE_PREFIX . $key);
        if (!is_array($state) || ($state['qr_key'] ?? '') === '') {
            throw new WechatException('登录会话不存在或已过期，请重新扫码');
        }
        $state = [
            'cookies' => (string)($state['cookies'] ?? ''),
            'qr_key'  => (string)$state['qr_key'],
            'ok'      => (bool)($state['ok'] ?? false),
        ];
        if ($needLogin && !$state['ok']) {
            throw new WechatException('尚未完成扫码登录');
        }

        return self::hydrate($key, $state);
    }

    /**
     * @param array{cookies: string, qr_key: string, ok: bool} $state
     * @throws WechatException
     */
    private static function hydrate(string $key, array $state): self
    {
        $jar = tempnam(sys_get_temp_dir(), 'wxadmin_');
        if ($jar === false) {
            throw new WechatException('无法创建临时 Cookie 文件');
        }
        if ($state['cookies'] !== '') {
            file_put_contents($jar, $state['cookies']);
        }

        return new self($key, $jar, $state);
    }

    private static function cache(): Cache
    {
        return Context::instance()->cache;
    }

    private function markLoggedIn(): void
    {
        $this->state['ok'] = true;
        $this->persist();
    }

    private function persist(): void
    {
        $this->state['cookies'] = is_file($this->jar) ? (string)file_get_contents($this->jar) : '';
        self::cache()->set(self::CACHE_PREFIX . $this->key, $this->state, self::SESSION_TTL);
    }

    /**
     * 完成扫码后的 HTML 登录跳转。
     *
     * @param array<string, scalar> $query
     * @return string|null 需要短信验证时返回 tl_key，否则 null 表示已登录
     * @throws WechatException
     */
    private function loginBrowser(array $query): ?string
    {
        $url = self::BASE . 'loginpage_wx?' . http_build_query($query);
        for ($i = 0; $i < 8; $i++) {
            [$code, $body, $location] = $this->http($url, false);
            $next = $location;
            if ($next === '' && preg_match('/Found\. Redirecting to\s+(\S+)/', $body, $m)) {
                $next = $m[1];
            }
            if ($next !== '') {
                if (str_contains($next, 'captcha_page')) {
                    $q = [];
                    parse_str((string)parse_url($next, PHP_URL_QUERY), $q);
                    $tlKey = (string)($q['tl_key'] ?? '');
                    if ($tlKey === '') {
                        throw new WechatException('企微触发异地验证，但未返回 tl_key');
                    }

                    return $tlKey;
                }
                $url = str_starts_with($next, 'http') ? $next : ('https://work.weixin.qq.com' . $next);
                continue;
            }
            if ($code >= 400) {
                throw new WechatException('企业微信登录跳转失败 HTTP ' . $code);
            }
            break;
        }

        $this->finishLoginFrame();

        return null;
    }

    /**
     * 拉取验证码页并发送短信验证码，返回脱敏手机号（可能为空）。
     *
     * @throws WechatException
     */
    private function sendLoginCaptcha(string $tlKey): string
    {
        $pageUrl = self::BASE . 'mobile_confirm/captcha_page?' . http_build_query([
            'tl_key'       => $tlKey,
            'redirect_url' => 'https://work.weixin.qq.com/wework_admin/login/choose_corp?tl_key=' . $tlKey,
            'from'         => 'spamcheck',
        ]);
        [, $html] = $this->http($pageUrl, false);
        $mobile = '';
        if (preg_match('/mobile\\\\?":\\\\?"([^"\\\\]+)/', $html, $m) || preg_match('/"mobile"\s*:\s*"([^"]+)"/', $html, $m)) {
            $mobile = $m[1];
        }

        $sendUrl = self::BASE . 'mobile_confirm/send_captcha?' . http_build_query([
            'lang'   => 'zh_CN',
            'f'      => 'json',
            'ajax'   => '1',
            'random' => (string)(mt_rand() / mt_getrandmax()),
        ]);
        [, $raw] = $this->http($sendUrl, false, json_encode(['tl_key' => $tlKey], JSON_UNESCAPED_UNICODE), 20, true, 'application/json');
        $data = json_decode($raw, true);
        $err = (int)($data['result']['errCode'] ?? 0);
        // 0 或空表示成功；部分环境无 result 也算成功
        if (is_array($data) && $err !== 0 && isset($data['result']['errCode'])) {
            throw new WechatException($data['result']['humanMessage'] ?? $data['result']['message'] ?? '发送验证码失败');
        }

        return $mobile;
    }

    /**
     * @throws WechatException
     */
    private function confirmLoginCaptcha(string $tlKey, string $captcha): void
    {
        $confirmUrl = self::BASE . 'mobile_confirm/confirm_captcha?' . http_build_query([
            'lang'   => 'zh_CN',
            'f'      => 'json',
            'ajax'   => '1',
            'random' => (string)(mt_rand() / mt_getrandmax()),
        ]);
        [, $raw] = $this->http($confirmUrl, false, json_encode([
            'tl_key'  => $tlKey,
            'captcha' => $captcha,
        ], JSON_UNESCAPED_UNICODE), 20, true, 'application/json');
        $data = json_decode($raw, true);
        $err = (int)($data['result']['errCode'] ?? 0);
        if (is_array($data) && $err !== 0 && isset($data['result']['errCode'])) {
            throw new WechatException($data['result']['humanMessage'] ?? $data['result']['message'] ?? '验证码错误');
        }

        [$code] = $this->http(self::BASE . 'login/choose_corp?tl_key=' . urlencode($tlKey), false);
        if ($code >= 400) {
            throw new WechatException('验证通过后选择企业失败');
        }
        $this->finishLoginFrame();
    }

    /**
     * @throws WechatException
     */
    private function finishLoginFrame(): void
    {
        [$code] = $this->http(self::BASE . 'frame', false);
        if ($code >= 400) {
            throw new WechatException('企业微信登录未完成，无法进入管理后台');
        }
    }

    /**
     * @throws WechatException
     */
    private function json(string $path, array $query, ?string $body = null, int $timeout = 20): array
    {
        $raw = $this->request($path, $query, $body, false, $timeout);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Logger::error('企业微信后台返回非 JSON：' . substr($raw, 0, 200));
            throw new WechatException('企业微信后台返回异常，登录可能已失效');
        }
        $err = (int)($data['result']['errCode'] ?? 0);
        if ($err === -3 || (($data['result']['message'] ?? '') === 'outsession')) {
            throw new WechatException('企业微信管理后台会话已失效，请关闭后重新扫码');
        }

        return $data;
    }

    /**
     * @throws WechatException
     */
    private function request(string $path, array $query = [], ?string $body = null, bool $follow = false, int $timeout = 20): string
    {
        $query += ['lang' => 'zh_CN', 'f' => 'json', 'ajax' => '1'];
        [$code, $out] = $this->http(self::BASE . $path . '?' . http_build_query($query), $follow, $body, $timeout, true);
        if ($out === '' && $code === 0) {
            return '{"data":{}}';
        }

        return $out;
    }

    /**
     * @return array{0:int,1:string,2:string} HTTP 状态码、正文、Location
     * @throws WechatException
     */
    private function http(string $url, bool $follow, ?string $body = null, int $timeout = 20, bool $ajax = false, string $contentType = ''): array
    {
        $headers = [
            'referer: https://work.weixin.qq.com/wework_admin/frame',
            'origin: https://work.weixin.qq.com',
        ];
        if ($ajax) {
            $headers[] = 'x-requested-with: XMLHttpRequest';
        }
        if ($contentType !== '') {
            $headers[] = 'content-type: ' . $contentType;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_FOLLOWLOCATION => $follow,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36',
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        if ($raw === false) {
            if (str_contains($err, 'timed out')) {
                return [0, '', ''];
            }
            throw new WechatException('请求企业微信后台失败：' . $err);
        }
        $this->persist();
        $header = substr($raw, 0, $headerSize);
        $content = substr($raw, $headerSize);
        $location = '';
        if (preg_match('/^Location:\s*(.+)$/mi', $header, $m)) {
            $location = trim($m[1]);
        }

        return [$code, (string)$content, $location];
    }
}
