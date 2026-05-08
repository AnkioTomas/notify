<?php

declare(strict_types=1);

namespace app\utils;

use function nova\framework\config;

use nova\framework\core\Context;
use nova\framework\core\Instance;
use nova\framework\json\Json;
use nova\framework\json\JsonDecodeException;
use nova\plugin\http\HttpClient;
use nova\plugin\http\HttpException;

class WorkWechatApp extends Instance
{
    private const string BASE = 'https://qyapi.weixin.qq.com/';

    private const string CACHE_KEY = 'wechat.app.access_token';

    private const int TIMEOUT = 25;

    /**
     * 配置：work_wechat.corpid、work_wechat.secret
     *
     * @throws WechatException
     */
    public function getAccessToken(): string
    {
        $cache = Context::instance()->cache;
        $hit = $cache->get(self::CACHE_KEY);
        if ($hit !== null) {
            return $hit;
        }

        $corpId = config('work_wechat.corpid') ?? '';
        $secret = config('work_wechat.secret') ?? '';
        if ($corpId === '' || $secret === '') {
            throw new WechatException('work_wechat.corpid / work_wechat.secret 未配置');
        }

        $resp = HttpClient::init(self::BASE)->timeout(self::TIMEOUT)->get()->send('cgi-bin/gettoken', [
            'corpid' => $corpId,
            'corpsecret' => $secret,
        ]);
        $data = Json::decode($resp->getBody(), true);
        $code = $data['errcode'] ?? -1;
        if ($code !== 0) {
            throw new WechatException($data['errmsg'] ?? 'API 失败', $code);
        }

        $token = $data['access_token'] ?? '';

        $ttl = $data['expires_in'] - 300;
        $cache->set(self::CACHE_KEY, $token, $ttl);

        return $token;
    }

    /**
     * @throws JsonDecodeException
     * @throws WechatException
     * @throws HttpException
     */
    public function sendText(string $agentid, string $message, string $toUser)
    {
        $q = ['access_token' => $this->getAccessToken()];
        $resp = HttpClient::init(self::BASE)->timeout(self::TIMEOUT)->post([
            "touser" => $toUser,
            "toparty" => "",
            "totag" => "",
            "msgtype" => "text",
            "agentid" => $agentid,
            "text" => [
                "content" => $message
            ],
            "safe" => 0,
            "enable_id_trans" => 0,
            "enable_duplicate_check" => 0,
            "duplicate_check_interval" => 1800

        ])->send('cgi-bin/message/send', $q);
        $data = Json::decode($resp->getBody(), true);
        $code = $data['errcode'] ?? -1;
        if ($code !== 0) {
            throw new WechatException($data['errmsg'] ?? 'API 失败', $code);
        }

    }
}
