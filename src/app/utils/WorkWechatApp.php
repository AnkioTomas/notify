<?php

declare(strict_types=1);

namespace app\utils;

use app\database\model\AppModel;
use nova\framework\core\Context;
use nova\framework\core\Instance;
use nova\framework\json\Json;
use nova\framework\json\JsonDecodeException;
use nova\plugin\http\HttpClient;
use nova\plugin\http\HttpException;

use function nova\framework\config;

class WorkWechatApp extends Instance
{
    private const string BASE = 'https://qyapi.weixin.qq.com/';

    private const string CACHE_KEY_PREFIX = 'wechat.app.access_token.';

    private const int TIMEOUT = 25;

    public function __construct(private readonly AppModel $app)
    {
    }

    /**
     * @throws WechatException
     * @throws HttpException
     * @throws JsonDecodeException
     */
    public function getAccessToken(): string
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $this->app->id;
        $cache    = Context::instance()->cache;
        $hit      = $cache->get($cacheKey);
        if ($hit !== null) {
            return $hit;
        }

        $corpId = config('work_wechat.corpid') ?? '';
        if ($corpId === '' || $this->app->secret === '') {
            throw new WechatException("企业微信凭证缺失：work_wechat.corpid 或 应用[{$this->app->short_name}]的 secret 未配置");
        }

        $data  = $this->call('cgi-bin/gettoken', [
            'corpid'     => $corpId,
            'corpsecret' => $this->app->secret,
        ]);
        $token = $data['access_token'] ?? '';
        $ttl   = ($data['expires_in'] ?? 7200) - 300;
        $cache->set($cacheKey, $token, $ttl);

        return $token;
    }

    /**
     * @throws WechatException
     * @throws HttpException
     * @throws JsonDecodeException
     */
    public function sendText(string $message, string $toUser): void
    {
        if ($this->app->agent_id === '') {
            throw new WechatException("应用[{$this->app->short_name}]未配置 agent_id");
        }

        $this->call('cgi-bin/message/send', ['access_token' => $this->getAccessToken()], [
            "touser"                   => $toUser,
            "msgtype"                  => "text",
            "agentid"                  => $this->app->agent_id,
            "text"                     => ["content" => $message],
            "safe"                     => 0,
            "enable_id_trans"          => 0,
            "enable_duplicate_check"   => 0,
            "duplicate_check_interval" => 1800,
        ]);
    }

    /**
     * @throws WechatException
     * @throws HttpException
     * @throws JsonDecodeException
     */
    private function call(string $path, array $query, ?array $body = null): array
    {
        $client = HttpClient::init(self::BASE)->timeout(self::TIMEOUT);
        $client = $body === null ? $client->get() : $client->post($body);

        $resp = $client->send($path, $query);
        $data = Json::decode($resp->getBody(), true);
        $code = $data['errcode'] ?? -1;
        if ($code !== 0) {
            throw new WechatException($data['errmsg'] ?? 'API 失败', $code);
        }

        return $data;
    }
}
